<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bundles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('license_id')->constrained()->restrictOnDelete(); // every item in the bundle gets this license
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('price_cents');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bundle_product', function (Blueprint $table) {
            $table->foreignId('bundle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['bundle_id', 'product_id']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->change();
            $table->foreignId('license_id')->nullable()->change();
            $table->foreignId('bundle_id')->nullable()->after('license_id')->constrained()->cascadeOnDelete();
            $table->unique(['cart_id', 'bundle_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('bundle_id')->nullable()->after('license_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $table) => $table->dropConstrainedForeignId('bundle_id'));
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['cart_id', 'bundle_id']);
            $table->dropConstrainedForeignId('bundle_id');
        });
        Schema::dropIfExists('bundle_product');
        Schema::dropIfExists('bundles');
    }
};
