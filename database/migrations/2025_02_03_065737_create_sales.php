<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice')->unique();
            $table->string('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('order_date')->useCurrent();
            $table->timestamp('due_date')->nullable();
            $table->enum('payment_type', ['Cash', 'Card', 'Transfer', 'eWallet', '-'])->default('-');
            $table->decimal('order_discount', 10, 2)->default(0);
            $table->enum('order_discount_type', ['percentage', 'fixed'])->default('fixed');
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->decimal('total_tax', 10, 2)->nullable();
            $table->double('total');
            $table->enum('status', ['Unpaid', 'Paid', 'Partial'])->default('Unpaid');
            $table->decimal('amount_received', 10, 2)->nullable();
            $table->decimal('change_amount', 10, 2)->nullable();
            $table->boolean('is_pos')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};