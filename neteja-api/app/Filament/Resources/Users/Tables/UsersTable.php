<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Services\AuditLogger;
use App\Modules\Auth\Services\InternalAccountService;
use App\Modules\Compliance\Services\AccountErasureService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('phone')->label('Téléphone')->searchable()->placeholder('—'),
                TextColumn::make('email')->label('Email')->searchable()->placeholder('—'),
                TextColumn::make('roles.name')->label('Rôles')->badge(),
                IconColumn::make('must_change_password')->label('Mot de passe temporaire')->boolean(),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Rôle')
                    ->options(fn (): array => Role::query()->orderBy('hierarchy_level')->pluck('name', 'name')->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->role($data['value'])
                        : $query),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('resetPassword')
                    ->label('Réinitialiser le mot de passe')
                    ->icon(Heroicon::OutlinedKey)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Un nouveau mot de passe temporaire sera envoyé par SMS et toutes les sessions du compte seront fermées.')
                    ->visible(fn (User $record): bool => filled($record->phone)
                        && ! $record->is(auth()->user())
                        && auth()->user()->can('update', $record))
                    ->action(function (User $record): void {
                        try {
                            $smsSent = app(InternalAccountService::class)->resetPassword(auth()->user(), $record);
                        } catch (AuthorizationException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();

                            return;
                        }

                        $smsSent
                            ? Notification::make()->success()->title('Mot de passe temporaire envoyé par SMS')->send()
                            : Notification::make()->warning()->title("Le SMS n'a pas pu être envoyé")->body('Réessayez dans un instant.')->send();
                    }),

                Action::make('anonymize')
                    ->label('Anonymiser')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Anonymiser ce compte ?')
                    ->modalDescription('Les données personnelles seront effacées définitivement. Les factures restent conservées (obligation légale). Cette action est irréversible.')
                    // Le Gate::before du super-admin laisse tout passer : on vérifie donc aussi ici.
                    ->visible(fn (User $record): bool => auth()->user()->can('delete', $record)
                        && ! $record->is(auth()->user())
                        && auth()->user()->hierarchyLevel() < $record->hierarchyLevel())
                    ->action(function (User $record): void {
                        app(AccountErasureService::class)->anonymize($record);
                        app(AuditLogger::class)->log('users.anonymized', 'auth', $record);

                        Notification::make()->success()->title('Compte anonymisé')->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
