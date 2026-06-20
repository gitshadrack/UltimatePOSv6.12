<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('business_locations', 'show_customer_balance_on_invoice')) {
            Schema::table('business_locations', function (Blueprint $table) {
                $column = $table->boolean('show_customer_balance_on_invoice')->default(0);

                if (Schema::hasColumn('business_locations', 'pos_inactivity_logout_minutes')) {
                    $column->after('pos_inactivity_logout_minutes');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('business_locations', 'show_customer_balance_on_invoice')) {
            Schema::table('business_locations', function (Blueprint $table) {
                $table->dropColumn('show_customer_balance_on_invoice');
            });
        }
    }
};
