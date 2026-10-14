<?php

namespace App\Modules\Ecommerce\Http\Controllers;

use App\Modules\Ecommerce\Exceptions\PromoCodeException;
use App\Modules\Ecommerce\Models\Cart;
use App\Modules\Ecommerce\Services\CartPricingService;
use App\Modules\Ecommerce\Services\CartService;
use App\Modules\Ecommerce\Services\PromoCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartController
{
    public function __construct(
        private CartService $carts,
        private CartPricingService $pricing,
        private PromoCodeService $promos,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return $this->respond($this->carts->resolve($request), $request);
    }

    public function addItem(Request $request): JsonResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:1000'],
        ]);

        // Dans une transaction : si l'ajout est refusé, le panier créé pour l'occasion disparaît aussi.
        $cart = DB::transaction(function () use ($request, $data) {
            $cart = $this->carts->resolve($request, create: true);
            $this->carts->addItem($cart, (int) $data['variant_id'], (int) ($data['quantity'] ?? 1));

            return $cart;
        });

        return $this->respond($cart, $request, 201);
    }

    public function updateItem(Request $request, int $item): JsonResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:1000']]);

        $cart = $this->carts->resolve($request) ?? abort(404);

        // On cherche la ligne DANS ce panier : celle d'un autre panier donne un 404.
        $this->carts->setQuantity($cart, $cart->items()->findOrFail($item), (int) $data['quantity']);

        return $this->respond($cart, $request);
    }

    public function removeItem(Request $request, int $item): JsonResponse
    {
        $cart = $this->carts->resolve($request) ?? abort(404);

        $cart->items()->findOrFail($item)->delete();
        $this->carts->touch($cart);

        return $this->respond($cart, $request);
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->carts->resolve($request);

        if ($cart) {
            $cart->items()->delete();
            $cart->update(['promo_code_id' => null]);
            $this->carts->touch($cart);
        }

        return $this->respond($cart, $request);
    }

    public function applyPromo(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:40']]);

        $cart = $this->carts->resolve($request);
        $subtotal = $cart ? $this->pricing->subtotal($cart) : 0;

        if (! $cart || $subtotal === 0) {
            throw ValidationException::withMessages(['code' => ['Votre panier est vide.']]);
        }

        $promo = $this->promos->find($data['code']);

        if (! $promo) {
            throw ValidationException::withMessages(['code' => ['Ce code promo est inconnu.']]);
        }

        try {
            $this->promos->discountFor($promo, $subtotal);
        } catch (PromoCodeException $e) {
            throw ValidationException::withMessages(['code' => [$e->getMessage()]]);
        }

        $cart->update(['promo_code_id' => $promo->id]);
        $this->carts->touch($cart);

        return $this->respond($cart, $request);
    }

    public function removePromo(Request $request): JsonResponse
    {
        $cart = $this->carts->resolve($request);

        $cart?->update(['promo_code_id' => null]);

        return $this->respond($cart, $request);
    }

    /** Appelée par le front juste après la connexion : verse le panier du visiteur dans celui du compte. */
    public function merge(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = (string) $request->header('X-Cart-Token', '');

        $result = $this->carts->merge($user, $token);

        return response()->json([
            'data' => $this->pricing->summary(Cart::where('user_id', $user->id)->first()),
            'merge' => $result,
        ]);
    }

    private function respond(?Cart $cart, Request $request, int $status = 200): JsonResponse
    {
        $commune = $request->query('commune');
        $commune = is_string($commune) && trim($commune) !== '' ? trim($commune) : null;

        return response()->json(['data' => $this->pricing->summary($cart, $commune)], $status);
    }
}
