<?php

namespace App\Utils;

use App\AccountTransaction;
use App\BusinessLocation;
use App\Contact;
use App\Events\TransactionPaymentAdded;
use App\IntaSendPayment;
use App\IntaSendSetting;
use App\TransactionPayment;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IntaSendUtil
{
    /**
     * Records an IntaSend webhook payload and attempts a conservative match.
     *
     * @param  array  $payload
     * @return \App\IntaSendPayment
     */
    public function recordWebhookPayment(array $payload)
    {
        $normalized = $this->normalizePayload($payload);

        if (empty($normalized['transaction_code'])) {
            throw new \Exception('Missing IntaSend transaction reference.');
        }

        $existing = IntaSendPayment::where('transaction_code', $normalized['transaction_code'])->first();
        if (! empty($existing)) {
            return $existing;
        }

        $setting = $this->resolveSetting($normalized);
        $location = ! empty($setting) ? $setting->location : null;
        $business_id = ! empty($setting) ? $setting->business_id : null;

        $contact = null;
        $match = ['contact' => null, 'reason' => null, 'note' => null];
        if (! empty($business_id)) {
            $match = $this->matchContact($business_id, $normalized);
            $contact = $match['contact'];
        }

        $is_complete = strtoupper((string) $normalized['status']) == 'COMPLETE';
        $reconciliation_status = ! empty($contact) ? 'matched' : 'unassigned';
        $match_note = $match['note'];
        if (! $is_complete) {
            $reconciliation_status = 'ignored';
            $match_note = 'Ignored because IntaSend state is not COMPLETE.';
        } elseif (empty($location)) {
            $match_note = 'No active IntaSend location setting matched the incoming till/paybill number.';
        } elseif (empty($contact)) {
            $match_note = $match_note ?: 'No customer matched api_ref or phone number.';
        } elseif (! $this->customerHasSellDue($contact->id)) {
            $match_note = 'Customer matched, but no outstanding sale/opening-balance due was found.';
        }

        $payment = IntaSendPayment::create([
            'business_id' => $business_id,
            'business_location_id' => ! empty($location) ? $location->id : null,
            'contact_id' => ! empty($contact) ? $contact->id : null,
            'till_number' => $normalized['till_number'],
            'transaction_code' => $normalized['transaction_code'],
            'phone_number' => $normalized['phone_number'],
            'amount' => $normalized['amount'],
            'net_amount' => $normalized['net_amount'],
            'charges' => $normalized['charges'],
            'currency' => $normalized['currency'],
            'status' => $normalized['status'],
            'api_ref' => $normalized['api_ref'],
            'reconciliation_status' => $reconciliation_status,
            'match_reason' => $match['reason'],
            'match_note' => $match_note,
            'payload' => $payload,
            'note' => ! $is_complete ? 'Ignored because IntaSend state is not COMPLETE.' : null,
        ]);

        if ($is_complete) {
            $payment = $this->linkExistingPosPayment($payment) ?: $payment;
        }

        if (! $payment->is_attached && $is_complete && ! empty($contact) && ! empty($location) && $this->customerHasSellDue($contact->id)) {
            try {
                $this->attachToCustomerDue($payment, $contact->id, null, true);
            } catch (\Exception $e) {
                Log::warning('IntaSend auto attachment skipped: '.$e->getMessage(), [
                    'intasend_payment_id' => $payment->id,
                ]);
            }
        }

        return $payment->fresh();
    }

    /**
     * Attaches an inbound IntaSend payment as a customer due payment.
     *
     * @param  \App\IntaSendPayment  $payment
     * @param  int  $contact_id
     * @param  int|null  $user_id
     * @param  bool  $auto
     * @return \App\TransactionPayment
     */
    public function attachToCustomerDue(IntaSendPayment $payment, $contact_id, $user_id = null, $auto = false)
    {
        if ($payment->is_attached) {
            throw new \Exception('This IntaSend payment is already attached.');
        }

        if (strtoupper((string) $payment->status) != 'COMPLETE') {
            throw new \Exception('Only COMPLETE IntaSend payments can be attached.');
        }

        if (empty($payment->business_id) || empty($payment->business_location_id)) {
            throw new \Exception('The IntaSend payment must be mapped to a business location before attaching.');
        }

        $contact = Contact::where('business_id', $payment->business_id)
            ->whereIn('type', ['customer', 'both'])
            ->findOrFail($contact_id);

        $created_by = $user_id ?: $this->defaultUserId($payment->business_id);
        if (empty($created_by)) {
            throw new \Exception('No user was found for IntaSend payment posting.');
        }

        return DB::transaction(function () use ($payment, $contact, $created_by, $auto) {
            $payment = IntaSendPayment::lockForUpdate()->findOrFail($payment->id);
            if ($payment->is_attached) {
                throw new \Exception('This IntaSend payment is already attached.');
            }

            $location = BusinessLocation::findOrFail($payment->business_location_id);
            $account_id = $this->getMpesaAccountId($location);

            $payment_ref_no = 'IS-'.$payment->transaction_code;
            $transaction_payment = TransactionPayment::create([
                'amount' => $payment->amount,
                'method' => 'custom_pay_1',
                'transaction_no' => strtoupper(trim((string) $payment->transaction_code)),
                'paid_on' => Carbon::now()->toDateTimeString(),
                'created_by' => $created_by,
                'payment_for' => $contact->id,
                'business_id' => $payment->business_id,
                'is_advance' => 1,
                'payment_ref_no' => $payment_ref_no,
                'account_id' => $account_id,
                'payment_type' => AccountTransaction::getAccountTransactionType('sell'),
                'note' => trim('IntaSend payment '.$payment->transaction_code.($auto ? ' auto-attached.' : ' manually attached.')),
                'mpesa_verification_status' => 'verified',
                'mpesa_verified_by' => $created_by,
                'mpesa_verified_at' => Carbon::now(),
                'mpesa_verification_note' => 'Verified by IntaSend webhook reconciliation.',
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
            $payment->match_reason = $payment->match_reason ?: ($auto ? 'auto' : 'manual');
            $payment->match_note = $auto ? 'Auto-attached to customer due payment.' : 'Manually linked to customer due payment.';
            $payment->attached_by = $created_by;
            $payment->attached_at = Carbon::now();
            $payment->note = $transaction_payment->note;
            $payment->save();

            return $transaction_payment;
        });
    }

    public function normalizePayload(array $payload)
    {
        $transaction_code = $payload['mpesa_reference']
            ?? $payload['provider_reference']
            ?? $payload['provider_ref']
            ?? $payload['tracking_id']
            ?? $payload['invoice_id']
            ?? null;

        return [
            'status' => $payload['state'] ?? $payload['status'] ?? null,
            'transaction_code' => strtoupper(trim((string) $transaction_code)),
            'amount' => $payload['value'] ?? $payload['amount'] ?? $payload['net_amount'] ?? 0,
            'net_amount' => $payload['net_amount'] ?? $payload['amount'] ?? $payload['value'] ?? 0,
            'charges' => $payload['charges'] ?? 0,
            'currency' => $payload['currency'] ?? null,
            'phone_number' => $this->normalizePhone($payload['account'] ?? $payload['phone_number'] ?? ''),
            'api_ref' => $payload['api_ref'] ?? data_get($payload, 'metadata.api_ref'),
            'till_number' => $payload['till_identifier'] ?? data_get($payload, 'metadata.till_number') ?? data_get($payload, 'metadata.till'),
        ];
    }

    public function hasCompletePayment($business_id, $transaction_code, $amount = null)
    {
        return IntaSendPayment::where('business_id', $business_id)
            ->where('transaction_code', $this->normalizeTransactionCode($transaction_code))
            ->where('status', 'COMPLETE')
            ->exists();
    }

    public function linkPosTransactionPayment(TransactionPayment $transaction_payment, $transaction = null, $user_id = null)
    {
        if ($transaction_payment->method != 'custom_pay_1' || empty($transaction_payment->transaction_no)) {
            return null;
        }

        $transaction_code = $this->normalizeTransactionCode($transaction_payment->transaction_no);
        if ($transaction_code === '') {
            return null;
        }

        $query = IntaSendPayment::where('business_id', $transaction_payment->business_id)
            ->where('transaction_code', $transaction_code)
            ->where('status', 'COMPLETE')
            ->where(function ($query) use ($transaction_payment) {
                $query->whereNull('transaction_payment_id')
                    ->orWhere('transaction_payment_id', $transaction_payment->id);
            });

        $payment = $query->first();
        if (empty($payment)) {
            return null;
        }

        $transaction = $transaction ?: $transaction_payment->transaction;
        $contact_id = ! empty($transaction->contact_id) ? $transaction->contact_id : $transaction_payment->payment_for;
        $location_id = ! empty($transaction->location_id) ? $transaction->location_id : $payment->business_location_id;
        $verified_by = $user_id ?: $transaction_payment->created_by;

        DB::transaction(function () use ($payment, $transaction_payment, $transaction, $contact_id, $location_id, $verified_by) {
            $payment = IntaSendPayment::lockForUpdate()->findOrFail($payment->id);
            if (! empty($payment->transaction_payment_id) && $payment->transaction_payment_id != $transaction_payment->id) {
                return;
            }

            $payment->contact_id = $contact_id;
            $payment->business_location_id = $payment->business_location_id ?: $location_id;
            $payment->transaction_payment_id = $transaction_payment->id;
            $payment->is_attached = true;
            $payment->auto_attached = false;
            $payment->reconciliation_status = 'attached';
            $payment->match_reason = 'pos_transaction_code';
            $payment->match_note = 'Linked from POS M-PESA transaction code during billing.';
            $payment->attached_by = $verified_by;
            $payment->attached_at = Carbon::now();
            $payment->note = trim('Linked to POS payment '.$transaction_payment->payment_ref_no);
            $payment->save();

            $transaction_payment->mpesa_verification_status = 'verified';
            $transaction_payment->mpesa_verified_by = $verified_by;
            $transaction_payment->mpesa_verified_at = Carbon::now();
            $transaction_payment->mpesa_verification_note = 'Verified by matching IntaSend collection '.$payment->transaction_code.'.';
            $transaction_payment->save();

            if (! empty($transaction)) {
                $transaction_payment->setRelation('transaction', $transaction);
                AccountTransaction::updateAccountTransaction($transaction_payment, $transaction->type);
            }
        });

        return $payment->fresh();
    }

    protected function linkExistingPosPayment(IntaSendPayment $payment)
    {
        if (strtoupper((string) $payment->status) != 'COMPLETE' || empty($payment->transaction_code)) {
            return null;
        }

        $transaction_payment = TransactionPayment::with('transaction')
            ->where('business_id', $payment->business_id)
            ->where('method', 'custom_pay_1')
            ->where('transaction_no', $this->normalizeTransactionCode($payment->transaction_code))
            ->whereNotNull('transaction_id')
            ->orderByDesc('id')
            ->first();

        if (empty($transaction_payment)) {
            return null;
        }

        return $this->linkPosTransactionPayment($transaction_payment, $transaction_payment->transaction);
    }

    public function resolveSetting(array $normalized)
    {
        if (! empty($normalized['till_number'])) {
            return IntaSendSetting::with('location')
                ->where('till_or_paybill_number', $normalized['till_number'])
                ->where('is_active', 1)
                ->first();
        }

        $configured_settings = IntaSendSetting::with('location')
            ->where('is_active', 1)
            ->where(function ($query) {
                $query->whereNotNull('intasend_public_key')
                    ->where('intasend_public_key', '!=', '')
                    ->orWhereNotNull('intasend_secret_key')
                    ->where('intasend_secret_key', '!=', '')
                    ->orWhereNotNull('payment_link_url')
                    ->where('payment_link_url', '!=', '');
            })
            ->limit(2)
            ->get();

        if ($configured_settings->count() == 1) {
            return $configured_settings->first();
        }

        return null;
    }

    protected function matchContact($business_id, array $normalized)
    {
        if (! empty($normalized['api_ref']) && preg_match('/customer_id_(\d+)/', $normalized['api_ref'], $matches)) {
            $contact = Contact::where('business_id', $business_id)
                ->whereIn('type', ['customer', 'both'])
                ->find($matches[1]);

            return [
                'contact' => $contact,
                'reason' => ! empty($contact) ? 'api_ref' : null,
                'note' => ! empty($contact) ? 'Matched by api_ref customer_id_'.$matches[1].'.' : 'api_ref customer_id_'.$matches[1].' did not match a customer in this business.',
            ];
        }

        if (empty($normalized['phone_number'])) {
            return ['contact' => null, 'reason' => null, 'note' => 'No phone number was supplied in the IntaSend payload.'];
        }

        $phone_variants = array_unique(array_filter([
            $normalized['phone_number'],
            $this->toLocalPhone($normalized['phone_number']),
            $this->toInternationalPhone($normalized['phone_number']),
        ]));

        $matches = Contact::where('business_id', $business_id)
            ->whereIn('type', ['customer', 'both'])
            ->where(function ($query) use ($phone_variants) {
                $query->whereIn('mobile', $phone_variants)
                    ->orWhereIn('contact_id', $phone_variants)
                    ->orWhereIn('alternate_number', $phone_variants);
            })
            ->limit(2)
            ->get();

        if ($matches->count() == 1) {
            return [
                'contact' => $matches->first(),
                'reason' => 'phone',
                'note' => 'Matched by phone number.',
            ];
        }

        if ($matches->count() > 1) {
            return [
                'contact' => null,
                'reason' => 'phone_multiple',
                'note' => 'Multiple customers matched the incoming phone number; manual review required.',
            ];
        }

        return [
            'contact' => null,
            'reason' => null,
            'note' => 'No customer matched the incoming phone number.',
        ];
    }

    public function verifyWebhookRequest($request, ?IntaSendSetting $setting)
    {
        if (empty($setting) || empty($setting->require_webhook_signature)) {
            return true;
        }

        $secret = (string) $setting->webhook_secret;
        if ($secret === '') {
            return false;
        }

        $provided = (string) ($request->header('X-IntaSend-Signature')
            ?: $request->header('X-Webhook-Signature')
            ?: $request->header('X-Hub-Signature-256')
            ?: $request->input('signature'));

        if ($provided === '') {
            return false;
        }

        $provided = trim($provided);
        $raw_body = $request->getContent();
        $candidates = [
            hash_hmac('sha256', $raw_body, $secret),
            'sha256='.hash_hmac('sha256', $raw_body, $secret),
        ];

        $timestamp = (string) ($request->header('X-IntaSend-Timestamp') ?: $request->header('X-Webhook-Timestamp'));
        if ($timestamp !== '') {
            $candidates[] = hash_hmac('sha256', $timestamp.'.'.$raw_body, $secret);
            $candidates[] = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$raw_body, $secret);
        }

        foreach ($candidates as $candidate) {
            if (hash_equals($candidate, $provided)) {
                return true;
            }
        }

        return false;
    }

    protected function normalizePhone($phone)
    {
        return preg_replace('/[^0-9]/', '', (string) $phone);
    }

    protected function toLocalPhone($phone)
    {
        $phone = $this->normalizePhone($phone);
        if (str_starts_with($phone, '254')) {
            return '0'.substr($phone, 3);
        }

        return $phone;
    }

    protected function toInternationalPhone($phone)
    {
        $phone = $this->normalizePhone($phone);
        if (str_starts_with($phone, '0')) {
            return '254'.substr($phone, 1);
        }

        return $phone;
    }

    protected function normalizeTransactionCode($transaction_code)
    {
        return strtoupper(trim((string) $transaction_code));
    }

    protected function defaultUserId($business_id)
    {
        return User::where('business_id', $business_id)
            ->where('user_type', 'user')
            ->orderBy('id')
            ->value('id');
    }

    protected function customerHasSellDue($contact_id)
    {
        $due = DB::table('transactions')
            ->where('contact_id', $contact_id)
            ->whereIn('type', ['sell', 'opening_balance'])
            ->where(function ($query) {
                $query->where('type', 'opening_balance')
                    ->orWhere(function ($q) {
                        $q->where('type', 'sell')->where('status', 'final');
                    });
            })
            ->where('payment_status', '!=', 'paid')
            ->select(DB::raw('COALESCE(SUM(final_total - (SELECT COALESCE(SUM(IF(tp.is_return = 1, -1 * tp.amount, tp.amount)), 0) FROM transaction_payments as tp WHERE tp.transaction_id = transactions.id)), 0) as due'))
            ->value('due');

        return (float) $due > 0;
    }

    protected function getMpesaAccountId(BusinessLocation $location)
    {
        $accounts = json_decode($location->default_payment_accounts, true);

        return $accounts['custom_pay_1']['account'] ?? null;
    }
}
