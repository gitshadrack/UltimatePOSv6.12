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
        if (! Schema::hasColumn('purchase_lines', 'lot_sell_price_inc_tax')) {
            Schema::table('purchase_lines', function (Blueprint $table) {
                $table->decimal('lot_sell_price_inc_tax', 22, 4)
                    ->nullable()
                    ->after('purchase_price_inc_tax');
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
        if (Schema::hasColumn('purchase_lines', 'lot_sell_price_inc_tax')) {
            Schema::table('purchase_lines', function (Blueprint $table) {
                $table->dropColumn('lot_sell_price_inc_tax');
            });
        }
    }
};
