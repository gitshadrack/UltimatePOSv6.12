<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->uuid('offline_transaction_uuid')->nullable()->after('invoice_no');
            $table->unique(
                ['business_id', 'offline_transaction_uuid'],
                'transactions_business_offline_uuid_unique'
            );
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique('transactions_business_offline_uuid_unique');
            $table->dropColumn('offline_transaction_uuid');
        });
    }
};
