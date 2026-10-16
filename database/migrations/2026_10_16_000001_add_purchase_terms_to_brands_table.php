<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->unsignedTinyInteger('discount_percent')->default(30);
            $table->unsignedInteger('shipping_fee')->default(0);
            $table->string('company_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();
        });

        $terms = [
            'AB' => [40, 'PT Citra Pasifik Kosmetik'],
            'FTI' => [30, 'PT Inovasi Alam Nusantara'],
            'K+A' => [30, 'PT Kinerja Inovasi Nusantara'],
        ];

        foreach ($terms as $short => [$discount, $company]) {
            DB::table('brands')->where('short', $short)->update([
                'discount_percent' => $discount,
                'shipping_fee' => 20000,
                'company_name' => $company,
                'bank_account_name' => $company,
            ]);
        }

        Schema::table('claim_periods', function (Blueprint $table) {
            $table->dropColumn('discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('claim_periods', function (Blueprint $table) {
            $table->unsignedTinyInteger('discount_percent')->default(30);
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn([
                'discount_percent',
                'shipping_fee',
                'company_name',
                'bank_name',
                'bank_account_number',
                'bank_account_name',
            ]);
        });
    }
};
