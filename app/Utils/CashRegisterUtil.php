<?php

namespace App\Utils;

use App\CashRegister;
use App\CashRegisterTransaction;
use App\Transaction;
use App\TransactionPayment;
use DB;
use Illuminate\Support\Facades\Schema;

class CashRegisterUtil extends Util
{
    /**
     * Returns number of opened Cash Registers for the
     * current logged in user
     *
     * @return int
     */
    public function countOpenedRegister()
    {
        $user_id = auth()->user()->id;
        $count = CashRegister::where('user_id', $user_id)
                                ->where('status', 'open')
                                ->count();

        return $count;
    }

    /**
     * Adds sell payments to currently opened cash register
     *
     * @param object/int $transaction
     * @param  array  $payments
     * @return bool
     */
    public function addSellPayments($transaction, $payments)
    {
        $user_id = auth()->user()->id;
        $register = CashRegister::where('user_id', $user_id)
                                ->where('status', 'open')
                                ->first();
        $payments_formatted = [];
        foreach ($payments as $payment) {
            $payment_amount = (isset($payment['is_return']) && $payment['is_return'] == 1) ? (-1 * $this->num_uf($payment['amount'])) : $this->num_uf($payment['amount']);
            if ($payment_amount != 0) {
                $type = 'credit';
                if ($transaction->type == 'expense') {
                    $type = 'debit';
                }

                $payments_formatted[] = new CashRegisterTransaction([
                    'amount' => $payment_amount,
                    'pay_method' => $payment['method'],
                    'type' => $type,
                    'transaction_type' => $transaction->type,
                    'transaction_id' => $transaction->id,
                ]);
            }
        }

        if (! empty($payments_formatted)) {
            $register->cash_register_transactions()->saveMany($payments_formatted);
        }

        return true;
    }

