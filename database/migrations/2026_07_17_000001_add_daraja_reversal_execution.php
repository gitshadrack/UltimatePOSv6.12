<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('daraja_settings')) {
            $add_initiator_name = ! Schema::hasColumn('daraja_settings', 'initiator_name');
            $add_security_credential = ! Schema::hasColumn('daraja_settings', 'security_credential');
            if ($add_initiator_name || $add_security_credential) {
                Schema::table('daraja_settings', function (Blueprint $table) use ($add_initiator_name, $add_security_credential) {
                    if ($add_initiator_name) {
                    $table->string('initiator_name')->nullable()->after('passkey');
                    }
                    if ($add_security_credential) {
                    $table->text('security_credential')->nullable()->after('initiator_name');
                    }
                });
            }
        }

        if (Schema::hasTable('daraja_payments')) {
            $add_requested_by = ! Schema::hasColumn('daraja_payments', 'reversal_requested_by');
            $add_reversal_payment = ! Schema::hasColumn('daraja_payments', 'reversal_transaction_payment_id');
            $add_response = ! Schema::hasColumn('daraja_payments', 'reversal_response');
            if ($add_requested_by || $add_reversal_payment || $add_response) {
                Schema::table('daraja_payments', function (Blueprint $table) use ($add_requested_by, $add_reversal_payment, $add_response) {
                    if ($add_requested_by) {
                    $table->unsignedInteger('reversal_requested_by')->nullable()->index()->after('reversal_request_id');
                    }
                    if ($add_reversal_payment) {
                    $table->unsignedInteger('reversal_transaction_payment_id')->nullable()->index()->after('reversal_requested_by');
                    }
                    if ($add_response) {
                    $table->json('reversal_response')->nullable()->after('reversal_note');
                    }
                });
            }
        }

        Permission::firstOrCreate(['name' => 'mpesa.reversal', 'guard_name' => 'web']);
    }

    public function down()
    {
        Permission::where('name', 'mpesa.reversal')->where('guard_name', 'web')->delete();

        if (Schema::hasTable('daraja_payments')) {
            $columns = array_values(array_filter(
                ['reversal_requested_by', 'reversal_transaction_payment_id', 'reversal_response'],
                fn ($column) => Schema::hasColumn('daraja_payments', $column)
            ));
            if (! empty($columns)) {
                Schema::table('daraja_payments', fn (Blueprint $table) => $table->dropColumn($columns));
            }
        }

        if (Schema::hasTable('daraja_settings')) {
            $columns = array_values(array_filter(
                ['initiator_name', 'security_credential'],
                fn ($column) => Schema::hasColumn('daraja_settings', $column)
            ));
            if (! empty($columns)) {
                Schema::table('daraja_settings', fn (Blueprint $table) => $table->dropColumn($columns));
            }
        }
    }
};
