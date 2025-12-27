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
        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_pipeline_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('position');
            $table->boolean('is_closed')->default(false); // e.g., Closed Won, Closed Lost
            $table->timestamps();
            $table->unique(['sales_pipeline_id', 'position']);
            $table->unique(['sales_pipeline_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pipeline_stages');
    }
};
