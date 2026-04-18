<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('reference_number');
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('Draft'); // Draft, In Progress, Completed, Canceled
            $table->date('scheduled_date');
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Unique constraint on reference_number per tenant
            $table->unique(['tenant_id', 'reference_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opnames');
    }
};