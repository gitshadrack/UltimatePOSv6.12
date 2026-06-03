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
        if (Schema::hasTable('intasend_settings')) {
            Schema::table('intasend_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('intasend_settings', 'webhook_secret')) {
                    $table->string('webhook_secret')->nullable()->after('intasend_secret_key');
                }
                if (! Schema::hasColumn('intasend_settings', 'require_webhook_signature')) {
                    $table->boolean('require_webhook_signature')->default(0)->after('webhook_secret');
                }
            });
        }

        if (Schema::hasTable('intasend_payments')) {
            Schema::table('intasend_payments', function (Blueprint $table) {
                if (! Schema::hasColumn('intasend_payments', 'match_reason')) {
                    $table->string('match_reason')->nullable()->after('reconciliation_status')->index();
                }
                if (! Schema::hasColumn('intasend_payments', 'match_note')) {
                    $table->text('match_note')->nullable()->after('match_reason');
                }
                if (! Schema::hasColumn('intasend_payments', 'auto_attached')) {
                    $table->boolean('auto_attached')->default(false)->after('match_note')->index();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('intasend_payments')) {
            Schema::table('intasend_payments', function (Blueprint $table) {
                if (Schema::hasColumn('intasend_payments', 'auto_attached')) {
                    $table->dropColumn('auto_attached');
                }
                if (Schema::hasColumn('intasend_payments', 'match_note')) {
                    $table->dropColumn('match_note');
                }
                if (Schema::hasColumn('intasend_payments', 'match_reason')) {
                    $table->dropColumn('match_reason');
                }
            });
        }

        if (Schema::hasTable('intasend_settings')) {
            Schema::table('intasend_settings', function (Blueprint $table) {
                if (Schema::hasColumn('intasend_settings', 'require_webhook_signature')) {
                    $table->dropColumn('require_webhook_signature');
                }
                if (Schema::hasColumn('intasend_settings', 'webhook_secret')) {
                    $table->dropColumn('webhook_secret');
                }
            });
        }
    }
};
