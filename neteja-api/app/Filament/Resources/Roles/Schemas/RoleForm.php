<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Modules\Auth\Models\Role;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        // Une section par module : "Utilisateurs", "Rôles et permissions", ...
        $permissionSections = [];

        foreach (config('rbac.permissions') as $module => $actions) {
            $options = collect($actions)
                ->mapWithKeys(fn (string $action): array => [
                    "{$module}.{$action}" => config("rbac.action_labels.{$action}", $action),
                ])
                ->all();

            $permissionSections[] = Section::make(config("rbac.module_labels.{$module}", $module))
                ->collapsible()
                ->schema([
                    CheckboxList::make(self::field($module))->hiddenLabel()->options($options)->columns(4),
                ]);
        }

        return $schema->components([
            Section::make('Rôle')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nom technique')
                        ->required()
                        ->maxLength(50)
                        ->regex('/^[a-z0-9_-]+$/')
                        ->unique(ignoreRecord: true)
                        ->helperText('Minuscules, chiffres, tirets. Non modifiable pour un rôle système.')
                        ->disabled(fn (?Role $record): bool => (bool) $record?->is_system),

                    TextInput::make('hierarchy_level')
                        ->label('Niveau hiérarchique')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(1000)
                        ->helperText('Plus le nombre est petit, plus le rôle a de pouvoir. Non modifiable pour un rôle système.')
                        ->disabled(fn (?Role $record): bool => (bool) $record?->is_system),

                    Select::make('parent_role_id')
                        ->label('Rôle parent')
                        ->options(fn (?Role $record): array => Role::query()
                            ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                            ->orderBy('hierarchy_level')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->helperText("Informatif pour l'instant : l'héritage de permissions sera décidé avec le cahier des charges."),
                ]),

            Section::make('Permissions')
                ->description('Cochez ce que ce rôle a le droit de faire. Le super-admin a tous les droits, quelles que soient les cases.')
                ->schema($permissionSections),
        ]);
    }

    /** Nom du champ de formulaire d'un module (ce ne sont pas des colonnes de la table roles). */
    public static function field(string $module): string
    {
        return "permissions_{$module}";
    }

    /** Retire les champs de permissions de $data et renvoie la liste à synchroniser. */
    public static function pullPermissions(array &$data): array
    {
        $permissions = [];

        foreach (array_keys(config('rbac.permissions')) as $module) {
            $permissions = array_merge($permissions, Arr::pull($data, self::field($module), []));
        }

        return $permissions;
    }

    /** Coche les cases correspondant aux permissions actuelles du rôle. */
    public static function fillPermissions(array $data, Role $role): array
    {
        $granted = $role->permissions->pluck('name');

        foreach (array_keys(config('rbac.permissions')) as $module) {
            $data[self::field($module)] = $granted
                ->filter(fn (string $name): bool => str_starts_with($name, "{$module}."))
                ->values()
                ->all();
        }

        return $data;
    }
}
