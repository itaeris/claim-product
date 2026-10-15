<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('claims')->where('status', 'submitted')->update(['status' => 'diajukan']);
    }

    public function down(): void
    {
        DB::table('claims')->where('status', 'diajukan')->update(['status' => 'submitted']);
    }
};
