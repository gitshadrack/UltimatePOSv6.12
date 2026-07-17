<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        foreach (['daraja_payments', 'intasend_payments'] as $table_name) {
            if (Schema::hasTable($table_name) && ! Schema::hasColumn($table_name, 'reversal_status')) {
                Schema::table($table_name, function (Blueprint $table) {
                    $table->enum('reversal_status', ['none', 'requested', 'pending', 'successful', 'failed'])
                        ->default('none')->index()->after('status');
                    $table->string('reversal_request_id')->nullable()->index()->after('reversal_status');
                    $table->text('reversal_note')->nullable()->after('reversal_request_id');
                    $table->dateTime('reversal_requested_at')->nullable()->after('reversal_note');
                    $table->dateTime('reversed_at')->nullable()->after('reversal_requested_at');
                });
            }
        }
    }

    public function down()
    {
        foreach (['daraja_payments', 'intasend_payments'] as $table_name) {
            if (Schema::hasTable($table_name) && Schema::hasColumn($table_name, 'reversal_status')) {
                Schema::table($table_name, function (Blueprint $table) {
                    $table->dropColumn([
                        'reversal_status',
                        'reversal_request_id',
                        'reversal_note',
                        'reversal_requested_at',
                        'reversed_at',
                    ]);
                });
            }
        }
    }
};
