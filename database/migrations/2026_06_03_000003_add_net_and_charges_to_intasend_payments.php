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
        if (! Schema::hasTable('intasend_payments')) {
            return;
        }

        Schema::table('intasend_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('intasend_payments', 'net_amount')) {
                $table->decimal('net_amount', 22, 4)->nullable()->after('amount');
            }
            if (! Schema::hasColumn('intasend_payments', 'charges')) {
                $table->decimal('charges', 22, 4)->nullable()->after('net_amount');
            }
            if (! Schema::hasColumn('intasend_payments', 'currency')) {
                $table->string('currency', 10)->nullable()->after('charges');
            }
        });

        DB::table('intasend_payments')
            ->select('id', 'amount', 'payload')
            ->orderBy('id')
            ->chunkById(100, function ($payments) {
                foreach ($payments as $payment) {
                    $payload = json_decode($payment->payload ?: '[]', true);
                    if (! is_array($payload)) {
                        $payload = [];
                    }

                    $gross_amount = $payload['value'] ?? $payload['amount'] ?? $payment->amount;
                    $net_amount = $payload['net_amount'] ?? $payment->amount;
                    $charges = $payload['charges'] ?? null;

                    DB::table('intasend_payments')
                        ->where('id', $payment->id)
                        ->update([
                            'amount' => $gross_amount,
                            'net_amount' => $net_amount,
                            'charges' => $charges,
                            'currency' => $payload['currency'] ?? null,
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! Schema::hasTable('intasend_payments')) {
            return;
        }

        Schema::table('intasend_payments', function (Blueprint $table) {
            if (Schema::hasColumn('intasend_payments', 'currency')) {
                $table->dropColumn('currency');
            }
            if (Schema::hasColumn('intasend_payments', 'charges')) {
                $table->dropColumn('charges');
            }
            if (Schema::hasColumn('intasend_payments', 'net_amount')) {
                $table->dropColumn('net_amount');
            }
        });
    }
};
