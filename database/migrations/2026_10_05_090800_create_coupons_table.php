<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            // Money goes straight to the seller, so only a seller can fund a discount.
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->string('code', 40)->unique();
            $table->string('type', 10); // percent | fixed
            $table->unsignedInteger('value'); // percent: 1-100, fixed: cents
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
