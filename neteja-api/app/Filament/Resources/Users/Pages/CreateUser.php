<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Modules\Auth\Services\InternalAccountService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    private bool $smsSent = true;

    /** On passe par notre service (hiérarchie, mot de passe temporaire, SMS, audit) et non par un simple create(). */
    protected function handleRecordCreation(array $data): Model
    {
        $roles = Arr::pull($data, 'roles', []);

        [$user, $this->smsSent] = app(InternalAccountService::class)->create(auth()->user(), $data, $roles);

        return $user;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return $this->smsSent
            ? Notification::make()->success()->title('Compte créé')->body('Le mot de passe temporaire a été envoyé par SMS.')
            : Notification::make()->warning()->title("Compte créé, mais le SMS n'a pas pu être envoyé")
                ->body('Utilisez « Réinitialiser le mot de passe » pour réessayer.');
    }
}
