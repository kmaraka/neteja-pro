<?php

namespace App\Modules\Ecommerce\Http\Controllers;

use App\Modules\Ecommerce\Http\Requests\ProductIndexRequest;
use App\Modules\Ecommerce\Http\Resources\PublicCategoryResource;
use App\Modules\Ecommerce\Http\Resources\PublicProductDetailResource;
use App\Modules\Ecommerce\Http\Resources\PublicProductResource;
use App\Modules\Ecommerce\Models\Category;
use App\Modules\Ecommerce\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CatalogController
{
    public function categories(): AnonymousResourceCollection
    {
        $categories = Category::active()
            ->withCount(['products as products_count' => fn (Builder $query) => $this->sellable($query)])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn (Category $category) => $category->products_count > 0)
            ->values();

        return PublicCategoryResource::collection($categories);
    }

    public function products(ProductIndexRequest $request): AnonymousResourceCollection
    {
        $query = $this->sellable(Product::query())
            ->with(['category', 'variants' => fn ($query) => $query->active()])
            ->withMin(['variants as price_from' => fn ($query) => $query->active()], 'price_fcfa');

        if ($slug = $request->validated('category')) {
            $query->whereHas('category', fn (Builder $category) => $category->where('slug', $slug));
        }

        if ($term = $request->validated('search')) {
            $like = '%'.$term.'%';
            $query->where(fn (Builder $q) => $q->where('name', 'like', $like)->orWhere('description', 'like', $like));
        }

        $sort = $request->validated('sort', 'newest');

        if ($sort === 'price_asc') {
            $query->orderBy('price_from')->orderBy('id');
        } elseif ($sort === 'price_desc') {
            $query->orderByDesc('price_from')->orderBy('id');
        } else {
            $query->latest('id');
        }

        return PublicProductResource::collection(
            $query->paginate((int) $request->validated('per_page', 12))->withQueryString()
        );
    }

    public function product(string $slug): PublicProductDetailResource
    {
        $product = $this->sellable(Product::query())
            ->with(['category', 'variants' => fn ($query) => $query->active()])
            ->where('slug', $slug)
            ->firstOrFail();

        return new PublicProductDetailResource($product);
    }

    /** Un produit n'est visible que s'il est en ligne, dans une catégorie visible, avec au moins une variante active. */
    private function sellable(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereHas('category', fn (Builder $category) => $category->where('is_active', true))
            ->whereHas('variants', fn (Builder $variant) => $variant->where('is_active', true));
    }
}
