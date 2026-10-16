<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->nullable()->unique();
            $table->unsignedTinyInteger('discount_percent')->default(0);
            $table->string('fulfillment', 16)->default('pickup');
            $table->text('shipping_address')->nullable();
            $table->unsignedInteger('shipping_fee')->default(0);
            $table->unsignedInteger('total_due')->default(0);
            $table->unsignedInteger('paid_total')->default(0);
            $table->unsignedInteger('refund_total')->default(0);
            $table->string('tracking_number')->nullable();
            $table->timestamp('invoice_issued_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('completed_at')->nullable();
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->string('status')->default('cek_stok')->change();
        });

        $statuses = [
            'diajukan' => 'cek_stok',
            'diproses' => 'menunggu_pembayaran',
            'disetujui' => 'disiapkan',
            'ditolak' => 'dibatalkan',
        ];
        $sequences = [];

        foreach (DB::table('claims')->orderBy('id')->get() as $claim) {
            $brand = DB::table('claim_items')
                ->join('products', 'products.id', '=', 'claim_items.product_id')
                ->join('brands', 'brands.id', '=', 'products.brand_id')
                ->where('claim_items.claim_id', $claim->id)
                ->select('brands.id', 'brands.short')
                ->first();
            $status = $statuses[$claim->status] ?? $claim->status;
            $submittedAt = Carbon::parse($claim->submitted_at);
            $code = preg_replace('/[^A-Z0-9]/', '', strtoupper($brand->short ?? 'PC'));
            $sequenceKey = $code.$submittedAt->format('Ym');
            $sequences[$sequenceKey] = ($sequences[$sequenceKey] ?? 0) + 1;
            $discount = $claim->retail_total > 0
                ? (int) round(100 - ($claim->claim_total * 100 / $claim->retail_total))
                : 0;

            DB::table('claims')->where('id', $claim->id)->update([
                'brand_id' => $brand?->id,
                'invoice_number' => sprintf('INV/%s/%s/%04d', $code, $submittedAt->format('Ym'), $sequences[$sequenceKey]),
                'status' => $status,
                'discount_percent' => $discount,
                'total_due' => $claim->claim_total,
                'paid_total' => in_array($status, ['disiapkan', 'selesai'], true) ? $claim->claim_total : 0,
                'invoice_issued_at' => in_array($status, ['cek_stok', 'dibatalkan'], true) ? null : $claim->submitted_at,
                'completed_at' => $status === 'selesai' ? $claim->updated_at : null,
            ]);
        }
    }

    public function down(): void
    {
        $statuses = [
            'cek_stok' => 'diajukan',
            'stok_kosong' => 'diajukan',
            'menunggu_pembayaran' => 'diproses',
            'kurang_bayar' => 'diproses',
            'verifikasi_pembayaran' => 'diproses',
            'disiapkan' => 'disetujui',
            'dikirim' => 'disetujui',
            'siap_diambil' => 'disetujui',
            'dibatalkan' => 'ditolak',
        ];

        foreach ($statuses as $from => $to) {
            DB::table('claims')->where('status', $from)->update(['status' => $to]);
        }

        Schema::table('claims', function (Blueprint $table) {
            $table->string('status')->default('diajukan')->change();
            $table->dropConstrainedForeignId('brand_id');
            $table->dropUnique(['invoice_number']);
            $table->dropColumn([
                'invoice_number',
                'discount_percent',
                'fulfillment',
                'shipping_address',
                'shipping_fee',
                'total_due',
                'paid_total',
                'refund_total',
                'tracking_number',
                'invoice_issued_at',
                'refunded_at',
                'completed_at',
            ]);
        });
    }
};
