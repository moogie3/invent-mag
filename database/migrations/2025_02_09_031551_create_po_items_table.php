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
        if (!Schema::hasTable('po_items')) {
            Schema::create('po_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('po_id');
                $table->unsignedBigInteger('product_id');
                $table->integer('quantity');
                $table->decimal('price', 10);
                $table->decimal('discount', 10)->nullable();
                $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
                $table->decimal('total', 10);
                $table->timestamps();

                // Define foreign key constraints
                $table->foreign('po_id')->references('id')->on('po')->onDelete('cascade');
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            });
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('po_items');
    }
};