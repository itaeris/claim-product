<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('claim_period_id')->constrained()->restrictOnDelete();
            $table->string('number')->unique();
            $table->string('status')->default('diajukan');
            $table->unsignedInteger('retail_total');
            $table->unsignedInteger('saving_total');
            $table->unsignedInteger('claim_total');
            $table->timestamp('submitted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
