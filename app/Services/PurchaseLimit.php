<?php

namespace App\Services;

use App\Models\ClaimItem;
use App\Models\User;
use App\Support\IndonesianDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class PurchaseLimit
{
    private const TIMEZONE = 'Asia/Jakarta';

    public function yearly(): int
    {
        return max(1, (int) config('claimly.yearly_limit', 5));
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
     * @return array{total: int, year: int, resets_label: string}
     */
    public function policy(): array
    {
        return [
            'total' => $this->yearly(),
            'year' => $this->currentYear(),
            'resets_label' => $this->resetsLabel(),
        ];
    }

    /**
     * @return array<int, int> product id => pieces
     */
    public function usedByProduct(User $user, ?int $year = null): array
    {
        return $this->purchases($user, $year)
            ->groupBy('claim_items.product_id')
            ->selectRaw('claim_items.product_id as product_id, SUM(claim_items.quantity) as qty')
            ->pluck('qty', 'product_id')
            ->mapWithKeys(fn ($qty, $productId) => [(int) $productId => (int) $qty])
            ->all();
    }

    /**
     * @param  array<int, int>  $quantities  product id => pieces requested on top of what was already bought
     */
    public function ensureProducts(User $user, array $quantities): void
    {
        $used = $this->usedByProduct($user);
        $cap = $this->yearly();

        foreach ($quantities as $productId => $pieces) {
            $remaining = max(0, $cap - ($used[(int) $productId] ?? 0));

            if ((int) $pieces <= $remaining) {
                continue;
            }

            throw ValidationException::withMessages([
                'limit' => $remaining > 0
                    ? "Sisa kuota produk ini tahun ini {$remaining} pcs (maks. {$cap} pcs per SKU per tahun)."
                    : "Kuota produk ini tahun ini sudah habis ({$cap} pcs). Kuota reset otomatis {$this->resetsLabel()}.",
            ]);
        }
    }

    /**
     * @return Builder<ClaimItem>
     */
    private function purchases(User $user, ?int $year): Builder
    {
        $year ??= $this->currentYear();

        return ClaimItem::query()
            ->join('claims', 'claims.id', '=', 'claim_items.claim_id')
            ->where('claims.user_id', $user->id)
            ->where('claims.status', '!=', 'dibatalkan')
            ->where('claims.submitted_at', '>=', $this->resetsOn($year - 1)->utc())
            ->where('claims.submitted_at', '<', $this->resetsOn($year)->utc());
    }
}
