<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description');
            $table->string('status')->default('draft')->index();
            $table->text('rejection_reason')->nullable();
            $table->json('file_formats')->nullable();
            $table->json('compatible_software')->nullable();
            $table->boolean('is_free')->default(false)->index();
            $table->timestamp('featured_at')->nullable()->index();
            $table->unsignedInteger('sales_count')->default(0);
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->string('version', 20)->default('1.0');
            // Lowest license price, denormalised for sorting/filtering.
            $table->unsignedInteger('min_price_cents')->default(0)->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
