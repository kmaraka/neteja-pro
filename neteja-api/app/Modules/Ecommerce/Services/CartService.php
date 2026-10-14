<?php

namespace App\Modules\Ecommerce\Services;

use App\Models\User;
use App\Modules\Ecommerce\Models\Cart;
use App\Modules\Ecommerce\Models\CartItem;
use App\Modules\Ecommerce\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartService
{
    /**
     * Retrouve le panier de la requête : celui du compte si l'utilisateur est connecté,
     * sinon celui désigné par l'en-tête X-Cart-Token. Avec $create, un panier neuf est créé si besoin.
     */
    public function resolve(Request $request, bool $create = false): ?Cart
    {
        $user = $request->user('sanctum');

        if ($user) {
            return $create
                ? Cart::firstOrCreate(['user_id' => $user->id], ['last_activity_at' => now()])
                : Cart::where('user_id', $user->id)->first();
        }

        $token = (string) $request->header('X-Cart-Token', '');

        if (strlen($token) === 40) {
            $cart = Cart::where('token', $token)->whereNull('user_id')->first();

            if ($cart) {
                return $cart;
            }
        }

        // Jeton absent ou périmé : on n'en crée un neuf que lorsqu'on ajoute un article.
        return $create
            ? Cart::create(['token' => Str::random(40), 'last_activity_at' => now()])
            : null;
    }

    public function addItem(Cart $cart, int $variantId, int $quantity): CartItem
    {
        $variant = ProductVariant::with('product.category')->find($variantId);

        if (! $variant || ! $this->isSellable($variant)) {
            throw ValidationException::withMessages(['variant_id' => ["Cet article n'est plus disponible."]]);
        }

        $item = $cart->items()->firstOrNew(['product_variant_id' => $variant->id]);
        $total = ($item->exists ? $item->quantity : 0) + $quantity;

        $this->assertStock($variant, $total);

        $item->quantity = $total;
        $item->save();
        $this->touch($cart);

        return $item;
    }

    public function setQuantity(Cart $cart, CartItem $item, int $quantity): void
    {
        $variant = ProductVariant::with('product.category')->findOrFail($item->product_variant_id);

        if (! $this->isSellable($variant)) {
            throw ValidationException::withMessages(['quantity' => ["Cet article n'est plus disponible."]]);
        }

        $this->assertStock($variant, $quantity);

        $item->update(['quantity' => $quantity]);
        $this->touch($cart);
    }

    public function touch(Cart $cart): void
    {
        $cart->forceFill(['last_activity_at' => now()])->save();
    }

    /**
     * Fusionne le panier d'un visiteur dans celui du compte (à la connexion).
     * Les quantités s'additionnent, plafonnées au stock disponible.
     *
     * @return array{merged: bool, adjusted: int}
     */
    public function merge(User $user, string $token): array
    {
        $guest = strlen($token) === 40
            ? Cart::where('token', $token)->whereNull('user_id')->first()
            : null;

        if (! $guest) {
            return ['merged' => false, 'adjusted' => 0];
        }

        return DB::transaction(function () use ($user, $guest) {
            $cart = Cart::firstOrCreate(['user_id' => $user->id], ['last_activity_at' => now()]);
            $adjusted = 0;

            foreach ($guest->items()->with('variant.product.category')->get() as $guestItem) {
                $variant = $guestItem->variant;

                if (! $this->isSellable($variant)) {
                    $adjusted++;

                    continue;
                }

                $item = $cart->items()->firstOrNew(['product_variant_id' => $variant->id]);
                $wanted = ($item->exists ? $item->quantity : 0) + $guestItem->quantity;
                $final = min($wanted, $variant->stock);

                if ($final < $wanted) {
                    $adjusted++;
                }

                if ($final > 0) {
                    $item->quantity = $final;
                    $item->save();
                }
            }

            // Le code promo du compte l'emporte ; sinon on reprend celui du visiteur.
            if ($cart->promo_code_id === null && $guest->promo_code_id !== null) {
                $cart->promo_code_id = $guest->promo_code_id;
            }

            $cart->last_activity_at = now();
            $cart->save();

            $guest->delete(); // ses lignes partent avec lui (suppression en cascade)

            return ['merged' => true, 'adjusted' => $adjusted];
        });
    }

    /** Un article est achetable s'il est actif, ainsi que son produit et sa catégorie. */
    private function isSellable(ProductVariant $variant): bool
    {
        return $variant->is_active
            && $variant->product->is_active
            && $variant->product->category->is_active;
    }

    private function assertStock(ProductVariant $variant, int $quantity): void
    {
        if ($quantity <= $variant->stock) {
            return;
        }

        // Même règle de confidentialité que le catalogue : le stock n'est cité que s'il est faible.
        $message = match (true) {
            $variant->stock <= 0 => 'Cet article est en rupture de stock.',
            $variant->stock <= 5 => "Il n'en reste que {$variant->stock} en stock.",
            default => 'Stock insuffisant pour cette quantité.',
        };

        throw ValidationException::withMessages(['quantity' => [$message]]);
    }
}
