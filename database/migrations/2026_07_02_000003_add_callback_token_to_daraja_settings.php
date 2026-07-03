<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('daraja_settings', 'callback_token')) {
            Schema::table('daraja_settings', function (Blueprint $table) {
                $table->string('callback_token', 80)->nullable()->unique()->after('account_reference');
            });
        }

        DB::table('daraja_settings')->whereNull('callback_token')->orderBy('id')->get()->each(function ($setting) {
            DB::table('daraja_settings')->where('id', $setting->id)->update(['callback_token' => Str::random(48)]);
        });
    }

    public function down()
    {
        if (Schema::hasColumn('daraja_settings', 'callback_token')) {
            Schema::table('daraja_settings', function (Blueprint $table) {
                $table->dropUnique(['callback_token']);
                $table->dropColumn('callback_token');
            });
        }
    }
};
