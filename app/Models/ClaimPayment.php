<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'claim_id',
    'declared_amount',
    'verified_amount',
    'proof_path',
    'proof_name',
    'status',
    'note',
    'verified_by',
    'verified_at',
])]
class ClaimPayment extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'declared_amount' => 'integer',
            'verified_amount' => 'integer',
            'verified_at' => 'datetime',
        ];
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }
}
