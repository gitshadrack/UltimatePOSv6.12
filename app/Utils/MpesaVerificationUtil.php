<?php

namespace App\Utils;

use App\TransactionPayment;
use Illuminate\Support\Facades\Schema;

class MpesaVerificationUtil
{
    public function hasCompletePayment($business_id, $transaction_code, $amount = null)
    {
        if (Schema::hasTable('intasend_payments')
            && (new IntaSendUtil())->hasCompletePayment($business_id, $transaction_code, $amount)) {
            return true;
        }

        return Schema::hasTable('daraja_payments')
            && (new DarajaUtil())->hasCompletePayment($business_id, $transaction_code, $amount);
    }

    public function linkPosTransactionPayment(TransactionPayment $payment, $transaction = null, $user_id = null)
    {
        if (Schema::hasTable('intasend_payments')) {
            $linked = (new IntaSendUtil())->linkPosTransactionPayment($payment, $transaction, $user_id);
            if (! empty($linked)) {
                return $linked;
            }
        }

        if (Schema::hasTable('daraja_payments')) {
            return (new DarajaUtil())->linkPosTransactionPayment($payment, $transaction, $user_id);
        }

        return null;
    }
}
