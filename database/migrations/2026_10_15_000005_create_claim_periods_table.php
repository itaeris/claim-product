<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claim_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->unsignedTinyInteger('discount_percent');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_periods');
    }
};
