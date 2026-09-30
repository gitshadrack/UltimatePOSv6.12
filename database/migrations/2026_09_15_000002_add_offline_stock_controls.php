<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('offline_safety_stock', 22, 4)->default(0)->after('alert_quantity');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dateTime('offline_created_at')->nullable()->after('offline_transaction_uuid');
            $table->string('offline_sync_status', 30)->nullable()->after('offline_created_at');
            $table->text('offline_sync_note')->nullable()->after('offline_sync_status');
            $table->index(['business_id', 'offline_sync_status'], 'transactions_offline_sync_review_index');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_offline_sync_review_index');
            $table->dropColumn(['offline_created_at', 'offline_sync_status', 'offline_sync_note']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('offline_safety_stock');
        });
    }
};
