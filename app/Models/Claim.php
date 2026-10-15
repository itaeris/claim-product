<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'claim_period_id',
    'number',
    'status',
    'retail_total',
    'saving_total',
    'claim_total',
    'submitted_at',
])]
class Claim extends Model
{
    public const STATUSES = ['diajukan', 'diproses', 'disetujui', 'ditolak', 'selesai'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'retail_total' => 'integer',
            'saving_total' => 'integer',
            'claim_total' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(ClaimPeriod::class, 'claim_period_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ClaimItem::class);
    }
}
