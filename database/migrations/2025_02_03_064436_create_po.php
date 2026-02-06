<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('po', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('invoice');
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null')->after('supplier_id');
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete(); // Added for multi-warehouse support
            $table->timestamp('order_date')->useCurrent()->index();
            $table->timestamp('due_date')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->enum('payment_type', ['Cash', 'Card', 'Transfer', 'eWallet', '-'])->default('-');
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->enum('discount_total_type', ['percentage', 'fixed'])->default('fixed');
            $table->decimal('total', 15, 2);
            $table->enum('status', ['Unpaid', 'Paid', 'Partial', 'Returned'])->index();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('po');
    }
};