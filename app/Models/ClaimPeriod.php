<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'starts_on', 'ends_on', 'discount_percent'])]
class ClaimPeriod extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'discount_percent' => 'integer',
        ];
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }

    public static function current(): self
    {
        $today = now()->toDateString();

        return static::query()
            ->whereDate('starts_on', '<=', $today)
            ->whereDate('ends_on', '>=', $today)
            ->orderBy('starts_on')
            ->first()
            ?? static::query()->orderByDesc('ends_on')->firstOrFail();
    }
}
