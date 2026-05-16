<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('buyer_pin')->nullable()->after('contact_id');
            $table->enum('etims_status', ['pending', 'submitted', 'accepted', 'failed', 'cancelled'])
                ->default('pending')
                ->after('buyer_pin');
            $table->string('etims_invoice_no')->nullable()->after('etims_status');
            $table->string('etims_control_code')->nullable()->after('etims_invoice_no');
            $table->text('etims_qr_code')->nullable()->after('etims_control_code');
            $table->dateTime('etims_submitted_at')->nullable()->after('etims_qr_code');
            $table->longText('etims_response')->nullable()->after('etims_submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'buyer_pin',
                'etims_status',
                'etims_invoice_no',
                'etims_control_code',
                'etims_qr_code',
                'etims_submitted_at',
                'etims_response',
            ]);
        });
    }
};
