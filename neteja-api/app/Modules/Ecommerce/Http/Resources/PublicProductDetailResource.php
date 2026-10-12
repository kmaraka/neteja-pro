<?php

namespace App\Modules\Ecommerce\Http\Resources;

use Illuminate\Http\Request;

class PublicProductDetailResource extends PublicProductResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'description' => $this->description,
            'variants' => $this->variants->map(fn ($variant) => [
                'id' => $variant->id,
                'name' => $variant->name,
                'price_fcfa' => $variant->price_fcfa,
                'in_stock' => $variant->isInStock(),
                // Stock exact masqué, sauf quand il devient faible (« plus que 3 »).
                'stock_left' => $variant->stock > 0 && $variant->stock <= 5 ? $variant->stock : null,
            ])->values(),
        ];
    }
}
