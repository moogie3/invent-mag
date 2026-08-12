<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the 'voided' status to subscription_orders.
     *
     * The super admin can void an order created in error (BillingController),
     * and terminating a subscription voids all pending orders
     * (SubscriptionController). Both write 'voided', which the original enum
     * did not include — so any void attempt failed against the column.
     */
    public function up(): void
    {
        Schema::table('subscription_orders', function (Blueprint $table) {
            $table->enum('status', ['pending', 'paid', 'failed', 'expired', 'canceled', 'voided'])->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('subscription_orders', function (Blueprint $table) {
            $table->enum('status', ['pending', 'paid', 'failed', 'expired', 'canceled'])->default('pending')->change();
        });
    }
};
