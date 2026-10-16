<?php

namespace App\Services;

use App\Models\ClaimItem;
use App\Models\User;
use App\Support\IndonesianDate;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class PurchaseLimit
{
    private const TIMEZONE = 'Asia/Jakarta';

    public function perSku(): int
    {
        return max(1, (int) config('claimly.sku_yearly_limit', 5));
    }

    public function currentYear(): int
    {
        return now(self::TIMEZONE)->year;
    }

    public function resetsOn(?int $year = null): Carbon
    {
        return Carbon::create(($year ?? $this->currentYear()) + 1, 1, 1, 0, 0, 0, self::TIMEZONE);
    }

    public function resetsLabel(?int $year = null): string
    {
        return IndonesianDate::date($this->resetsOn($year));
    }

    /**
     * Pieces bought per product in the calendar year (WIB), excluding cancelled orders.
     *
     * @param  array<int, int>|null  $productIds  null for every product
     * @return array<int, int>
     */
    public function used(User $user, ?array $productIds = null, ?int $year = null): array
    {
        if ($productIds === []) {
            return [];
        }

        $year ??= $this->currentYear();

        return ClaimItem::query()
            ->join('claims', 'claims.id', '=', 'claim_items.claim_id')
            ->where('claims.user_id', $user->id)
            ->where('claims.status', '!=', 'dibatalkan')
            ->where('claims.submitted_at', '>=', $this->resetsOn($year - 1)->utc())
            ->where('claims.submitted_at', '<', $this->resetsOn($year)->utc())
            ->when($productIds !== null, fn ($query) => $query->whereIn('claim_items.product_id', $productIds))
            ->groupBy('claim_items.product_id')
            ->selectRaw('claim_items.product_id as product_id, SUM(claim_items.quantity) as qty')
            ->pluck('qty', 'product_id')
            ->mapWithKeys(fn ($qty, $productId) => [(int) $productId => (int) $qty])
            ->all();
    }

    /**
     * @param  array<int, int>  $productIds
     * @return array<int, array{used: int, allowed: int, remaining: int}>
     */
    public function summary(User $user, array $productIds, ?int $year = null): array
    {
        $used = $this->used($user, $productIds, $year);
        $allowed = $this->perSku();
        $summary = [];

        foreach ($productIds as $productId) {
            $taken = $used[$productId] ?? 0;
            $summary[$productId] = [
                'used' => $taken,
                'allowed' => $allowed,
                'remaining' => max(0, $allowed - $taken),
            ];
        }

        return $summary;
    }

    /**
     * @param  array<int, int>  $quantities  product id => qty
     * @param  array<int, string>  $names  product id => name
     */
    public function ensureWithin(User $user, array $quantities, array $names = []): void
    {
        $summary = $this->summary($user, array_keys($quantities));
        $reset = $this->resetsLabel();

        foreach ($quantities as $productId => $qty) {
            $remaining = $summary[$productId]['remaining'] ?? $this->perSku();

            if ($qty > $remaining) {
                $name = $names[$productId] ?? 'produk ini';

                throw ValidationException::withMessages([
                    'limit' => $remaining > 0
                        ? "Sisa kuota {$name} tahun ini {$remaining} pcs (maksimal {$this->perSku()} pcs per SKU)."
                        : "Kuota {$name} tahun ini sudah habis (maksimal {$this->perSku()} pcs per SKU). Kuota reset otomatis {$reset}.",
                ]);
            }
        }
    }
}
