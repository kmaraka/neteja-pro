<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    // Pas de bouton « Supprimer » : on met le produit hors ligne.
    protected function getHeaderActions(): array
    {
        return [];
    }
}
