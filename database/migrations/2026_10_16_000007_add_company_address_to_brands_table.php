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
            $table->text('company_address')->nullable()->after('company_name');
        });

        DB::table('brands')->where('short', 'AB')->whereNull('company_address')->update([
            'company_address' => "Jl. DI Panjaitan No. 138, Kec. Jatinegara\nJakarta Timur 13410\nDKI Jakarta",
        ]);

        DB::table('brands')->where('short', 'AB')->whereNull('bank_account_number')->update([
            'bank_name' => 'BCA',
            'bank_account_number' => '065-373-6662',
        ]);
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn('company_address');
        });
    }
};
