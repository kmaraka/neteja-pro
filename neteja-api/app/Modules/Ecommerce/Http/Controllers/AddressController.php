<?php

namespace App\Modules\Ecommerce\Http\Controllers;

use App\Modules\Ecommerce\Http\Requests\AddressRequest;
use App\Modules\Ecommerce\Http\Resources\AddressResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddressController
{
    private const MAX_PER_USER = 10;

    public function index(Request $request): AnonymousResourceCollection
    {
        return AddressResource::collection(
            $request->user()->addresses()->orderByDesc('is_default')->orderBy('id')->get()
        );
    }

    public function store(AddressRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->addresses()->count() >= self::MAX_PER_USER) {
            throw ValidationException::withMessages([
                'label' => ['Vous ne pouvez pas enregistrer plus de '.self::MAX_PER_USER.' adresses.'],
            ]);
        }

        // La première adresse devient automatiquement l'adresse par défaut.
        $address = $user->addresses()->create(
            $request->validated() + ['is_default' => ! $user->addresses()->exists()]
        );

        return (new AddressResource($address))->response()->setStatusCode(201);
    }

    public function update(AddressRequest $request, int $address): AddressResource
    {
        // On cherche parmi les adresses DE CET utilisateur : celle d'un autre donne un 404.
        $model = $request->user()->addresses()->findOrFail($address);
        $model->update($request->validated());

        return new AddressResource($model);
    }

    public function makeDefault(Request $request, int $address): AddressResource
    {
        $user = $request->user();
        $model = $user->addresses()->findOrFail($address);

        DB::transaction(function () use ($user, $model) {
            $user->addresses()->update(['is_default' => false]);
            $model->update(['is_default' => true]);
        });

        return new AddressResource($model->fresh());
    }

    public function destroy(Request $request, int $address): Response
    {
        $user = $request->user();
        $model = $user->addresses()->findOrFail($address);

        DB::transaction(function () use ($user, $model) {
            $wasDefault = $model->is_default;
            $model->delete();

            // Si on supprime l'adresse par défaut, la plus récente restante la remplace.
            if ($wasDefault) {
                $user->addresses()->latest('id')->first()?->update(['is_default' => true]);
            }
        });

        return response()->noContent();
    }
}
