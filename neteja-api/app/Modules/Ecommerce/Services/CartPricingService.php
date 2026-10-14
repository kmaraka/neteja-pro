<?php

namespace App\Modules\Ecommerce\Services;

use App\Modules\Ecommerce\Exceptions\PromoCodeException;
use App\Modules\Ecommerce\Models\Cart;
use App\Modules\Ecommerce\Models\CartItem;
use App\Modules\Ecommerce\Models\DeliveryZone;
use Illuminate\Support\Facades\Storage;

class CartPricingService
{
    public function __construct(private PromoCodeService $promos) {}

    public function subtotal(Cart $cart): int
    {
        return (int) $cart->items()->with('variant')->get()
            ->sum(fn (CartItem $item) => $item->variant->price_fcfa * $item->quantity);
    }

    /**
     * Contenu du panier et total, prêts à être affichés.
     * $commune (facultative) permet d'estimer les frais de livraison.
     */
    public function summary(?Cart $cart, ?string $commune = null): array
    {
        $lines = collect();

        if ($cart) {
            $cart->load(['items.variant.product.category', 'promoCode']);
            $lines = $cart->items->map(fn (CartItem $item) => $this->line($item))->values();
        }

        $subtotal = (int) $lines->sum('line_total_fcfa');

        // Code promo : revérifié à chaque lecture, retiré s'il n'est plus utilisable.
        $discount = 0;
        $promo = null;
        $promoError = null;

        if ($cart?->promoCode) {
            try {
                $discount = $this->promos->discountFor($cart->promoCode, $subtotal);
                $promo = [
                    'code' => $cart->promoCode->code,
                    'type' => $cart->promoCode->type,
                    'value' => $cart->promoCode->value,
                    'discount_fcfa' => $discount,
                ];
            } catch (PromoCodeException $e) {
                $cart->update(['promo_code_id' => null]);
                $promoError = $e->getMessage();
            }
        }

        // Livraison : seulement si une commune est indiquée.
        $delivery = null;
        $deliveryError = null;

        if ($commune !== null) {
            $zone = DeliveryZone::active()->where('commune', $commune)->first();

            if ($zone) {
                $delivery = [
                    'commune' => $zone->commune,
                    'fee_fcfa' => $zone->fee_fcfa,
                    'estimated_delay' => $zone->estimated_delay,
                ];
            } else {
                $deliveryError = 'Nous ne livrons pas encore dans cette commune.';
            }
        }

        return [
            'token' => $cart?->token,
            'items_count' => (int) $lines->sum('quantity'),
            'lines' => $lines,
            'has_issues' => $lines->contains(fn (array $line) => $line['issue'] !== null),
            'subtotal_fcfa' => $subtotal,
            'promo' => $promo,
            'promo_error' => $promoError,
            'discount_fcfa' => $discount,
            'delivery' => $delivery,
            'delivery_error' => $deliveryError,
            'delivery_fee_fcfa' => $delivery['fee_fcfa'] ?? null,
            'total_fcfa' => max(0, $subtotal - $discount) + ($delivery['fee_fcfa'] ?? 0),
        ];
    }

    private function line(CartItem $item): array
    {
        $variant = $item->variant;
        $product = $variant->product;

        $sellable = $variant->is_active && $product->is_active && $product->category->is_active;

        $issue = null;
        $stockLeft = null;

        if (! $sellable) {
            $issue = 'unavailable';
        } elseif ($item->quantity > $variant->stock) {
            $issue = 'insufficient_stock';
            $stockLeft = $variant->stock <= 5 ? $variant->stock : null;
        }

        return [
            'item_id' => $item->id,
            'variant_id' => $variant->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'variant_name' => $variant->name,
            'image_url' => $product->image_path ? Storage::disk('public')->url($product->image_path) : null,
            'unit_price_fcfa' => $variant->price_fcfa,
            'quantity' => $item->quantity,
            'line_total_fcfa' => $variant->price_fcfa * $item->quantity,
            'issue' => $issue,
            'stock_left' => $stockLeft,
        ];
    }
}
