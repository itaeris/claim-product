<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\PurchaseLimit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LimitController extends Controller
{
    public function index(Request $request, PurchaseLimit $limits): JsonResponse
    {
        $year = $limits->currentYear();
        $perSku = $limits->perSku();
        $used = $limits->used($request->user(), null, $year);
        $products = Product::query()->with('brand')->whereIn('id', array_keys($used))->get();

        $rows = $products
            ->map(fn (Product $product) => [
                'product_id' => $product->id,
                'name' => $product->name,
                'brand' => $product->brand?->name,
                'brand_short' => $product->brand?->short,
                'image_url' => $product->image_url,
                'used' => $used[$product->id],
                'allowed' => $perSku,
                'remaining' => max(0, $perSku - $used[$product->id]),
            ])
            ->sortBy([['used', 'desc'], ['name', 'asc']])
            ->values();

        return response()->json([
            'year' => $year,
            'per_sku' => $perSku,
            'resets_label' => $limits->resetsLabel($year),
            'days_to_reset' => (int) ceil(now()->diffInDays($limits->resetsOn($year), true)),
            'totals' => [
                'skus' => $rows->count(),
                'pcs' => $rows->sum('used'),
                'full' => $rows->where('remaining', 0)->count(),
            ],
            'products' => $rows->all(),
        ]);
    }
}
