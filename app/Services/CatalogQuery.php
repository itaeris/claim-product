<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\ClaimPeriod;
use App\Models\Product;
use App\Support\IndonesianDate;
use Illuminate\Support\Facades\Cache;

class CatalogQuery
{
    private const KEY = 'catalog.v3';

    public function __construct(
        private ClaimPricing $pricing,
        private PurchaseLimit $limits,
    ) {}

    public function forget(): void
    {
        Cache::forget(self::KEY);
        Cache::forget('catalog.v2');
        Cache::forget('catalog.v1');
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return Cache::remember(self::KEY, now()->addHour(), function () {
            $period = ClaimPeriod::current();
            $brands = Brand::query()
                ->with([
                    'categories' => fn ($query) => $query->orderBy('sort_order'),
                    'products' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
                    'products.category',
                ])
                ->orderBy('sort_order')
                ->get();

            return [
                'period' => [
                    'name' => $period->name,
                    'ends_label' => 'Berakhir '.IndonesianDate::date($period->ends_on),
                ],
                'max_discount_percent' => (int) $brands->max('discount_percent'),
                'yearly_limit' => $this->limits->yearly(),
                'warehouse_address' => config('claimly.warehouse_address'),
                'brands' => $brands
                    ->map(fn (Brand $brand) => [
                        'id' => $brand->id,
                        'name' => $brand->name,
                        'short' => $brand->short,
                        'subtitle' => $brand->subtitle,
                        'company_name' => $brand->company_name,
                        'discount_percent' => $brand->discount_percent,
                        'shipping_fee' => $brand->shipping_fee,
                        'categories' => $brand->categories->pluck('name')->values()->all(),
                        'products' => $brand->products->map(fn (Product $product) => [
                            'id' => $product->id,
                            'name' => $product->name,
                            'category' => $product->category->name,
                            'price' => $product->retail_price,
                            'claim_price' => $this->pricing->claimUnitPrice($product->retail_price, $brand->discount_percent),
                            'image' => $product->image_url,
                        ])->values()->all(),
                    ])
                    ->values()
                    ->all(),
            ];
        });
    }
}
