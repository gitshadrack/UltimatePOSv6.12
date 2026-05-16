<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_payments', function (Blueprint $table) {
            $table->enum('mpesa_verification_status', ['pending', 'verified', 'rejected'])
                ->default('pending')
                ->after('transaction_no');
            $table->unsignedInteger('mpesa_verified_by')->nullable()->after('mpesa_verification_status');
            $table->dateTime('mpesa_verified_at')->nullable()->after('mpesa_verified_by');
            $table->text('mpesa_verification_note')->nullable()->after('mpesa_verified_at');

            $table->foreign('mpesa_verified_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });

        DB::table('transaction_payments')
            ->where('method', 'custom_pay_1')
            ->whereNull('mpesa_verification_status')
            ->update(['mpesa_verification_status' => 'pending']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_payments', function (Blueprint $table) {
            $table->dropForeign(['mpesa_verified_by']);
            $table->dropColumn([
                'mpesa_verification_status',
                'mpesa_verified_by',
                'mpesa_verified_at',
                'mpesa_verification_note',
            ]);
        });
    }
};