    /**
     * Adds sell payments to currently opened cash register
     *
     * @param object/int $transaction
     * @param  array  $payments
     * @return bool
     */
    public function updateSellPayments($status_before, $transaction, $payments)
    {
        $user_id = auth()->user()->id;
        $register = CashRegister::where('user_id', $user_id)
                                ->where('status', 'open')
                                ->first();
        //If draft -> final then add all
        //If final -> draft then refund all
        //If final -> final then update payments
        if ($status_before == 'draft' && $transaction->status == 'final') {
            $this->addSellPayments($transaction, $payments);
        } elseif ($status_before == 'final' && $transaction->status == 'draft') {
            $this->refundSell($transaction);
        } elseif ($status_before == 'final' && $transaction->status == 'final') {
            $prev_payments = CashRegisterTransaction::where('transaction_id', $transaction->id)
                            ->select(
                                DB::raw("SUM(IF(pay_method='cash', IF(type='credit', amount, -1 * amount), 0)) as total_cash"),
                                DB::raw("SUM(IF(pay_method='card', IF(type='credit', amount, -1 * amount), 0)) as total_card"),
                                DB::raw("SUM(IF(pay_method='cheque', IF(type='credit', amount, -1 * amount), 0)) as total_cheque"),
                                DB::raw("SUM(IF(pay_method='bank_transfer', IF(type='credit', amount, -1 * amount), 0)) as total_bank_transfer"),
                                DB::raw("SUM(IF(pay_method='other', IF(type='credit', amount, -1 * amount), 0)) as total_other"),
                                DB::raw("SUM(IF(pay_method='custom_pay_1', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_1"),
                                DB::raw("SUM(IF(pay_method='custom_pay_2', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_2"),
                                DB::raw("SUM(IF(pay_method='custom_pay_3', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_3"),
                                DB::raw("SUM(IF(pay_method='custom_pay_4', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_4"),
                                DB::raw("SUM(IF(pay_method='custom_pay_5', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_5"),
                                DB::raw("SUM(IF(pay_method='custom_pay_6', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_6"),
                                DB::raw("SUM(IF(pay_method='custom_pay_7', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_7"),
                                DB::raw("SUM(IF(pay_method='advance', IF(type='credit', amount, -1 * amount), 0)) as total_advance")
                            )->first();
            if (! empty($prev_payments)) {
                $payment_diffs = [
                    'cash' => $prev_payments->total_cash,
                    'card' => $prev_payments->total_card,
                    'cheque' => $prev_payments->total_cheque,
                    'bank_transfer' => $prev_payments->total_bank_transfer,
                    'other' => $prev_payments->total_other,
                    'custom_pay_1' => $prev_payments->total_custom_pay_1,
                    'custom_pay_2' => $prev_payments->total_custom_pay_2,
                    'custom_pay_3' => $prev_payments->total_custom_pay_3,
                    'custom_pay_4' => $prev_payments->total_custom_pay_4,
                    'custom_pay_5' => $prev_payments->total_custom_pay_5,
                    'custom_pay_6' => $prev_payments->total_custom_pay_6,
                    'custom_pay_7' => $prev_payments->total_custom_pay_7,
                    'advance' => $prev_payments->total_advance,
                ];

                foreach ($payments as $payment) {
                    if (isset($payment['is_return']) && $payment['is_return'] == 1) {
                        $payment_diffs[$payment['method']] += $this->num_uf($payment['amount']);
                    } else {
                        $payment_diffs[$payment['method']] -= $this->num_uf($payment['amount']);
                    }
                }
                $payments_formatted = [];
                foreach ($payment_diffs as $key => $value) {
                    if ($value > 0) {
                        $payments_formatted[] = new CashRegisterTransaction([
                            'amount' => $value,
                            'pay_method' => $key,
                            'type' => 'debit',
                            'transaction_type' => 'refund',
                            'transaction_id' => $transaction->id,
                        ]);
                    } elseif ($value < 0) {
                        $payments_formatted[] = new CashRegisterTransaction([
                            'amount' => -1 * $value,
                            'pay_method' => $key,
                            'type' => 'credit',
                            'transaction_type' => 'sell',
                            'transaction_id' => $transaction->id,
                        ]);
                    }
                }
                if (! empty($payments_formatted)) {
                    $register->cash_register_transactions()->saveMany($payments_formatted);
                }
            }
        }

        return true;
    }

    /**
     * Refunds all payments of a sell
     *
     * @param object/int $transaction
     * @return bool
     */
    public function refundSell($transaction)
    {
        $user_id = auth()->user()->id;
        $register = CashRegister::where('user_id', $user_id)
                                ->where('status', 'open')
                                ->first();

        $total_payment = CashRegisterTransaction::where('transaction_id', $transaction->id)
                            ->select(
                                DB::raw("SUM(IF(pay_method='cash', IF(type='credit', amount, -1 * amount), 0)) as total_cash"),
                                DB::raw("SUM(IF(pay_method='card', IF(type='credit', amount, -1 * amount), 0)) as total_card"),
                                DB::raw("SUM(IF(pay_method='cheque', IF(type='credit', amount, -1 * amount), 0)) as total_cheque"),
                                DB::raw("SUM(IF(pay_method='bank_transfer', IF(type='credit', amount, -1 * amount), 0)) as total_bank_transfer"),
                                DB::raw("SUM(IF(pay_method='other', IF(type='credit', amount, -1 * amount), 0)) as total_other"),
                                DB::raw("SUM(IF(pay_method='custom_pay_1', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_1"),
                                DB::raw("SUM(IF(pay_method='custom_pay_2', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_2"),
                                DB::raw("SUM(IF(pay_method='custom_pay_3', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_3"),
                                DB::raw("SUM(IF(pay_method='custom_pay_4', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_4"),
                                DB::raw("SUM(IF(pay_method='custom_pay_5', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_5"),
                                DB::raw("SUM(IF(pay_method='custom_pay_6', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_6"),
                                DB::raw("SUM(IF(pay_method='custom_pay_7', IF(type='credit', amount, -1 * amount), 0)) as total_custom_pay_7")
                            )->first();
        $refunds = [
            'cash' => $total_payment->total_cash,
            'card' => $total_payment->total_card,
            'cheque' => $total_payment->total_cheque,
            'bank_transfer' => $total_payment->total_bank_transfer,
            'other' => $total_payment->total_other,
            'custom_pay_1' => $total_payment->total_custom_pay_1,
            'custom_pay_2' => $total_payment->total_custom_pay_2,
            'custom_pay_3' => $total_payment->total_custom_pay_3,
            'custom_pay_4' => $total_payment->total_custom_pay_4,
            'custom_pay_5' => $total_payment->total_custom_pay_5,
            'custom_pay_6' => $total_payment->total_custom_pay_6,
            'custom_pay_7' => $total_payment->total_custom_pay_7,
        ];
        $refund_formatted = [];
        foreach ($refunds as $key => $val) {
            if ($val > 0) {
                $refund_formatted[] = new CashRegisterTransaction([
                    'amount' => $val,
                    'pay_method' => $key,
                    'type' => 'debit',
                    'transaction_type' => 'refund',
                    'transaction_id' => $transaction->id,
                ]);
            }
        }

        if (! empty($refund_formatted)) {
            $register->cash_register_transactions()->saveMany($refund_formatted);
        }

        return true;
    }

