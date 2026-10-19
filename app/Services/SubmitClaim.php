<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Claim;
use App\Models\ClaimPeriod;
use App\Models\Product;
use App\Models\User;
use App\Support\Rupiah;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitClaim
{
    public function __construct(
        private CartStore $cart,
        private ClaimPricing $pricing,
        private PurchaseLimit $limits,
        private ClaimNotifier $notifier,
    ) {}

    /**
     * @return Collection<int, Claim>
     */
    public function handle(User $user, string $fulfillment = 'pickup', ?string $address = null): Collection
    {
        $quantities = $this->cart->items($user);

        if ($quantities === []) {
            $this->emptyCart();
        }

        $claims = DB::transaction(function () use ($user, $quantities, $fulfillment, $address) {
            $period = ClaimPeriod::current();
            $products = Product::query()
                ->with('brand')
                ->whereIn('id', array_keys($quantities))
                ->where('is_active', true)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $quantities = array_filter(
                $quantities,
                fn (int $qty, int $productId) => $products->has($productId),
                ARRAY_FILTER_USE_BOTH,
            );

            if ($quantities === []) {
                $this->emptyCart();
            }

            $this->limits->ensureProducts($user, $quantities);

            $isDelivery = $fulfillment === 'delivery';
            $address = $isDelivery
                ? (trim((string) $address) !== '' ? trim((string) $address) : config('claimly.default_delivery_address'))
                : null;

            return collect($quantities)
                ->groupBy(fn (int $qty, int $productId) => $products[$productId]->brand_id, true)
                ->sortBy(fn (Collection $group, int $brandId) => $products->firstWhere('brand_id', $brandId)->brand->sort_order)
                ->map(function (Collection $group, int $brandId) use ($user, $period, $products, $isDelivery, $address) {
                    $brand = $products->firstWhere('brand_id', $brandId)->brand;

                    return $this->createInvoice($user, $period, $brand, $group, $products, $isDelivery, $address);
                })
                ->values();
        });

        $this->cart->clear($user);
        $this->announce($user, $claims);

        return $claims;
    }

    /**
     * @param  Collection<int, int>  $quantities
     * @param  Collection<int, Product>  $products
     */
    private function createInvoice(
        User $user,
        ClaimPeriod $period,
        Brand $brand,
        Collection $quantities,
        Collection $products,
        bool $isDelivery,
        ?string $address,
    ): Claim {
        $lines = [];
        $retailTotal = 0;
        $claimTotal = 0;

        foreach ($quantities as $productId => $qty) {
            $product = $products[$productId];
            $unitClaim = $this->pricing->claimUnitPrice($product->retail_price, $brand->discount_percent);
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

        $shippingFee = $isDelivery ? $brand->shipping_fee : 0;

        $claim = Claim::query()->create([
            'user_id' => $user->id,
            'claim_period_id' => $period->id,
            'brand_id' => $brand->id,
            'number' => $this->nextNumber($period),
            'invoice_number' => $this->nextInvoiceNumber($brand),
            'status' => 'cek_stok',
            'discount_percent' => $brand->discount_percent,
            'fulfillment' => $isDelivery ? 'delivery' : 'pickup',
            'shipping_address' => $address,
            'shipping_fee' => $shippingFee,
            'retail_total' => $retailTotal,
            'saving_total' => $retailTotal - $claimTotal,
            'claim_total' => $claimTotal,
            'total_due' => $claimTotal + $shippingFee,
            'submitted_at' => now(),
        ]);

        $claim->items()->createMany($lines);

        return $claim->setRelation('brand', $brand);
    }

    /**
     * @param  Collection<int, Claim>  $claims
     */
    private function announce(User $user, Collection $claims): void
    {
        $invoices = $claims->pluck('invoice_number')->join(', ');
        $count = $claims->count();

        $this->notifier->employee(
            $user,
            'Pesanan diterima',
            "Pesanan kamu dipisah menjadi {$count} invoice sesuai brand ({$invoices}). Tim kami sedang mengecek stok.",
        );

        $lines = $claims->map(fn (Claim $claim) => sprintf(
            '%s · %s · %s · %s',
            $claim->invoice_number,
            $claim->brand->name,
            Rupiah::format($claim->total_due),
            $claim->isDelivery() ? 'Kirim ke '.$claim->shipping_address : 'Ambil di gudang',
        ))->join("\n");

        $this->notifier->ops(
            "Pesanan baru dari {$user->name}",
            "Limit sudah dicek sistem. Mohon cek stok.\n{$lines}",
            $user,
        );
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

    private function nextInvoiceNumber(Brand $brand): string
    {
        $prefix = sprintf('INV/%s/%s/', $brand->invoiceCode(), now()->format('Ym'));
        $latest = Claim::query()
            ->where('invoice_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $sequence = is_string($latest) ? ((int) substr($latest, strlen($prefix))) + 1 : 1;

        return $prefix.sprintf('%04d', $sequence);
    }

    private function emptyCart(): never
    {
        throw ValidationException::withMessages([
            'cart' => 'Keranjang klaim masih kosong.',
        ]);
    }
}
