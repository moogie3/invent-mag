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
        Schema::create('po', function (Blueprint $table) {
            $table->id();
            $table->string('invoice');
            $table->integer('supplier_id');
            $table->date('order_date');
            $table->integer('due_date');
            $table->enum('payment_type', ['Cash','Transfer']);
            $table->integer('product_id');
            $table->enum('status', ['Belum Jatuh Tempo', 'Jatuh Tempo' , '-']);
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
