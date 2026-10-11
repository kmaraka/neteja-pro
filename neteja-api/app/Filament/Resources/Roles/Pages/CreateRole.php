<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Services\AuditLogger;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $permissions = RoleForm::pullPermissions($data);

        $role = Role::create($data + ['guard_name' => 'web']);
        $role->syncPermissions($permissions);

        app(AuditLogger::class)->log('roles.created', 'auth', $role, ['permissions' => $permissions]);

        return $role;
    }
}
