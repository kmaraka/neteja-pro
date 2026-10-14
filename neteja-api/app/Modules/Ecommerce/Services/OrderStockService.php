<?php

namespace App\Modules\Ecommerce\Services;

use App\Modules\Ecommerce\Models\Order;
use App\Modules\Ecommerce\Models\ProductVariant;
use Illuminate\Validation\ValidationException;

class OrderStockService
{
    /**
     * Réserve le stock : décrémente chaque variante, sous verrou.
     * À appeler dans une transaction : si une ligne échoue, tout est annulé.
     *
     * @param  array<int, int>  $quantities  identifiant de variante => quantité
     *
     * @throws ValidationException
     */
    public function reserve(array $quantities): void
    {
        // Ordre fixe : deux commandes simultanées ne peuvent pas se bloquer mutuellement.
        ksort($quantities);

        $variants = ProductVariant::with('product.category')
            ->whereIn('id', array_keys($quantities))
            ->orderBy('id')
            ->lockForUpdate()  // les autres commandes attendent ici
            ->get()
            ->keyBy('id');

        foreach ($quantities as $variantId => $quantity) {
            $variant = $variants->get($variantId);

            if (! $variant || ! $variant->is_active || ! $variant->product->is_active || ! $variant->product->category->is_active) {
                throw ValidationException::withMessages(['cart' => ["Un article de votre panier n'est plus disponible."]]);
            }

            if ($variant->stock < $quantity) {
                throw ValidationException::withMessages([
                    'cart' => ["Le stock de « {$variant->product->name} ({$variant->name}) » est insuffisant."],
                ]);
            }
        }

        foreach ($quantities as $variantId => $quantity) {
            $variants[$variantId]->decrement('stock', $quantity);
        }
    }

    /** Rend au stock les quantités d'une commande annulée. */
    public function release(Order $order): void
    {
        $quantities = $order->items()->get()->pluck('quantity', 'product_variant_id')->all();
        ksort($quantities);

        foreach ($quantities as $variantId => $quantity) {
            ProductVariant::whereKey($variantId)->increment('stock', $quantity);
        }
    }
}
