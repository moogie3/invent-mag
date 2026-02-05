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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('barcode')->unique()->nullable();
            $table->string('name');
            $table->index('name');
            // $table->integer('stock_quantity'); // Removed for multi-warehouse support
            $table->integer('low_stock_threshold')->nullable();
            $table->decimal('price', 15, 2);
            $table->decimal('selling_price', 15, 2);
            $table->integer('category_id');
            $table->integer('units_id');
            $table->integer('supplier_id');
            $table->text('description')->nullable();
            // $table->integer('warehouse_id'); // Removed for multi-warehouse support
            $table->string('image')->nullable();
            $table->boolean('has_expiry')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
