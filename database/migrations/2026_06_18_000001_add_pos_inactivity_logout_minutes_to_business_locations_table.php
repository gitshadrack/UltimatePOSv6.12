<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('business_locations', 'pos_inactivity_logout_minutes')) {
            Schema::table('business_locations', function (Blueprint $table) {
                $column = $table->unsignedSmallInteger('pos_inactivity_logout_minutes')->default(0);

                if (Schema::hasColumn('business_locations', 'enable_numeric_login')) {
                    $column->after('enable_numeric_login');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('business_locations', 'pos_inactivity_logout_minutes')) {
            Schema::table('business_locations', function (Blueprint $table) {
                $table->dropColumn('pos_inactivity_logout_minutes');
            });
        }
    }
};
