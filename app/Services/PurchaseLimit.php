<?php

namespace App\Services;

use App\Models\ClaimItem;
use App\Models\LimitRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class PurchaseLimit
{
    public function perSku(): int
    {
        return max(1, (int) config('claimly.sku_yearly_limit', 5));
    }

    /**
     * @param  array<int, int>  $productIds
     * @return array<int, int>
     */
    public function used(User $user, array $productIds, ?int $year = null): array
    {
        if ($productIds === []) {
            return [];
        }

        $year ??= now()->year;

        return ClaimItem::query()
            ->join('claims', 'claims.id', '=', 'claim_items.claim_id')
            ->where('claims.user_id', $user->id)
            ->where('claims.status', '!=', 'dibatalkan')
            ->whereBetween('claims.submitted_at', [
                Carbon::create($year, 1, 1)->startOfDay(),
                Carbon::create($year, 12, 31)->endOfDay(),
            ])
            ->whereIn('claim_items.product_id', $productIds)
            ->groupBy('claim_items.product_id')
            ->selectRaw('claim_items.product_id as product_id, SUM(claim_items.quantity) as qty')
            ->pluck('qty', 'product_id')
            ->map(fn ($qty) => (int) $qty)
            ->all();
    }

    /**
     * @param  array<int, int>  $productIds
     * @return array<int, int>
     */
    public function extra(User $user, array $productIds, ?int $year = null): array
    {
        if ($productIds === []) {
            return [];
        }

        return LimitRequest::query()
            ->where('user_id', $user->id)
            ->where('year', $year ?? now()->year)
            ->where('status', 'disetujui')
            ->whereIn('product_id', $productIds)
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(extra_qty) as qty')
            ->pluck('qty', 'product_id')
            ->map(fn ($qty) => (int) $qty)
            ->all();
    }

    /**
     * @param  array<int, int>  $productIds
     * @return array<int, array{used: int, allowed: int, remaining: int}>
     */
    public function summary(User $user, array $productIds, ?int $year = null): array
    {
        $used = $this->used($user, $productIds, $year);
        $extra = $this->extra($user, $productIds, $year);
        $summary = [];

        foreach ($productIds as $productId) {
            $allowed = $this->perSku() + ($extra[$productId] ?? 0);
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

        foreach ($quantities as $productId => $qty) {
            $remaining = $summary[$productId]['remaining'] ?? $this->perSku();

            if ($qty > $remaining) {
                $name = $names[$productId] ?? 'produk ini';

                throw ValidationException::withMessages([
                    'limit' => $remaining > 0
                        ? "Sisa kuota {$name} tahun ini {$remaining} pcs. Ajukan penambahan limit di halaman Profil."
                        : "Kuota {$name} tahun ini sudah habis (maksimal {$this->perSku()} pcs per SKU). Ajukan penambahan limit di halaman Profil.",
                ]);
            }
        }
    }
}
