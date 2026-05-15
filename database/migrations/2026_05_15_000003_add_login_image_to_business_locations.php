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
        if (! Schema::hasColumn('business_locations', 'login_image')) {
            Schema::table('business_locations', function (Blueprint $table) {
                $table->string('login_image')->nullable()->after('website');
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
        if (Schema::hasColumn('business_locations', 'login_image')) {
            Schema::table('business_locations', function (Blueprint $table) {
                $table->dropColumn('login_image');
            });
        }
    }
};
