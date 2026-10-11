<?php

namespace Database\Seeders;

use App\Modules\Auth\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $all = [];
        foreach (config('rbac.permissions') as $module => $actions) {
            foreach ($actions as $action) {
                $all[] = Permission::findOrCreate("{$module}.{$action}", 'web')->name;
            }
        }

        // Niveau 0 = pouvoir maximal.
        $roles = [
            'super-admin' => 0,
            'admin' => 10,
            'superviseur' => 30,
            'intervenant' => 40,
            'employe' => 40,
            'apporteur' => 40,
            'client' => 50,
        ];

        foreach ($roles as $name => $level) {
            Role::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['hierarchy_level' => $level, 'is_system' => true],
            );
        }

        // L'admin reçoit tout sauf la gestion des rôles (réservée au super-admin).
        Role::where('name', 'admin')->first()
            ->syncPermissions(array_diff($all, ['roles.manage']));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
