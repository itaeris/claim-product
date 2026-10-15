<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\Product;
use App\Models\User;
use App\Services\CartPresenter;
use App\Services\CartStore;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    public function show(CartStore $cart, CartPresenter $presenter): JsonResponse
    {
        return response()->json($presenter->present($cart->items($this->employee())));
    }

    public function store(StoreCartItemRequest $request, CartStore $cart, CartPresenter $presenter): JsonResponse
    {
        $items = $cart->add($this->employee(), (int) $request->validated('product_id'));

        return response()->json($presenter->present($items));
    }

    public function update(UpdateCartItemRequest $request, Product $product, CartStore $cart, CartPresenter $presenter): JsonResponse
    {
        $items = $cart->setQty($this->employee(), $product->id, (int) $request->validated('qty'));

        return response()->json($presenter->present($items));
    }

    public function destroy(Product $product, CartStore $cart, CartPresenter $presenter): JsonResponse
    {
        $items = $cart->remove($this->employee(), $product->id);

        return response()->json($presenter->present($items));
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
