<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'product_id',
    'year',
    'extra_qty',
    'reason',
    'supervisor_name',
    'supervisor_email',
    'status',
    'review_note',
    'reviewed_by',
    'reviewed_at',
])]
class LimitRequest extends Model
{
    public const LABELS = [
        'menunggu' => 'Menunggu HR',
        'disetujui' => 'Disetujui',
        'ditolak' => 'Ditolak',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'extra_qty' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
