<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('business_locations', 'enable_numeric_login')) {
            Schema::table('business_locations', function (Blueprint $table) {
                $column = $table->boolean('enable_numeric_login')->default(0);

                if (Schema::hasColumn('business_locations', 'enable_mpesa_verification')) {
                    $column->after('enable_mpesa_verification');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('business_locations', 'enable_numeric_login')) {
            Schema::table('business_locations', function (Blueprint $table) {
                $table->dropColumn('enable_numeric_login');
            });
        }
    }
};
