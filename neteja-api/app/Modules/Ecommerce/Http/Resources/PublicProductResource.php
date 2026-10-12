<?php

namespace App\Modules\Ecommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'excerpt' => Str::limit((string) $this->description, 140),
            'image_url' => $this->image_path ? Storage::disk('public')->url($this->image_path) : null,
            'category' => ['name' => $this->category->name, 'slug' => $this->category->slug],
            'price_from_fcfa' => $this->priceFrom(),
            'has_variants' => $this->variants->count() > 1,
            'in_stock' => $this->isInStock(),
        ];
    }
}
