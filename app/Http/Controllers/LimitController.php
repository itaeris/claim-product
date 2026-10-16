<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CartStore;
use App\Services\PurchaseLimit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LimitController extends Controller
{
    public function index(Request $request, PurchaseLimit $limits, CartStore $cart): JsonResponse
    {
        $user = $request->user();
        $year = $limits->currentYear();
        $byProduct = $limits->usedByProduct($user, $year);
        $products = Product::query()->with('brand')->whereIn('id', array_keys($byProduct))->get();

        $rows = $products
            ->map(fn (Product $product) => [
                'product_id' => $product->id,
                'name' => $product->name,
                'brand' => $product->brand?->name,
                'brand_short' => $product->brand?->short,
                'image_url' => $product->image_url,
                'qty' => $byProduct[$product->id],
            ])
            ->sortBy([['qty', 'desc'], ['name', 'asc']])
            ->values();

        return response()->json([
            ...$limits->status($user),
            'in_cart' => array_sum($cart->items($user)),
            'days_to_reset' => (int) ceil(now()->diffInDays($limits->resetsOn($year), true)),
            'brands' => $rows
                ->groupBy('brand_short')
                ->map(fn ($group, $short) => [
                    'short' => $short,
                    'name' => $group->first()['brand'],
                    'qty' => $group->sum('qty'),
                ])
                ->sortByDesc('qty')
                ->values()
                ->all(),
            'products' => $rows->all(),
        ]);
    }
}
