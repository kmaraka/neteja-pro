<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use App\Modules\Auth\Models\Role;
use App\Modules\Core\Support\Phone;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identité')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nom complet')
                        ->required()
                        ->maxLength(100),

                    TextInput::make('phone')
                        ->label('Téléphone')
                        ->tel()
                        ->placeholder('07 00 00 00 00')
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->helperText('Le mot de passe temporaire sera envoyé par SMS à ce numéro.')
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Phone::normalize($state) : null)
                        ->rules([
                            // L'unicité porte sur le numéro NORMALISÉ (+225...), pas sur la saisie brute.
                            fn (?User $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                                if (blank($value)) {
                                    return;
                                }

                                $exists = User::query()
                                    ->where('phone', Phone::normalize($value))
                                    ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                                    ->exists();

                                if ($exists) {
                                    $fail('Ce numéro est déjà utilisé.');
                                }
                            },
                        ]),

                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(190)
                        ->unique(ignoreRecord: true)
                        ->required(fn (Get $get): bool => collect($get('roles') ?? [])
                            ->intersect(User::PANEL_ROLES)->isNotEmpty())
                        ->helperText('Obligatoire pour les rôles qui accèdent au back-office (admin, superviseur).')
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Str::lower(trim($state)) : null),
                ]),

            Section::make('Accès')
                ->schema([
                    Select::make('roles')
                        ->label('Rôles')
                        ->multiple()
                        ->live()
                        ->required(fn (string $operation): bool => $operation === 'create')
                        // Seuls les rôles de niveau inférieur au sien sont proposés.
                        ->options(fn (): array => Role::query()
                            ->where('hierarchy_level', '>', auth()->user()->hierarchyLevel())
                            ->orderBy('hierarchy_level')
                            ->pluck('name', 'name')
                            ->all())
                        // RG-AUTH-01 : on ne modifie pas son propre rôle.
                        ->disabled(fn (?User $record): bool => (bool) $record?->is(auth()->user()))
                        ->helperText('Un compte ne peut pas modifier son propre rôle.'),
                ]),
        ]);
    }
}
