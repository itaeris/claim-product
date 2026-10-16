<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\ClaimItem;
use App\Models\ClaimPayment;
use App\Support\IndonesianDate;

class ClaimPresenter
{
    public const RELATIONS = ['items.product', 'brand', 'payments'];

    /**
     * @return array<string, mixed>
     */
    public function present(Claim $claim, bool $withUser = false): array
    {
        $brand = $claim->brand;
        $invoiceIssued = $claim->invoice_issued_at !== null;
        $payload = [
            'id' => $claim->id,
            'number' => $claim->number,
            'invoice_number' => $claim->invoice_number,
            'status' => $claim->status,
            'status_label' => $claim->statusLabel(),
            'brand' => [
                'id' => $brand?->id,
                'name' => $brand?->name,
                'short' => $brand?->short,
                'company_name' => $brand?->company_name,
            ],
            'fulfillment' => $claim->fulfillment,
            'shipping_address' => $claim->shipping_address,
            'pickup_address' => config('claimly.warehouse_address'),
            'tracking_number' => $claim->tracking_number,
            'discount_percent' => $claim->discount_percent,
            'retail_total' => $claim->retail_total,
            'saving_total' => $claim->saving_total,
            'claim_total' => $claim->claim_total,
            'shipping_fee' => $claim->shipping_fee,
            'total_due' => $claim->total_due,
            'paid_total' => $claim->paid_total,
            'remaining_due' => $claim->remainingDue(),
            'refund_total' => $claim->refund_total,
            'refunded' => $claim->refunded_at !== null,
            'submitted_label' => IndonesianDate::date($claim->submitted_at),
            'completed_label' => IndonesianDate::date($claim->completed_at),
            'invoice_url' => $invoiceIssued ? '/invoices/'.$claim->id : null,
            'bank' => $invoiceIssued && $brand !== null ? [
                'company_name' => $brand->company_name,
                'bank_name' => $brand->bank_name,
                'account_number' => $brand->bank_account_number,
                'account_name' => $brand->bank_account_name,
            ] : null,
            'items' => $claim->items->map(fn (ClaimItem $item) => [
                'product_id' => $item->product_id,
                'name' => $item->product_name,
                'quantity' => $item->quantity,
                'image_url' => $item->product?->image_url,
                'unit_retail_price' => $item->unit_retail_price,
                'unit_claim_price' => $item->unit_claim_price,
            ])->values()->all(),
            'payments' => $claim->payments->map(fn (ClaimPayment $payment) => [
                'id' => $payment->id,
                'declared_amount' => $payment->declared_amount,
                'verified_amount' => $payment->verified_amount,
                'status' => $payment->status,
                'note' => $payment->note,
                'uploaded_label' => IndonesianDate::dateTime($payment->created_at),
                'proof_url' => '/api/payments/'.$payment->id.'/proof',
                'proof_name' => $payment->proof_name,
            ])->values()->all(),
        ];

        if ($withUser) {
            $payload['user'] = [
                'name' => $claim->user?->name,
                'email' => $claim->user?->email,
                'team' => $claim->user?->team,
            ];
        }

        return $payload;
    }
}
