<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up()
    {
        $permissions = [
            'intasend.settings',
            'intasend.transactions',
            'daraja.settings',
            'daraja.transactions',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->copyLegacyAccess('intasend.manage', ['intasend.settings', 'intasend.transactions']);
        $this->copyLegacyAccess('daraja.manage', ['daraja.settings', 'daraja.transactions']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down()
    {
        Permission::whereIn('name', [
            'intasend.settings',
            'intasend.transactions',
            'daraja.settings',
            'daraja.transactions',
        ])->where('guard_name', 'web')->delete();
    }

    protected function copyLegacyAccess($legacy_permission, array $new_permissions)
    {
        if (! Permission::where('name', $legacy_permission)->where('guard_name', 'web')->exists()) {
            return;
        }

        Role::permission($legacy_permission)->get()->each(function ($role) use ($new_permissions) {
            $role->givePermissionTo($new_permissions);
        });
    }
};
