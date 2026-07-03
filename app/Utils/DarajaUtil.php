<?php

namespace App\Utils;

use App\AccountTransaction;
use App\BusinessLocation;
use App\Contact;
use App\DarajaPayment;
use App\DarajaSetting;
use App\Events\TransactionPaymentAdded;
use App\TransactionPayment;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DarajaUtil
{
    public function sendStkPush(DarajaSetting $setting, $phone, $amount, $contact_id = null)
    {
        $phone = $this->normalizePhone($phone);
        $amount = (float) $amount;
        $timestamp = Carbon::now()->format('YmdHis');
        $callback_url = $this->callbackUrl($setting, $setting->callback_url, 'daraja.callback');
        $payload = [
            'BusinessShortCode' => trim((string) $setting->business_shortcode),
            'Password' => base64_encode(trim((string) $setting->business_shortcode).trim((string) $setting->passkey).$timestamp),
            'Timestamp' => $timestamp,
            'TransactionType' => $setting->transaction_type ?: 'CustomerPayBillOnline',
            'Amount' => max(1, (int) round($amount)),
            'PartyA' => $phone,
            'PartyB' => trim((string) $setting->business_shortcode),
            'PhoneNumber' => $phone,
            'CallBackURL' => $callback_url,
            'AccountReference' => substr($setting->account_reference ?: 'UltimatePOS', 0, 12),
            'TransactionDesc' => 'UltimatePOS',
        ];

        $response = Http::withToken($this->accessToken($setting))
            ->asJson()
            ->timeout(30)
            ->post($this->baseUrl($setting).'/mpesa/stkpush/v1/processrequest', $payload);
        $data = $response->json() ?: [];
        $accepted = $response->successful() && (string) ($data['ResponseCode'] ?? '') === '0';

        $payment = DarajaPayment::create([
            'daraja_setting_id' => $setting->id,
            'business_id' => $setting->business_id,
            'business_location_id' => $setting->business_location_id,
            'contact_id' => $contact_id ?: null,
            'source' => 'stk',
            'merchant_request_id' => $data['MerchantRequestID'] ?? null,
            'checkout_request_id' => $data['CheckoutRequestID'] ?? null,
            'phone_number' => $phone,
            'amount' => $amount,
            'business_shortcode' => $setting->business_shortcode,
            'account_reference' => $payload['AccountReference'],
            'status' => $accepted ? 'PENDING' : 'FAILED',
            'result_description' => $data['ResponseDescription'] ?? $data['errorMessage'] ?? $response->body(),
            'reconciliation_status' => $accepted ? 'unassigned' : 'ignored',
            'match_note' => $accepted ? 'Awaiting Daraja STK callback.' : 'Daraja rejected the STK request.',
            'request_payload' => $payload,
            'payload' => $data,
        ]);

        return compact('accepted', 'payment', 'data') + ['http_status' => $response->status()];
    }

    public function recordStkCallback(DarajaSetting $setting, array $payload)
    {
        $callback = data_get($payload, 'Body.stkCallback', []);
        $checkout_request_id = $callback['CheckoutRequestID'] ?? null;
        $payment = DarajaPayment::where('daraja_setting_id', $setting->id)
            ->where('checkout_request_id', $checkout_request_id)
            ->first();

        if (empty($checkout_request_id) || empty($payment)) {
            throw new \Exception('Daraja callback does not match an initiated STK request.');
        }

        $metadata = $this->callbackMetadata($callback);
        $result_code = (int) ($callback['ResultCode'] ?? 1);
        $complete = $result_code === 0 && ! empty($metadata['MpesaReceiptNumber']);

        DB::transaction(function () use ($payment, $callback, $metadata, $payload, $result_code, $complete) {
            $payment = DarajaPayment::lockForUpdate()->findOrFail($payment->id);
            $payment->merchant_request_id = $callback['MerchantRequestID'] ?? $payment->merchant_request_id;
            $payment->transaction_code = $metadata['MpesaReceiptNumber'] ?? $payment->transaction_code;
            $payment->phone_number = ! empty($metadata['PhoneNumber']) ? $this->normalizePhone($metadata['PhoneNumber']) : $payment->phone_number;
            $payment->amount = $metadata['Amount'] ?? $payment->amount;
            $payment->transaction_date = $this->parseTransactionDate($metadata['TransactionDate'] ?? null) ?: $payment->transaction_date;
            $payment->status = $complete ? 'COMPLETE' : 'FAILED';
            $payment->result_code = $result_code;
            $payment->result_description = $callback['ResultDesc'] ?? null;
            $payment->reconciliation_status = $complete ? $payment->reconciliation_status : 'ignored';
            $payment->match_note = $complete ? 'Successful Daraja STK callback received.' : ($callback['ResultDesc'] ?? 'Daraja STK request failed.');
            $payment->payload = $payload;
            $payment->save();
        });

        $payment = $payment->fresh();
        if ($complete) {
            $payment = $this->linkExistingPosPayment($payment) ?: $payment;
        }

        return $payment->fresh();
    }

    public function recordC2bPayment(DarajaSetting $setting, array $payload)
    {
        $transaction_code = strtoupper(trim((string) ($payload['TransID'] ?? '')));
        if ($transaction_code === '') {
            throw new \Exception('Daraja C2B confirmation is missing TransID.');
        }

        $existing = DarajaPayment::where('transaction_code', $transaction_code)->first();
        if (! empty($existing)) {
            return $existing;
        }

        $phone = $this->normalizePhone($payload['MSISDN'] ?? '');
        $contact = $this->matchContact($setting->business_id, $phone, $payload['BillRefNumber'] ?? null);
        $payment = DarajaPayment::create([
            'daraja_setting_id' => $setting->id,
            'business_id' => $setting->business_id,
            'business_location_id' => $setting->business_location_id,
            'contact_id' => optional($contact)->id,
            'source' => 'c2b',
            'transaction_code' => $transaction_code,
            'phone_number' => $phone,
            'amount' => (float) ($payload['TransAmount'] ?? 0),
            'business_shortcode' => $payload['BusinessShortCode'] ?? $setting->business_shortcode,
            'account_reference' => $payload['BillRefNumber'] ?? null,
            'status' => 'COMPLETE',
            'result_code' => 0,
            'result_description' => 'Daraja C2B confirmation received.',
            'transaction_date' => $this->parseTransactionDate($payload['TransTime'] ?? null) ?: now(),
            'reconciliation_status' => ! empty($contact) ? 'matched' : 'unassigned',
            'match_reason' => ! empty($contact) ? 'customer_reference_or_phone' : null,
            'match_note' => ! empty($contact) ? 'Matched customer from C2B reference or phone number.' : 'No unique customer match was found.',
            'payload' => $payload,
        ]);

        $payment = $this->linkExistingPosPayment($payment) ?: $payment;
        if (! $payment->is_attached && ! empty($contact) && $this->customerHasSellDue($contact->id)) {
            try {
                $this->attachToCustomerDue($payment, $contact->id, null, true);
            } catch (\Exception $e) {
                Log::warning('Daraja C2B auto attachment skipped: '.$e->getMessage(), ['daraja_payment_id' => $payment->id]);
            }
        }

        return $payment->fresh();
    }

    public function registerC2bUrls(DarajaSetting $setting)
    {
        $payload = [
            'ShortCode' => $setting->business_shortcode,
            'ResponseType' => $setting->c2b_response_type ?: 'Completed',
            'ConfirmationURL' => $this->callbackUrl($setting, $setting->confirmation_url, 'daraja.c2b_confirmation'),
            'ValidationURL' => $this->callbackUrl($setting, $setting->validation_url, 'daraja.c2b_validation'),
        ];

        $response = Http::withToken($this->accessToken($setting))
            ->asJson()->timeout(30)
            ->post($this->baseUrl($setting).'/mpesa/c2b/v1/registerurl', $payload);

        return ['successful' => $response->successful(), 'data' => $response->json() ?: [], 'status' => $response->status()];
    }

    public function hasCompletePayment($business_id, $transaction_code, $amount = null)
    {
        $query = DarajaPayment::where('business_id', $business_id)
            ->where('transaction_code', strtoupper(trim((string) $transaction_code)))
            ->where('status', 'COMPLETE');
        if ($amount !== null) {
            $query->whereRaw('ABS(amount - ?) <= 0.01', [(float) $amount]);
        }

        return $query->exists();
    }

    public function linkPosTransactionPayment(TransactionPayment $transaction_payment, $transaction = null, $user_id = null)
    {
        if ($transaction_payment->method !== 'custom_pay_1' || empty($transaction_payment->transaction_no)) {
            return null;
        }

        $payment = DarajaPayment::where('business_id', $transaction_payment->business_id)
            ->where('transaction_code', strtoupper(trim((string) $transaction_payment->transaction_no)))
            ->where('status', 'COMPLETE')
            ->whereRaw('ABS(amount - ?) <= 0.01', [(float) $transaction_payment->amount])
            ->where(function ($query) use ($transaction_payment) {
                $query->whereNull('transaction_payment_id')->orWhere('transaction_payment_id', $transaction_payment->id);
            })->first();

        if (empty($payment)) {
            return null;
        }

        $transaction = $transaction ?: $transaction_payment->transaction;
        $contact_id = ! empty($transaction->contact_id) ? $transaction->contact_id : $transaction_payment->payment_for;
        $location_id = ! empty($transaction->location_id) ? $transaction->location_id : $payment->business_location_id;
        $verified_by = $user_id ?: $transaction_payment->created_by;

        DB::transaction(function () use ($payment, $transaction_payment, $transaction, $contact_id, $location_id, $verified_by) {
            $payment = DarajaPayment::lockForUpdate()->findOrFail($payment->id);
            if (! empty($payment->transaction_payment_id) && $payment->transaction_payment_id != $transaction_payment->id) {
                return;
            }

            $payment->contact_id = $contact_id;
            $payment->business_location_id = $payment->business_location_id ?: $location_id;
            $payment->transaction_payment_id = $transaction_payment->id;
            $payment->is_attached = true;
            $payment->reconciliation_status = 'attached';
            $payment->match_reason = 'pos_transaction_code';
            $payment->match_note = 'Linked from POS using the Daraja M-PESA receipt number.';
            $payment->attached_by = $verified_by;
            $payment->attached_at = now();
            $payment->save();

            $transaction_payment->mpesa_verification_status = 'verified';
            $transaction_payment->mpesa_verified_by = $verified_by;
            $transaction_payment->mpesa_verified_at = now();
            $transaction_payment->mpesa_verification_note = 'Verified by Daraja receipt '.$payment->transaction_code.'.';
            $transaction_payment->save();

            if (! empty($transaction)) {
                $transaction_payment->setRelation('transaction', $transaction);
                AccountTransaction::updateAccountTransaction($transaction_payment, $transaction->type);
            }
        });

        return $payment->fresh();
    }

    public function attachToCustomerDue(DarajaPayment $payment, $contact_id, $user_id = null, $auto = false)
    {
        if ($payment->is_attached || $payment->status !== 'COMPLETE') {
            throw new \Exception(__('lang_v1.daraja_payment_not_attachable'));
        }

        $contact = Contact::where('business_id', $payment->business_id)
            ->whereIn('type', ['customer', 'both'])->findOrFail($contact_id);
        $created_by = $user_id ?: $this->defaultUserId($payment->business_id);
        if (empty($created_by)) {
            throw new \Exception('No user was found for Daraja payment posting.');
        }

        return DB::transaction(function () use ($payment, $contact, $created_by, $auto) {
            $payment = DarajaPayment::lockForUpdate()->findOrFail($payment->id);
            if ($payment->is_attached) {
                throw new \Exception(__('lang_v1.daraja_payment_not_attachable'));
            }

            $location = BusinessLocation::findOrFail($payment->business_location_id);
            $account_id = $this->getMpesaAccountId($location);
            $transaction_payment = TransactionPayment::create([
                'amount' => $payment->amount,
                'method' => 'custom_pay_1',
                'transaction_no' => $payment->transaction_code,
                'paid_on' => $payment->transaction_date ?: now(),
                'created_by' => $created_by,
                'payment_for' => $contact->id,
                'business_id' => $payment->business_id,
                'is_advance' => 1,
                'payment_ref_no' => 'DR-'.$payment->transaction_code,
                'account_id' => $account_id,
                'payment_type' => AccountTransaction::getAccountTransactionType('sell'),
                'note' => 'Daraja '.$payment->source.' payment '.$payment->transaction_code.'.',
                'mpesa_verification_status' => 'verified',
                'mpesa_verified_by' => $created_by,
                'mpesa_verified_at' => now(),
                'mpesa_verification_note' => 'Verified by Daraja callback.',
            ]);

            event(new TransactionPaymentAdded($transaction_payment, [
                'transaction_type' => 'sell',
                'payment_type' => AccountTransaction::getAccountTransactionType('sell'),
                'amount' => $payment->amount,
                'account_id' => $account_id,
            ]));

            $transactionUtil = new TransactionUtil();
            $excess_amount = $transactionUtil->payAtOnce($transaction_payment, 'sell');
            if (! empty($excess_amount)) {
                $transactionUtil->updateContactBalance($contact, $excess_amount);
            }

            $payment->contact_id = $contact->id;
            $payment->transaction_payment_id = $transaction_payment->id;
            $payment->is_attached = true;
            $payment->auto_attached = $auto;
            $payment->reconciliation_status = 'attached';
            $payment->match_reason = $auto ? 'auto' : 'manual';
            $payment->match_note = $auto ? 'Automatically attached to customer due.' : 'Manually attached to customer due.';
            $payment->attached_by = $created_by;
            $payment->attached_at = now();
            $payment->save();

            return $transaction_payment;
        });
    }

    protected function linkExistingPosPayment(DarajaPayment $payment)
    {
        if ($payment->status !== 'COMPLETE' || empty($payment->transaction_code)) {
            return null;
        }

        $transaction_payment = TransactionPayment::with('transaction')
            ->where('business_id', $payment->business_id)
            ->where('method', 'custom_pay_1')
            ->where('transaction_no', $payment->transaction_code)
            ->whereNotNull('transaction_id')->orderByDesc('id')->first();

        return empty($transaction_payment)
            ? null
            : $this->linkPosTransactionPayment($transaction_payment, $transaction_payment->transaction);
    }

    protected function accessToken(DarajaSetting $setting)
    {
        $key = 'daraja_token_'.$setting->id.'_'.sha1($setting->environment.$setting->consumer_key.$setting->consumer_secret);

        return Cache::remember($key, now()->addMinutes(50), function () use ($setting) {
            $response = Http::withBasicAuth($setting->consumer_key, $setting->consumer_secret)
                ->timeout(20)
                ->get($this->baseUrl($setting).'/oauth/v1/generate', ['grant_type' => 'client_credentials']);
            if (! $response->successful() || empty($response->json('access_token'))) {
                throw new \Exception(__('lang_v1.daraja_oauth_failed'));
            }

            return $response->json('access_token');
        });
    }

    protected function baseUrl(DarajaSetting $setting)
    {
        return $setting->environment === 'production' ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke';
    }

    protected function callbackMetadata(array $callback)
    {
        $metadata = [];
        foreach ((array) data_get($callback, 'CallbackMetadata.Item', []) as $item) {
            if (isset($item['Name'])) {
                $metadata[$item['Name']] = $item['Value'] ?? null;
            }
        }

        return $metadata;
    }

    protected function callbackUrl(DarajaSetting $setting, $configured_url, $route_name)
    {
        $url = trim((string) $configured_url) ?: route($route_name, ['setting' => $setting->id]);
        if (preg_match('/[?&]token=/', $url)) {
            return $url;
        }
        $separator = strpos($url, '?') === false ? '?' : '&';

        return $url.$separator.'token='.urlencode($setting->callback_token);
    }

    public function normalizePhone($phone)
    {
        $phone = preg_replace('/\D+/', '', (string) $phone);
        if (strpos($phone, '0') === 0 && strlen($phone) === 10) {
            return '254'.substr($phone, 1);
        }
        if (strpos($phone, '7') === 0 && strlen($phone) === 9) {
            return '254'.$phone;
        }

        return $phone;
    }

    protected function parseTransactionDate($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('YmdHis', (string) $value);
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function matchContact($business_id, $phone, $reference = null)
    {
        if (preg_match('/customer[_-]?id[_-]?(\d+)/i', (string) $reference, $matches)) {
            $contact = Contact::where('business_id', $business_id)->whereIn('type', ['customer', 'both'])->find($matches[1]);
            if (! empty($contact)) {
                return $contact;
            }
        }

        $international = $this->normalizePhone($phone);
        $local = strpos($international, '254') === 0 ? '0'.substr($international, 3) : $international;
        $matches = Contact::where('business_id', $business_id)
            ->whereIn('type', ['customer', 'both'])
            ->whereIn('mobile', array_unique([$international, '+'.$international, $local]))
            ->limit(2)->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    protected function customerHasSellDue($contact_id)
    {
        $due = DB::table('transactions')
            ->where('contact_id', $contact_id)
            ->whereIn('type', ['sell', 'opening_balance'])
            ->where(function ($query) {
                $query->where('type', 'opening_balance')
                    ->orWhere(function ($inner) {
                        $inner->where('type', 'sell')->where('status', 'final');
                    });
            })
            ->where('payment_status', '!=', 'paid')
            ->select(DB::raw('COALESCE(SUM(final_total - (SELECT COALESCE(SUM(IF(tp.is_return = 1, -1 * tp.amount, tp.amount)), 0) FROM transaction_payments as tp WHERE tp.transaction_id = transactions.id)), 0) as due'))
            ->value('due');

        return (float) $due > 0;
    }

    protected function defaultUserId($business_id)
    {
        return User::where('business_id', $business_id)->where('user_type', 'user')->orderBy('id')->value('id');
    }

    protected function getMpesaAccountId(BusinessLocation $location)
    {
        $accounts = json_decode($location->default_payment_accounts, true);

        return $accounts['custom_pay_1']['account'] ?? null;
    }
}
