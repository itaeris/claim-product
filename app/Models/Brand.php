<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'short',
    'subtitle',
    'sort_order',
    'discount_percent',
    'shipping_fee',
    'company_name',
    'company_address',
    'bank_name',
    'bank_account_number',
    'bank_account_name',
])]
class Brand extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discount_percent' => 'integer',
            'shipping_fee' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }

    public function invoiceCode(): string
    {
        $code = preg_replace('/[^A-Z0-9]/', '', strtoupper($this->short));

        return $code !== '' ? $code : 'PC';
    }
}