    /**
     * Retrieves details of given rigister id else currently opened register
     *
     * @param $register_id default null
     * @return object
     */
    public function getRegisterDetails($register_id = null)
    {
        $query = CashRegister::leftjoin(
            'cash_register_transactions as ct',
            'ct.cash_register_id',
            '=',
            'cash_registers.id'
        )
        ->join(
            'users as u',
            'u.id',
            '=',
            'cash_registers.user_id'
        )
        ->leftJoin(
            'business_locations as bl',
            'bl.id',
            '=',
            'cash_registers.location_id'
        );
        if (empty($register_id)) {
            $user_id = auth()->user()->id;
            $query->where('user_id', $user_id)
                ->where('cash_registers.status', 'open');
        } else {
            $query->where('cash_registers.id', $register_id);
        }

        $register_details = $query->select(
            'cash_registers.id',
            'cash_registers.business_id',
            'cash_registers.created_at as open_time',
            'cash_registers.closed_at as closed_at',
            'cash_registers.user_id',
            'cash_registers.status',
            'cash_registers.closing_note',
            'cash_registers.location_id',
            'cash_registers.denominations',
            DB::raw("SUM(IF(transaction_type='initial', amount, 0)) as cash_in_hand"),
            DB::raw("SUM(IF(transaction_type='sell', amount, IF(transaction_type='refund', -1 * amount, 0))) as total_sale"),
            DB::raw("SUM(IF(transaction_type='expense', IF(transaction_type='refund', -1 * amount, amount), 0)) as total_expense"),
            DB::raw("SUM(IF(pay_method='cash', IF(transaction_type='sell', amount, 0), 0)) as total_cash"),
            DB::raw("SUM(IF(pay_method='cash', IF(transaction_type='expense', amount, 0), 0)) as total_cash_expense"),
            DB::raw("SUM(IF(pay_method='cheque', IF(transaction_type='sell', amount, 0), 0)) as total_cheque"),
            DB::raw("SUM(IF(pay_method='cheque', IF(transaction_type='expense', amount, 0), 0)) as total_cheque_expense"),
            DB::raw("SUM(IF(pay_method='card', IF(transaction_type='sell', amount, 0), 0)) as total_card"),
            DB::raw("SUM(IF(pay_method='card', IF(transaction_type='expense', amount, 0), 0)) as total_card_expense"),
            DB::raw("SUM(IF(pay_method='bank_transfer', IF(transaction_type='sell', amount, 0), 0)) as total_bank_transfer"),
            DB::raw("SUM(IF(pay_method='bank_transfer', IF(transaction_type='expense', amount, 0), 0)) as total_bank_transfer_expense"),
            DB::raw("SUM(IF(pay_method='other', IF(transaction_type='sell', amount, 0), 0)) as total_other"),
            DB::raw("SUM(IF(pay_method='other', IF(transaction_type='expense', amount, 0), 0)) as total_other_expense"),
            DB::raw("SUM(IF(pay_method='advance', IF(transaction_type='sell', amount, 0), 0)) as total_advance"),
            DB::raw("SUM(IF(pay_method='advance', IF(transaction_type='expense', amount, 0), 0)) as total_advance_expense"),
            DB::raw("0 as total_custom_pay_1"),
            DB::raw("0 as total_mpesa"),
            DB::raw("0 as verified_mpesa"),
            DB::raw("0 as pending_mpesa"),
            DB::raw("0 as rejected_mpesa"),
            DB::raw("SUM(IF(pay_method='custom_pay_2', IF(transaction_type='sell', amount, 0), 0)) as total_custom_pay_2"),
            DB::raw("SUM(IF(pay_method='custom_pay_3', IF(transaction_type='sell', amount, 0), 0)) as total_custom_pay_3"),
            DB::raw("SUM(IF(pay_method='custom_pay_4', IF(transaction_type='sell', amount, 0), 0)) as total_custom_pay_4"),
            DB::raw("SUM(IF(pay_method='custom_pay_5', IF(transaction_type='sell', amount, 0), 0)) as total_custom_pay_5"),
            DB::raw("SUM(IF(pay_method='custom_pay_6', IF(transaction_type='sell', amount, 0), 0)) as total_custom_pay_6"),
            DB::raw("SUM(IF(pay_method='custom_pay_7', IF(transaction_type='sell', amount, 0), 0)) as total_custom_pay_7"),
            DB::raw("SUM(IF(pay_method='custom_pay_1', IF(transaction_type='expense', amount, 0), 0)) as total_custom_pay_1_expense"),
            DB::raw("SUM(IF(pay_method='custom_pay_2', IF(transaction_type='expense', amount, 0), 0)) as total_custom_pay_2_expense"),
            DB::raw("SUM(IF(pay_method='custom_pay_3', IF(transaction_type='expense', amount, 0), 0)) as total_custom_pay_3_expense"),
            DB::raw("SUM(IF(pay_method='custom_pay_4', IF(transaction_type='expense', amount, 0), 0)) as total_custom_pay_4_expense"),
            DB::raw("SUM(IF(pay_method='custom_pay_5', IF(transaction_type='expense', amount, 0), 0)) as total_custom_pay_5_expense"),
            DB::raw("SUM(IF(pay_method='custom_pay_6', IF(transaction_type='expense', amount, 0), 0)) as total_custom_pay_6_expense"),
            DB::raw("SUM(IF(pay_method='custom_pay_7', IF(transaction_type='expense', amount, 0), 0)) as total_custom_pay_7_expense"),
            DB::raw("SUM(IF(transaction_type='refund', amount, 0)) as total_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='cash', amount, 0), 0)) as total_cash_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='cheque', amount, 0), 0)) as total_cheque_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='card', amount, 0), 0)) as total_card_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='bank_transfer', amount, 0), 0)) as total_bank_transfer_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='other', amount, 0), 0)) as total_other_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='advance', amount, 0), 0)) as total_advance_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='custom_pay_1', amount, 0), 0)) as total_custom_pay_1_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='custom_pay_2', amount, 0), 0)) as total_custom_pay_2_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='custom_pay_3', amount, 0), 0)) as total_custom_pay_3_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='custom_pay_4', amount, 0), 0)) as total_custom_pay_4_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='custom_pay_5', amount, 0), 0)) as total_custom_pay_5_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='custom_pay_6', amount, 0), 0)) as total_custom_pay_6_refund"),
            DB::raw("SUM(IF(transaction_type='refund', IF(pay_method='custom_pay_7', amount, 0), 0)) as total_custom_pay_7_refund"),
            DB::raw("SUM(IF(pay_method='cheque', 1, 0)) as total_cheques"),
            DB::raw("SUM(IF(pay_method='card', 1, 0)) as total_card_slips"),
            DB::raw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as user_name"),
            'u.email',
            'bl.name as location_name'
        )->first();

        $this->setRegisterMpesaVerificationTotals($register_details);

        return $register_details;
    }

