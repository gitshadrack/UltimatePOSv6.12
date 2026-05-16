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
        if (! Schema::hasColumn('business_locations', 'enable_mpesa_verification')) {
            Schema::table('business_locations', function (Blueprint $table) {
                $table->boolean('enable_mpesa_verification')->default(0)->after('name');
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
        if (Schema::hasColumn('business_locations', 'enable_mpesa_verification')) {
            Schema::table('business_locations', function (Blueprint $table) {
                $table->dropColumn('enable_mpesa_verification');
            });
        }
    }
};
