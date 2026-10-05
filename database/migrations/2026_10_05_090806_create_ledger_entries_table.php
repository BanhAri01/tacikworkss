<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Record of what each seller earned (funds are paid out by PayPal directly, never held here).
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->string('type', 20); // sale | refund | adjustment
            $table->integer('amount_cents');
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['seller_id', 'created_at']);
            $table->unique(['order_item_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
