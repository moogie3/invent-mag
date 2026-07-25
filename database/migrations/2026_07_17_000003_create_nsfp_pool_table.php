<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NSFP = Nomor Seri Faktur Pajak
     * DJP allocates these in blocks. They MUST be used sequentially and cannot be reused.
     * Tenants upload their NSFP allocation here; system assigns them FIFO.
     */
    public function up(): void
    {
        Schema::create('nsfp_pool', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('nsfp', 25)->unique();
            $table->enum('status', ['available', 'used', 'cancelled'])->default('available')->index();
            $table->timestamp('used_at')->nullable();
            $table->foreignId('faktur_pajak_id')->nullable()->constrained('faktur_pajak')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);  // fast NSFP allocation query
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nsfp_pool');
    }
};
