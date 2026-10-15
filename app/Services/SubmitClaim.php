<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\ClaimPeriod;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitClaim
{
    public function __construct(
        private CartStore $cart,
        private ClaimPricing $pricing,
    ) {}

    public function handle(User $user): Claim
    {
        $quantities = $this->cart->items($user);

        if ($quantities === []) {
            throw ValidationException::withMessages([
                'cart' => 'Keranjang klaim masih kosong.',
            ]);
        }

        $claim = DB::transaction(function () use ($user, $quantities) {
            $period = ClaimPeriod::current();
            $products = Product::query()
                ->whereIn('id', array_keys($quantities))
                ->where('is_active', true)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $lines = [];
            $retailTotal = 0;
            $claimTotal = 0;

            foreach ($quantities as $productId => $qty) {
                $product = $products->get((int) $productId);

                if ($product === null) {
                    continue;
                }

                $unitClaim = $this->pricing->claimUnitPrice($product->retail_price, $period->discount_percent);
                $lines[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $qty,
                    'unit_retail_price' => $product->retail_price,
                    'unit_claim_price' => $unitClaim,
                ];
                $retailTotal += $product->retail_price * $qty;
                $claimTotal += $unitClaim * $qty;
            }

            if ($lines === []) {
                throw ValidationException::withMessages([
                    'cart' => 'Keranjang klaim masih kosong.',
                ]);
            }

            $claim = Claim::query()->create([
                'user_id' => $user->id,
                'claim_period_id' => $period->id,
                'number' => $this->nextNumber($period),
                'status' => 'diajukan',
                'retail_total' => $retailTotal,
                'saving_total' => $retailTotal - $claimTotal,
                'claim_total' => $claimTotal,
                'submitted_at' => now(),
            ]);

            $claim->items()->createMany($lines);

            return $claim;
        });

        $this->cart->clear($user);

        return $claim;
    }

    private function nextNumber(ClaimPeriod $period): string
    {
        $latest = Claim::query()
            ->where('claim_period_id', $period->id)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('number');

        $sequence = 1;

        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return sprintf('CLM-%s-%04d', $period->starts_on->format('Ym'), $sequence);
    }
}
