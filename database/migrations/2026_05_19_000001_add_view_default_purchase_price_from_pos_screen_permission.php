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
        Permission::firstOrCreate([
            'name' => 'view_default_purchase_price_from_pos_screen',
            'guard_name' => 'web',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Permission::where('name', 'view_default_purchase_price_from_pos_screen')
            ->where('guard_name', 'web')
            ->delete();
    }
};
