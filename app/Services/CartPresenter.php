<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;

class CartPresenter
{
    public function __construct(
        private ClaimPricing $pricing,
        private PurchaseLimit $limits,
    ) {}

    /**
     * @param  array<int|string, int>  $quantities
     * @return array<string, mixed>
     */
    public function present(User $user, array $quantities): array
    {
        $quantities = collect($quantities)
            ->mapWithKeys(fn ($qty, $id) => [(int) $id => (int) $qty])
            ->filter(fn (int $qty, int $id) => $id > 0 && $qty > 0);

        $products = $quantities->isEmpty()
            ? collect()
            : Product::query()
                ->with('brand')
                ->whereIn('id', $quantities->keys())
                ->where('is_active', true)
                ->get()
                ->keyBy('id');

        $limits = $this->limits->summary($user, $products->keys()->all());
        $items = [];
        $groups = [];
        $retailTotal = 0;
        $claimTotal = 0;
        $count = 0;

        foreach ($quantities as $productId => $qty) {
            $product = $products->get($productId);

            if ($product === null) {
                continue;
            }

            $brand = $product->brand;
            $unitClaim = $this->pricing->claimUnitPrice($product->retail_price, $brand->discount_percent);
            $items[] = [
                'product_id' => $product->id,
                'brand_id' => $brand->id,
                'name' => $product->name,
                'image' => $product->image_url,
                'qty' => $qty,
                'unit_retail_price' => $product->retail_price,
                'unit_claim_price' => $unitClaim,
                'discount_percent' => $brand->discount_percent,
                'limit_remaining' => $limits[$product->id]['remaining'] ?? $this->limits->perSku(),
            ];

            $groups[$brand->id] ??= [
                'brand_id' => $brand->id,
                'brand_name' => $brand->name,
                'brand_short' => $brand->short,
                'company_name' => $brand->company_name,
                'discount_percent' => $brand->discount_percent,
                'shipping_fee' => $brand->shipping_fee,
                'sort_order' => $brand->sort_order,
                'retail_total' => 0,
                'claim_total' => 0,
                'count' => 0,
            ];
            $groups[$brand->id]['retail_total'] += $product->retail_price * $qty;
            $groups[$brand->id]['claim_total'] += $unitClaim * $qty;
            $groups[$brand->id]['count'] += $qty;

            $retailTotal += $product->retail_price * $qty;
            $claimTotal += $unitClaim * $qty;
            $count += $qty;
        }

        $groups = collect($groups)
            ->sortBy('sort_order')
            ->map(function (array $group) {
                unset($group['sort_order']);
                $group['saving_total'] = $group['retail_total'] - $group['claim_total'];

                return $group;
            })
            ->values()
            ->all();

        return [
            'items' => $items,
            'groups' => $groups,
            'count' => $count,
            'retail_total' => $retailTotal,
            'saving_total' => $retailTotal - $claimTotal,
            'claim_total' => $claimTotal,
            'delivery_fee_total' => collect($groups)->sum('shipping_fee'),
            'sku_limit' => $this->limits->perSku(),
        ];
    }
}
