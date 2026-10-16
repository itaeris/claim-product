<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'claim_period_id',
    'brand_id',
    'number',
    'invoice_number',
    'status',
    'discount_percent',
    'fulfillment',
    'shipping_address',
    'shipping_fee',
    'retail_total',
    'saving_total',
    'claim_total',
    'total_due',
    'paid_total',
    'refund_total',
    'tracking_number',
    'submitted_at',
    'invoice_issued_at',
    'refunded_at',
    'completed_at',
])]
class Claim extends Model
{
    public const LABELS = [
        'cek_stok' => 'Cek stok',
        'stok_kosong' => 'Stok kosong',
        'menunggu_pembayaran' => 'Menunggu pembayaran',
        'kurang_bayar' => 'Kurang bayar',
        'verifikasi_pembayaran' => 'Verifikasi pembayaran',
        'disiapkan' => 'Disiapkan gudang',
        'dikirim' => 'Dikirim',
        'siap_diambil' => 'Siap diambil',
        'selesai' => 'Selesai',
        'dibatalkan' => 'Dibatalkan',
    ];

    public const AWAITING_PAYMENT = ['menunggu_pembayaran', 'kurang_bayar'];

    public const FULFILLMENTS = ['pickup', 'delivery'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discount_percent' => 'integer',
            'shipping_fee' => 'integer',
            'retail_total' => 'integer',
            'saving_total' => 'integer',
            'claim_total' => 'integer',
            'total_due' => 'integer',
            'paid_total' => 'integer',
            'refund_total' => 'integer',
            'submitted_at' => 'datetime',
            'invoice_issued_at' => 'datetime',
            'refunded_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(ClaimPeriod::class, 'claim_period_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ClaimItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ClaimPayment::class)->orderBy('id');
    }

    public function remainingDue(): int
    {
        return max(0, $this->total_due - $this->paid_total);
    }

    public function statusLabel(): string
    {
        return self::LABELS[$this->status] ?? $this->status;
    }

    public function isDelivery(): bool
    {
        return $this->fulfillment === 'delivery';
    }
}
