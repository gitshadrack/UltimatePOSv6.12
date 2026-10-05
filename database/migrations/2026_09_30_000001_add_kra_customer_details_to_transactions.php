<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('kra_customer_name')->nullable()->after('contact_id');
            $table->string('kra_pin', 11)->nullable()->after('kra_customer_name');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['kra_customer_name', 'kra_pin']);
        });
    }
};
