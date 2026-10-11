<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Modules\Auth\Services\RoleAssignmentService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    // Pas de bouton "Supprimer" : on anonymise depuis la liste (droit à l'oubli).
    protected function getHeaderActions(): array
    {
        return [];
    }

    /** Pré-remplit le champ "roles" (ce n'est pas une colonne de la table users). */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['roles'] = $this->getRecord()->getRoleNames()->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Le champ est absent quand il est désactivé (modification de son propre compte).
        $roles = Arr::pull($data, 'roles');

        $record->forceFill($data)->save(); // $data ne contient que les champs du formulaire

        if ($roles !== null) {
            $current = $record->getRoleNames()->sort()->values()->all();
            $wanted = collect($roles)->sort()->values()->all();

            if ($current !== $wanted) {
                // Applique la hiérarchie, RG-AUTH-01 et le journal d'audit.
                app(RoleAssignmentService::class)->assign(auth()->user(), $record, $roles);
            }
        }

        return $record;
    }
}
