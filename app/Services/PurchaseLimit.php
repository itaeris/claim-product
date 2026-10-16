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
     * Pieces bought in the calendar year (WIB) across every brand, excluding cancelled orders.
     */
    public function used(User $user, ?int $year = null): int
    {
        return (int) $this->purchases($user, $year)->sum('claim_items.quantity');
    }

    public function remaining(User $user): int
    {
        return max(0, $this->yearly() - $this->used($user));
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
     * @return array{total: int, used: int, remaining: int, year: int, resets_label: string}
     */
    public function status(User $user): array
    {
        $used = $this->used($user);

        return [
            'total' => $this->yearly(),
            'used' => $used,
            'remaining' => max(0, $this->yearly() - $used),
            'year' => $this->currentYear(),
            'resets_label' => $this->resetsLabel(),
        ];
    }

    public function ensureWithin(User $user, int $pieces): void
    {
        $remaining = $this->remaining($user);

        if ($pieces <= $remaining) {
            return;
        }

        throw ValidationException::withMessages([
            'limit' => $remaining > 0
                ? "Sisa kuota pembelian tahun ini {$remaining} pcs (maks. {$this->yearly()} pcs per tahun untuk semua brand)."
                : "Kuota pembelian tahun ini sudah habis ({$this->yearly()} pcs). Kuota reset otomatis {$this->resetsLabel()}.",
        ]);
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
