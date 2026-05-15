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
        if (! Permission::where('name', 'view_all_cash_register')->exists()) {
            Permission::create(['name' => 'view_all_cash_register']);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Permission::where('name', 'view_all_cash_register')->delete();
    }
};
