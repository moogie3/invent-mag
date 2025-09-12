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
            $table->string('code');
            $table->string('name');
            $table->integer('stock_quantity');
            $table->integer('low_stock_threshold')->nullable();
            $table->double('price');
            $table->double('selling_price');
            $table->integer('category_id');
            $table->integer('units_id');
            $table->integer('supplier_id');
            $table->text('description');
            $table->integer('warehouse_id');
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
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('products');
    }
};