    /**
     * Get the transaction details for a particular register
     *
     * @param $user_id int
     * @param $open_time datetime
     * @param $close_time datetime
     * @return array
     */
    public function getRegisterTransactionDetails($user_id, $open_time, $close_time, $is_types_of_service_enabled = false)
    {
        $product_details_by_brand = Transaction::where('transactions.created_by', $user_id)
                ->whereBetween('transactions.created_at', [$open_time, $close_time])
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->where('transactions.is_direct_sale', 0)
                ->join('transaction_sell_lines AS TSL', 'transactions.id', '=', 'TSL.transaction_id')
                ->join('products AS P', 'TSL.product_id', '=', 'P.id')
                ->where('TSL.children_type', '!=', 'combo')
                ->leftjoin('brands AS B', 'P.brand_id', '=', 'B.id')
                ->groupBy('B.id')
                ->select(
                    'B.name as brand_name',
                    DB::raw('SUM(TSL.quantity) as total_quantity'),
                    DB::raw('SUM(TSL.unit_price_inc_tax*TSL.quantity) as total_amount')
                )
                ->orderByRaw('CASE WHEN brand_name IS NULL THEN 2 ELSE 1 END, brand_name')
                ->get();

        $product_details = Transaction::where('transactions.created_by', $user_id)
                ->whereBetween('transactions.created_at', [$open_time, $close_time])
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->where('transactions.is_direct_sale', 0)
                ->join('transaction_sell_lines AS TSL', 'transactions.id', '=', 'TSL.transaction_id')
                ->join('variations AS v', 'TSL.variation_id', '=', 'v.id')
                ->join('product_variations AS pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('products AS p', 'v.product_id', '=', 'p.id')
                ->where('TSL.children_type', '!=', 'combo')
                ->groupBy('v.id')
                ->select(
                    'p.name as product_name',
                    'p.type as product_type',
                    'v.name as variation_name',
                    'pv.name as product_variation_name',
                    'v.sub_sku as sku',
                    DB::raw('SUM(TSL.quantity) as total_quantity'),
                    DB::raw('SUM(TSL.unit_price_inc_tax*TSL.quantity) as total_amount')
                )
                ->get();

        //If types of service
        $types_of_service_details = null;
        if ($is_types_of_service_enabled) {
            $types_of_service_details = Transaction::where('transactions.created_by', $user_id)
                ->whereBetween('transaction_date', [$open_time, $close_time])
                ->where('transactions.is_direct_sale', 0)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->leftjoin('types_of_services AS tos', 'tos.id', '=', 'transactions.types_of_service_id')
                ->groupBy('tos.id')
                ->select(
                    'tos.name as types_of_service_name',
                    DB::raw('SUM(final_total) as total_sales')
                )
                ->orderBy('total_sales', 'desc')
                ->get();
        }

        $transaction_details = Transaction::where('transactions.created_by', $user_id)
                ->whereBetween('transactions.created_at', [$open_time, $close_time])
                ->where('transactions.type', 'sell')
                ->where('transactions.is_direct_sale', 0)
                ->where('transactions.status', 'final')
                ->select(
                    DB::raw('SUM(tax_amount) as total_tax'),
                    DB::raw('SUM(IF(discount_type = "percentage", total_before_tax*discount_amount/100, discount_amount)) as total_discount'),
                    DB::raw('SUM(final_total) as total_sales'),
                    DB::raw('SUM(shipping_charges) as total_shipping_charges')
                )
                ->first();

        return ['product_details_by_brand' => $product_details_by_brand,
            'transaction_details' => $transaction_details,
            'types_of_service_details' => $types_of_service_details,
            'product_details' => $product_details,
        ];
    }

    /**
     * Returns M-PESA verification totals for a register window.
     *
     * @param  object  $register_details
     * @return object
     */
    public function getRegisterMpesaVerificationSummary($register_details)
    {
        if (! isset($register_details->total_mpesa)
            || ! isset($register_details->verified_mpesa)
            || ! isset($register_details->pending_mpesa)
            || ! isset($register_details->rejected_mpesa)) {
            $this->setRegisterMpesaVerificationTotals($register_details);
        }

        return (object) [
            'total' => $register_details->total_mpesa,
            'verified' => $register_details->verified_mpesa,
            'pending' => $register_details->pending_mpesa,
            'rejected' => $register_details->rejected_mpesa,
        ];
    }

    /**
     * Returns the M-PESA audit trail for a register window.
     *
     * @param  object  $register_details
     * @return \Illuminate\Support\Collection
     */
    public function getRegisterMpesaAuditTrail($register_details)
    {
        if (empty($register_details)) {
            return collect();
        }

        $close_time = ! empty($register_details->closed_at)
            ? $register_details->closed_at
            : \Carbon::now()->toDateTimeString();

        return TransactionPayment::join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->leftJoin('users as verifier', 'transaction_payments.mpesa_verified_by', '=', 'verifier.id')
            ->where('t.business_id', $register_details->business_id)
            ->where('t.created_by', $register_details->user_id)
            ->whereBetween('t.created_at', [$register_details->open_time, $close_time])
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('transaction_payments.method', 'custom_pay_1')
            ->select(
                'transaction_payments.id',
                'transaction_payments.amount',
                'transaction_payments.is_return',
                'transaction_payments.transaction_no',
                'transaction_payments.note',
                'transaction_payments.paid_on',
                'transaction_payments.mpesa_verification_status',
                'transaction_payments.mpesa_verified_at',
                'transaction_payments.mpesa_verification_note',
                't.invoice_no',
                't.transaction_date',
                'c.name as customer',
                'c.supplier_business_name',
                DB::raw("CONCAT(COALESCE(verifier.surname, ''), ' ', COALESCE(verifier.first_name, ''), ' ', COALESCE(verifier.last_name, '')) as verifier_name")
            )
            ->orderBy('transaction_payments.paid_on')
            ->orderBy('transaction_payments.id')
            ->get();
    }

    /**
     * Adds verified/pending/rejected M-PESA totals to register details.
     *
     * @param  object|null  $register_details
     * @return void
     */
    private function setRegisterMpesaVerificationTotals($register_details)
    {
        if (empty($register_details)) {
            return;
        }

        if (! Schema::hasColumn('business_locations', 'enable_mpesa_verification')) {
            $mpesa_totals = $this->getStandardPlainTotals($register_details->id);
            $register_details->total_mpesa = $mpesa_totals->total ?? 0;
            $register_details->verified_mpesa = $mpesa_totals->verified ?? 0;
            $register_details->pending_mpesa = $mpesa_totals->pending ?? 0;
            $register_details->rejected_mpesa = $mpesa_totals->rejected ?? 0;
            $register_details->total_custom_pay_1 = $register_details->total_mpesa;

            return;
        }

        $location = DB::table('business_locations')
            ->join('cash_registers', 'business_locations.id', '=', 'cash_registers.location_id')
            ->where('cash_registers.id', $register_details->id)
            ->select('business_locations.enable_mpesa_verification')
            ->first();

        if (! empty($location->enable_mpesa_verification)) {
            $mpesa_totals = $this->getAuditedMpesaTotals($register_details->id);
        } else {
            $mpesa_totals = $this->getStandardPlainTotals($register_details->id);
        }

        $register_details->total_mpesa = $mpesa_totals->total ?? 0;
        $register_details->verified_mpesa = $mpesa_totals->verified ?? 0;
        $register_details->pending_mpesa = $mpesa_totals->pending ?? 0;
        $register_details->rejected_mpesa = $mpesa_totals->rejected ?? 0;

        $register_details->total_custom_pay_1 = $register_details->total_mpesa;
    }

    /**
     * Returns M-PESA totals with verification status breakdown.
     *
     * @param  int  $register_id
     * @return object
     */
    private function getAuditedMpesaTotals($register_id)
    {
        $register = CashRegister::find($register_id);

        if (empty($register)) {
            return $this->emptyMpesaTotals();
        }

        $close_time = ! empty($register->closed_at)
            ? $register->closed_at
            : \Carbon::now()->toDateTimeString();

        return TransactionPayment::join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
            ->where('t.business_id', $register->business_id)
            ->where('t.created_by', $register->user_id)
            ->whereBetween('t.created_at', [$register->created_at, $close_time])
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('transaction_payments.method', 'custom_pay_1')
            ->select(
                DB::raw("COALESCE(SUM(CASE WHEN transaction_payments.method = 'custom_pay_1' THEN CASE WHEN transaction_payments.is_return = 1 THEN -1 * transaction_payments.amount ELSE transaction_payments.amount END ELSE 0 END), 0) as total"),
                DB::raw("COALESCE(SUM(CASE WHEN transaction_payments.mpesa_verification_status = 'verified' THEN CASE WHEN transaction_payments.is_return = 1 THEN -1 * transaction_payments.amount ELSE transaction_payments.amount END ELSE 0 END), 0) as verified"),
                DB::raw("COALESCE(SUM(CASE WHEN transaction_payments.mpesa_verification_status = 'pending' OR transaction_payments.mpesa_verification_status IS NULL THEN CASE WHEN transaction_payments.is_return = 1 THEN -1 * transaction_payments.amount ELSE transaction_payments.amount END ELSE 0 END), 0) as pending"),
                DB::raw("COALESCE(SUM(CASE WHEN transaction_payments.mpesa_verification_status = 'rejected' THEN CASE WHEN transaction_payments.is_return = 1 THEN -1 * transaction_payments.amount ELSE transaction_payments.amount END ELSE 0 END), 0) as rejected")
            )
            ->first();
    }

    /**
     * Returns plain M-PESA totals for locations without manual verification.
     *
     * @param  int  $register_id
     * @return object
     */
    private function getStandardPlainTotals($register_id)
    {
        $total = CashRegisterTransaction::where('cash_register_id', $register_id)
            ->where('pay_method', 'custom_pay_1')
            ->select(DB::raw("COALESCE(SUM(CASE WHEN transaction_type = 'sell' THEN amount WHEN transaction_type = 'refund' THEN -1 * amount ELSE 0 END), 0) as total"))
            ->value('total');

        return (object) [
            'total' => $total ?? 0,
            'verified' => $total ?? 0,
            'pending' => 0,
            'rejected' => 0,
        ];
    }

    /**
     * Empty M-PESA totals fallback.
     *
     * @return object
     */
    private function emptyMpesaTotals()
    {
        return (object) [
            'total' => 0,
            'verified' => 0,
            'pending' => 0,
            'rejected' => 0,
        ];
    }

    /**
     * Retrieves the currently opened cash register for the user
     *
     * @param $int user_id
     * @return obj
     */
    public function getCurrentCashRegister($user_id)
    {
        $register = CashRegister::where('user_id', $user_id)
                                ->where('status', 'open')
                                ->first();

        return $register;
    }
}
