<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\Product;
use App\Models\User;
use App\Services\CartPresenter;
use App\Services\CartStore;
use App\Services\PurchaseLimit;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    public function __construct(
        private CartStore $cart,
        private CartPresenter $presenter,
        private PurchaseLimit $limits,
    ) {}

    public function show(): JsonResponse
    {
        $user = $this->employee();

        return response()->json($this->presenter->present($user, $this->cart->items($user)));
    }

    public function store(StoreCartItemRequest $request): JsonResponse
    {
        $user = $this->employee();
        $product = Product::query()->findOrFail((int) $request->validated('product_id'));

        $this->limits->ensureWithin($user, array_sum($this->cart->items($user)) + 1);
        $items = $this->cart->add($user, $product->id);

        return response()->json($this->presenter->present($user, $items));
    }

    public function update(UpdateCartItemRequest $request, Product $product): JsonResponse
    {
        $user = $this->employee();
        $qty = (int) $request->validated('qty');

        if ($qty > 0) {
            $current = $this->cart->items($user);
            $this->limits->ensureWithin($user, array_sum($current) - ($current[$product->id] ?? 0) + $qty);
        }

        $items = $this->cart->setQty($user, $product->id, $qty);

        return response()->json($this->presenter->present($user, $items));
    }

    public function destroy(Product $product): JsonResponse
    {
        $user = $this->employee();
        $items = $this->cart->remove($user, $product->id);

        return response()->json($this->presenter->present($user, $items));
    }

    private function employee(): User
    {
        $user = request()->user();

        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }
}
