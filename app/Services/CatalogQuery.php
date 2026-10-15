<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\ClaimPeriod;
use App\Models\Product;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

class CatalogQuery
{
    public function __construct(private ClaimPricing $pricing) {}

    public function forget(): void
    {
        Cache::forget('catalog.v1');
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return Cache::remember('catalog.v1', now()->addHour(), function () {
            $period = ClaimPeriod::current();

            return [
                'period' => [
                    'name' => $period->name,
                    'ends_label' => 'Berakhir '.$this->formatDate($period->ends_on),
                    'discount_percent' => $period->discount_percent,
                ],
                'brands' => Brand::query()
                    ->with([
                        'categories' => fn ($query) => $query->orderBy('sort_order'),
                        'products' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
                        'products.category',
                    ])
                    ->orderBy('sort_order')
                    ->get()
                    ->map(fn (Brand $brand) => [
                        'id' => $brand->id,
                        'name' => $brand->name,
                        'short' => $brand->short,
                        'subtitle' => $brand->subtitle,
                        'categories' => $brand->categories->pluck('name')->values()->all(),
                        'products' => $brand->products->map(fn (Product $product) => [
                            'id' => $product->id,
                            'name' => $product->name,
                            'category' => $product->category->name,
                            'price' => $product->retail_price,
                            'claim_price' => $this->pricing->claimUnitPrice($product->retail_price, $period->discount_percent),
                            'image' => $product->image_url,
                        ])->values()->all(),
                    ])
                    ->values()
                    ->all(),
            ];
        });
    }

    private function formatDate(CarbonInterface $date): string
    {
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return $date->day.' '.$months[$date->month].' '.$date->year;
    }
}
