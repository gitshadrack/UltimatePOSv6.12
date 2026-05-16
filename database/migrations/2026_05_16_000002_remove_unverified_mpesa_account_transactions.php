<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('account_transactions')
            ->whereNull('deleted_at')
            ->whereIn('transaction_payment_id', function ($query) {
                $query->select('id')
                    ->from('transaction_payments')
                    ->where('method', 'custom_pay_1')
                    ->where(function ($q) {
                        $q->whereNull('mpesa_verification_status')
                            ->orWhere('mpesa_verification_status', '!=', 'verified');
                    });
            })
            ->update([
                'deleted_at' => DB::raw('NOW()'),
                'updated_at' => DB::raw('NOW()'),
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
