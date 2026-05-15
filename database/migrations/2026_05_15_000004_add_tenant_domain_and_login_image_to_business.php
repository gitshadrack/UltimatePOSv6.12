<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTenantDomainAndLoginImageToBusiness extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('business', 'tenant_domain')) {
            Schema::table('business', function (Blueprint $table) {
                $table->string('tenant_domain')->nullable()->after('name');
            });
        }

        if (! Schema::hasColumn('business', 'login_image')) {
            Schema::table('business', function (Blueprint $table) {
                $table->string('login_image')->nullable()->after('logo');
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
        if (Schema::hasColumn('business', 'tenant_domain')) {
            Schema::table('business', function (Blueprint $table) {
                $table->dropColumn('tenant_domain');
            });
        }

        if (Schema::hasColumn('business', 'login_image')) {
            Schema::table('business', function (Blueprint $table) {
                $table->dropColumn('login_image');
            });
        }
    }
}
