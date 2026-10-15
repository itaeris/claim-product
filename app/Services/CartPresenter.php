<?php

namespace App\Services;

use App\Models\ClaimPeriod;
use App\Models\Product;
use Illuminate\Support\Collection;

class CartPresenter
{
    public function __construct(private ClaimPricing $pricing) {}

    /**
     * @param  array<int|string, int>  $quantities
     * @return array<string, mixed>
     */
    public function present(array $quantities): array
    {
        $period = ClaimPeriod::current();
        $quantities = collect($quantities)
            ->mapWithKeys(fn ($qty, $id) => [(int) $id => (int) $qty])
            ->filter(fn (int $qty, int $id) => $id > 0 && $qty > 0);

        $products = $quantities->isEmpty()
            ? collect()
            : Product::query()
                ->whereIn('id', $quantities->keys())
                ->where('is_active', true)
                ->get()
                ->keyBy('id');

        return $this->summarize($period, $quantities, $products);
    }

    /**
     * @param  Collection<int, int>  $quantities
     * @param  Collection<int, Product>  $products
     * @return array<string, mixed>
     */
    private function summarize(ClaimPeriod $period, Collection $quantities, Collection $products): array
    {
        $items = [];
        $retailTotal = 0;
        $claimTotal = 0;
        $count = 0;

        foreach ($quantities as $productId => $qty) {
            $product = $products->get($productId);

            if ($product === null) {
                continue;
            }

            $unitClaim = $this->pricing->claimUnitPrice($product->retail_price, $period->discount_percent);
            $items[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'image' => $product->image_url,
                'qty' => $qty,
                'unit_retail_price' => $product->retail_price,
                'unit_claim_price' => $unitClaim,
            ];
            $retailTotal += $product->retail_price * $qty;
            $claimTotal += $unitClaim * $qty;
            $count += $qty;
        }

        return [
            'items' => $items,
            'count' => $count,
            'retail_total' => $retailTotal,
            'saving_total' => $retailTotal - $claimTotal,
            'claim_total' => $claimTotal,
            'discount_percent' => $period->discount_percent,
        ];
    }
}
