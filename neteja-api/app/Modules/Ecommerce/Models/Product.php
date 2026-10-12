<?php

namespace App\Modules\Ecommerce\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['category_id', 'name', 'slug', 'description', 'image_path', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Prix « à partir de » : la variante active la moins chère (null si aucune). */
    public function priceFrom(): ?int
    {
        return $this->variants->where('is_active', true)->min('price_fcfa');
    }

    /** Stock total des variantes actives. */
    public function stockTotal(): int
    {
        return (int) $this->variants->where('is_active', true)->sum('stock');
    }

    public function isInStock(): bool
    {
        return $this->stockTotal() > 0;
    }
}
