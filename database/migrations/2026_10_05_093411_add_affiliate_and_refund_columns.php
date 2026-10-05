<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('affiliate_code', 16)->nullable()->unique();
            $table->string('affiliate_paypal_email')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspension_reason')->nullable();
        });

        Schema::table('order_items', function (Blueprint $table) {
            // Affiliate commission is paid by the platform out of its own fee.
            $table->foreignId('affiliate_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('affiliate_commission_cents')->default(0);
            $table->timestamp('affiliate_paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->string('refund_ref', 64)->nullable();

            $table->index(['affiliate_id', 'affiliate_paid_at']);
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['affiliate_id', 'affiliate_paid_at']);
            $table->dropConstrainedForeignId('affiliate_id');
            $table->dropColumn(['affiliate_commission_cents', 'affiliate_paid_at', 'refunded_at', 'refund_ref']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['affiliate_code']);
            $table->dropColumn(['affiliate_code', 'affiliate_paypal_email', 'suspended_at', 'suspension_reason']);
        });
    }
};
