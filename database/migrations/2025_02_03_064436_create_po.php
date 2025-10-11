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
            $table->string('invoice');
            $table->integer('supplier_id');
            $table->timestamp('order_date')->useCurrent();
            $table->timestamp('due_date')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->enum('payment_type', ['Cash', 'Card', 'Transfer', 'eWallet', '-'])->default('-');
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->enum('discount_total_type', ['percentage', 'fixed'])->default('fixed');
            $table->float('total');
            $table->enum('status', ['Unpaid', 'Paid', 'Partial']);
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