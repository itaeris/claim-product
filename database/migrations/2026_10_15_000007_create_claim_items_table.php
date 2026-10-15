<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claim_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name');
            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('unit_retail_price');
            $table->unsignedInteger('unit_claim_price');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_items');
    }
};
