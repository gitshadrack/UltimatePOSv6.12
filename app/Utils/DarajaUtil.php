<?php

namespace App\Utils;

use App\AccountTransaction;
use App\BusinessLocation;
use App\CashRegisterTransaction;
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

        $response = $this->httpClient()->withToken($this->accessToken($setting))
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
            'business_shortcode' => $payload['BusinessShortCode'] ?? ($setting->c2b_shortcode ?: $setting->business_shortcode),
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
        $c2b_shortcode = trim((string) ($setting->c2b_shortcode ?: $setting->business_shortcode));
        $payload = [
            'ShortCode' => $c2b_shortcode,
            'ResponseType' => $setting->c2b_response_type ?: 'Completed',
            'ConfirmationURL' => $this->callbackUrl($setting, $setting->confirmation_url, 'daraja.c2b_confirmation'),
            'ValidationURL' => $this->callbackUrl($setting, $setting->validation_url, 'daraja.c2b_validation'),
        ];

        $response = $this->httpClient()->withToken($this->accessToken($setting))
            ->asJson()->timeout(30)
            ->post($this->baseUrl($setting).'/mpesa/c2b/v1/registerurl', $payload);

        $data = $response->json() ?: [];
        $already_registered = (string) ($data['errorCode'] ?? '') === '500.003.1001'
            && stripos((string) ($data['errorMessage'] ?? ''), 'Duplicate notification info') !== false;

        return [
            'successful' => $response->successful() || $already_registered,
            'already_registered' => $already_registered,
            'data' => $data,
            'status' => $response->status(),
        ];
    }

    /**
     * Submit a full reversal for a directly linked POS M-PESA payment.
     */
    public function requestReversal(DarajaSetting $setting, DarajaPayment $payment, $reason, $user_id)
    {
        $payment = DB::transaction(function () use ($setting, $payment, $reason, $user_id) {
            $payment = DarajaPayment::lockForUpdate()->findOrFail($payment->id);

            if ((int) $payment->daraja_setting_id !== (int) $setting->id
                || (int) $payment->business_id !== (int) $setting->business_id) {
                throw new \Exception(__('lang_v1.daraja_reversal_invalid_payment'));
            }
            if ($payment->status !== 'COMPLETE' || empty($payment->transaction_code)) {
                throw new \Exception(__('lang_v1.daraja_reversal_requires_complete_payment'));
            }
            if ($payment->reversal_status !== 'none') {
                throw new \Exception(__('lang_v1.daraja_reversal_already_requested'));
            }

            $transaction_payment = TransactionPayment::with('transaction')->find($payment->transaction_payment_id);
            if (empty($transaction_payment) || empty($transaction_payment->transaction_id)
                || empty($transaction_payment->transaction) || $transaction_payment->transaction->type !== 'sell') {
                throw new \Exception(__('lang_v1.daraja_reversal_requires_linked_sale'));
            }
            if (abs((float) $transaction_payment->amount - (float) $payment->amount) > 0.01) {
                throw new \Exception(__('lang_v1.daraja_reversal_amount_mismatch'));
            }

            $payment->reversal_status = 'requested';
            $payment->reversal_note = trim((string) $reason);
            $payment->reversal_requested_by = $user_id;
            $payment->reversal_requested_at = Carbon::now();
            $payment->save();

            return $payment;
        });

        $payload = [
            'Initiator' => trim((string) $setting->initiator_name),
            'SecurityCredential' => trim((string) $setting->security_credential),
            'CommandID' => 'TransactionReversal',
            'TransactionID' => strtoupper(trim((string) $payment->transaction_code)),
            'Amount' => max(1, (int) round((float) $payment->amount)),
            'ReceiverParty' => trim((string) ($payment->business_shortcode ?: $setting->business_shortcode)),
            'RecieverIdentifierType' => '11',
            'ResultURL' => $this->callbackUrl($setting, null, 'daraja.reversal_result'),
            'QueueTimeOutURL' => $this->callbackUrl($setting, null, 'daraja.reversal_timeout'),
            'Remarks' => substr(trim((string) $reason), 0, 100),
            'Occasion' => 'UltimatePOS',
        ];

        try {
            $response = $this->httpClient()->withToken($this->accessToken($setting))
                ->asJson()->timeout(30)
                ->post($this->baseUrl($setting).'/mpesa/reversal/v1/request', $payload);
            $data = $response->json() ?: [];
            $accepted = $response->successful() && (string) ($data['ResponseCode'] ?? '') === '0';

            $payment->refresh();
            $payment->reversal_status = $accepted ? 'pending' : 'failed';
            $payment->reversal_request_id = $data['OriginatorConversationID'] ?? $data['ConversationID'] ?? null;
            $payment->reversal_response = ['request' => $payload, 'response' => $data, 'http_status' => $response->status()];
            if (! $accepted) {
                $payment->reversal_note = trim($payment->reversal_note.' | '.($data['errorMessage'] ?? $data['ResponseDescription'] ?? 'Provider rejected the reversal request.'));
            }
            $payment->save();

            return ['accepted' => $accepted, 'payment' => $payment, 'data' => $data, 'http_status' => $response->status()];
        } catch (\Throwable $e) {
            $payment->refresh();
            $payment->reversal_status = 'failed';
            $payment->reversal_note = trim($payment->reversal_note.' | Request failed: '.$e->getMessage());
            $payment->save();
            throw $e;
        }
    }

    public function recordReversalResult(DarajaSetting $setting, array $payload)
    {
        $result = (array) data_get($payload, 'Result', []);
        $request_id = $result['OriginatorConversationID'] ?? null;
        $payment = DarajaPayment::where('daraja_setting_id', $setting->id)
            ->where('reversal_request_id', $request_id)
            ->first();

        if (empty($request_id) || empty($payment)) {
            throw new \Exception('Daraja reversal result does not match a requested reversal.');
        }

        $result_code = (int) ($result['ResultCode'] ?? 1);
        if ($result_code !== 0) {
            $payment->reversal_status = 'failed';
            $payment->reversal_note = trim($payment->reversal_note.' | '.($result['ResultDesc'] ?? 'M-PESA reversal failed.'));
            $payment->reversal_response = array_merge((array) $payment->reversal_response, ['result' => $payload]);
            $payment->save();

            return $payment->fresh();
        }

        return DB::transaction(function () use ($payment, $payload, $result) {
            $payment = DarajaPayment::lockForUpdate()->findOrFail($payment->id);
            if ($payment->reversal_status === 'successful') {
                return $payment;
            }

            $this->applySuccessfulReversal($payment, $result['ResultDesc'] ?? 'M-PESA reversal completed.');
            $payment->reversal_status = 'successful';
            $payment->reversed_at = Carbon::now();
            $payment->reversal_response = array_merge((array) $payment->reversal_response, ['result' => $payload]);
            $payment->save();

            return $payment;
        });
    }

    public function recordReversalTimeout(DarajaSetting $setting, array $payload)
    {
        $result = (array) data_get($payload, 'Result', []);
        $request_id = $result['OriginatorConversationID'] ?? null;
        $payment = DarajaPayment::where('daraja_setting_id', $setting->id)
            ->where('reversal_request_id', $request_id)
            ->first();

        if (empty($request_id) || empty($payment)) {
            throw new \Exception('Daraja reversal timeout does not match a requested reversal.');
        }

        if ($payment->reversal_status !== 'successful') {
            // A queue timeout is not proof that money was not reversed. Leave
            // it pending for provider-status reconciliation instead of retrying.
            $payment->reversal_status = 'pending';
            $payment->reversal_note = trim($payment->reversal_note.' | Provider queue timeout; verify status before any retry.');
            $payment->reversal_response = array_merge((array) $payment->reversal_response, ['timeout' => $payload]);
            $payment->save();
        }

        return $payment->fresh();
    }

    protected function applySuccessfulReversal(DarajaPayment $payment, $result_description)
    {
        if (! empty($payment->reversal_transaction_payment_id)) {
            return TransactionPayment::find($payment->reversal_transaction_payment_id);
        }

        $original = TransactionPayment::with('transaction')->lockForUpdate()->find($payment->transaction_payment_id);
        if (empty($original) || empty($original->transaction_id) || empty($original->transaction)) {
            throw new \Exception('The linked POS payment is unavailable for reversal accounting.');
        }

        $created_by = $payment->reversal_requested_by ?: $original->created_by;
        $reversal = TransactionPayment::create([
            'transaction_id' => $original->transaction_id,
            'business_id' => $original->business_id,
            'amount' => $original->amount,
            'method' => 'custom_pay_1',
            'transaction_no' => 'REV-'.strtoupper((string) $payment->transaction_code),
            'paid_on' => Carbon::now()->toDateTimeString(),
            'created_by' => $created_by,
            'payment_for' => $original->payment_for,
            'payment_ref_no' => 'REV-DJ-'.$payment->id,
            'account_id' => $original->account_id,
            'payment_type' => 'debit',
            'is_return' => 1,
            'note' => 'M-PESA reversal for '.$payment->transaction_code.'. '.$result_description,
            'mpesa_verification_status' => 'verified',
            'mpesa_verified_by' => $created_by,
            'mpesa_verified_at' => Carbon::now(),
            'mpesa_verification_note' => 'Verified by successful Daraja reversal callback.',
        ]);

        event(new TransactionPaymentAdded($reversal, [
            'transaction_type' => 'sell',
            'amount' => $reversal->amount,
            'account_id' => $reversal->account_id,
            'is_return' => 1,
        ]));

        $register_id = CashRegisterTransaction::where('transaction_id', $original->transaction_id)
            ->where('pay_method', 'custom_pay_1')
            ->orderBy('id')
            ->value('cash_register_id');
        if (! empty($register_id)) {
            CashRegisterTransaction::create([
                'cash_register_id' => $register_id,
                'amount' => $reversal->amount,
                'pay_method' => 'custom_pay_1',
                'type' => 'debit',
                'transaction_type' => 'refund',
                'transaction_id' => $original->transaction_id,
            ]);
        }

        (new TransactionUtil())->updatePaymentStatus($original->transaction_id, $original->transaction->final_total);
        $payment->reversal_transaction_payment_id = $reversal->id;

        return $reversal;
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
            try {
                $response = $this->httpClient()
                    ->withBasicAuth($setting->consumer_key, $setting->consumer_secret)
                    ->timeout(20)
                    ->get($this->baseUrl($setting).'/oauth/v1/generate', ['grant_type' => 'client_credentials']);
            } catch (\Throwable $e) {
                Log::error('M-PESA OAuth connection failed.', [
                    'setting_id' => $setting->id,
                    'environment' => $setting->environment,
                    'error' => $e->getMessage(),
                ]);

                if (stripos($e->getMessage(), 'cURL error 60') !== false || stripos($e->getMessage(), 'certificate') !== false) {
                    throw new \Exception(__('lang_v1.daraja_ssl_failed'));
                }

                throw new \Exception(__('lang_v1.daraja_oauth_connection_failed'));
            }
            if (! $response->successful() || empty($response->json('access_token'))) {
                Log::warning('M-PESA OAuth rejected.', [
                    'setting_id' => $setting->id,
                    'environment' => $setting->environment,
                    'http_status' => $response->status(),
                    'error_code' => $response->json('errorCode') ?: $response->json('error'),
                    'error_message' => $response->json('errorMessage') ?: $response->json('message'),
                ]);
                throw new \Exception(__('lang_v1.daraja_oauth_failed'));
            }

            return $response->json('access_token');
        });
    }

    protected function httpClient()
    {
        $client = Http::acceptJson();
        $ca_bundle = trim((string) config('services.daraja.ca_bundle'));

        if ($ca_bundle !== '') {
            if (! is_file($ca_bundle) || ! is_readable($ca_bundle)) {
                throw new \RuntimeException(__('lang_v1.daraja_ca_bundle_invalid'));
            }
            $client = $client->withOptions(['verify' => $ca_bundle]);
        }

        return $client;
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
