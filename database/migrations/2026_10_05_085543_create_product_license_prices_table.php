<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_license_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('license_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('price_cents');
            $table->timestamps();

            $table->unique(['product_id', 'license_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_license_prices');
    }
};
