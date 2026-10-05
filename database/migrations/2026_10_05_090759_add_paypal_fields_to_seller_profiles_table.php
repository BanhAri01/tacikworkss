<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->string('paypal_merchant_id', 32)->nullable()->unique()->after('paypal_email');
            $table->boolean('paypal_payments_receivable')->default(false)->after('paypal_merchant_id');
            $table->boolean('paypal_email_confirmed')->default(false)->after('paypal_payments_receivable');
            $table->timestamp('paypal_connected_at')->nullable()->after('paypal_email_confirmed');
        });
    }

    public function down(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->dropUnique(['paypal_merchant_id']);
            $table->dropColumn(['paypal_merchant_id', 'paypal_payments_receivable', 'paypal_email_confirmed', 'paypal_connected_at']);
        });
    }
};
