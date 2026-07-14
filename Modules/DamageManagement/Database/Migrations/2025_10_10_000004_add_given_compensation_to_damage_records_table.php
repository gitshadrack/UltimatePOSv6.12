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
        Schema::table('damage_records', function (Blueprint $table) {
            $table->decimal('given_compensation', 22, 4)->default(0)->after('expected_compensation');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('damage_records', function (Blueprint $table) {
            $table->dropColumn('given_compensation');
        });
    }
};

