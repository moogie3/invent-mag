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
            $table->string('customer_id')->constrained()->cascadeOnDelete();
            $table->timestamp('order_date');
            $table->timestamp('due_date')->nullable();
            $table->enum('payment_type', ['Cash','Transfer','-']);
            $table->decimal('order_discount', 10, 2)->default(0);
            $table->enum('order_discount_type', ['percentage', 'fixed'])->default('fixed');
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->decimal('total_tax',10,2)->nullable();
            $table->double('total');
            $table->enum('status', ['Unpaid', 'Paid']);
            $table->timestamp('payment_date')->nullable();
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
