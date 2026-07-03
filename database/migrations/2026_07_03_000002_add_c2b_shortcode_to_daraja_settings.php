<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('daraja_settings') && ! Schema::hasColumn('daraja_settings', 'c2b_shortcode')) {
            Schema::table('daraja_settings', function (Blueprint $table) {
                $table->string('c2b_shortcode')->nullable()->after('business_shortcode')->index();
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('daraja_settings') && Schema::hasColumn('daraja_settings', 'c2b_shortcode')) {
            Schema::table('daraja_settings', function (Blueprint $table) {
                $table->dropIndex(['c2b_shortcode']);
                $table->dropColumn('c2b_shortcode');
            });
        }
    }
};
