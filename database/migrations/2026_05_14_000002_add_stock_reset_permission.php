<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Permission::where('name', 'stock_adjustment.reset')->exists()) {
            Permission::create(['name' => 'stock_adjustment.reset']);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Permission::where('name', 'stock_adjustment.reset')->delete();
    }
};
