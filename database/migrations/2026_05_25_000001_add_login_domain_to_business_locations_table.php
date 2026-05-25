<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('business_locations', 'login_domain')) {
            Schema::table('business_locations', function (Blueprint $table) {
                $table->string('login_domain')->nullable()->after('location_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('business_locations', 'login_domain')) {
            Schema::table('business_locations', function (Blueprint $table) {
                $table->dropColumn('login_domain');
            });
        }
    }
};
