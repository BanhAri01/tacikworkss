<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('buyer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('subtotal_cents');   // list prices
            $table->unsignedInteger('discount_cents');
            $table->unsignedInteger('buyer_fee_cents');  // processing fee paid by buyer
            $table->unsignedInteger('total_cents');      // charged to buyer
            $table->string('status')->default('pending')->index();
            $table->string('gateway', 20);
            $table->string('gateway_ref', 64)->nullable()->unique(); // PayPal order id
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('license_id')->constrained()->restrictOnDelete();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->string('product_title');
            $table->string('license_name');
            $table->unsignedInteger('list_price_cents');
            $table->unsignedInteger('discount_cents')->default(0);
            $table->unsignedInteger('price_cents');           // list − discount
            $table->unsignedInteger('buyer_fee_cents')->default(0);
            $table->unsignedInteger('platform_fee_cents')->default(0);
            $table->unsignedInteger('payment_fee_cents')->default(0); // actual PayPal fee share
            $table->integer('seller_net_cents')->default(0);          // price + buyer fee − PayPal fee − platform fee
            $table->unsignedSmallInteger('fee_bps')->default(0);
            $table->string('attribution', 20);
            $table->uuid('purchase_code')->unique();
            $table->string('gateway_capture_id', 64)->nullable()->index();
            $table->timestamps();

            $table->index(['seller_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
