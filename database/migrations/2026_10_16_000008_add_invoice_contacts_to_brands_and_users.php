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
            $table->string('contact_phone', 40)->nullable()->after('company_address');
            $table->string('contact_email')->nullable()->after('contact_phone');
            $table->string('website')->nullable()->after('contact_email');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('email');
        });

        DB::table('brands')->where('short', 'FTI')->update([
            'contact_phone' => '0851-7511-4967',
            'contact_email' => 'help@fromthisisland.com',
            'website' => 'www.fromthisisland.com',
        ]);

        DB::table('brands')->where('short', 'FTI')->whereNull('bank_account_number')->update([
            'bank_name' => 'BCA',
            'bank_account_number' => '0062775775',
            'bank_account_name' => 'Inovasi Alam Nusantara',
        ]);
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn(['contact_phone', 'contact_email', 'website']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
