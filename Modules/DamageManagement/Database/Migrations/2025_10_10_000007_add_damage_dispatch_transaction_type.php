<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE `transactions` MODIFY COLUMN `type` ENUM('purchase','sell', 'expense', 'stock_adjustment', 'sell_transfer', 'purchase_transfer', 'opening_stock', 'sell_return', 'opening_balance', 'purchase_return', 'payroll', 'expense_refund', 'sales_order', 'purchase_order', 'production_purchase', 'production_sell', 'damage_dispatch') DEFAULT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE `transactions` MODIFY COLUMN `type` ENUM('purchase','sell', 'expense', 'stock_adjustment', 'sell_transfer', 'purchase_transfer', 'opening_stock', 'sell_return', 'opening_balance', 'purchase_return', 'payroll', 'expense_refund', 'sales_order', 'purchase_order', 'production_purchase', 'production_sell') DEFAULT NULL");
    }
};

