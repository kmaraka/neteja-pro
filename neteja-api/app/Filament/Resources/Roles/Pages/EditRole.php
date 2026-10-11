<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Services\AuditLogger;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->hidden(fn (Role $record): bool => $record->is_system),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return RoleForm::fillPermissions($data, $this->getRecord());
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $permissions = RoleForm::pullPermissions($data);
        $before = $record->permissions->pluck('name')->sort()->values()->all();

        $record->update($data);
        $record->syncPermissions($permissions);

        app(AuditLogger::class)->log('roles.updated', 'auth', $record, [
            'before' => $before,
            'after' => collect($permissions)->sort()->values()->all(),
        ]);

        return $record;
    }
}
