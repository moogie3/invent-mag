<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faktur_pajak', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_id')->constrained('sales')->cascadeOnDelete();

            // NSFP — Nomor Seri Faktur Pajak (DJP-allocated, must be used sequentially)
            // Format: XXX.XXX-YY.XXXXXXXX  e.g. 010.000-26.00000001
            $table->string('nsfp', 25)->unique();

            // Tax period
            $table->date('tanggal_faktur');
            $table->unsignedTinyInteger('masa_pajak');    // 1–12
            $table->unsignedSmallInteger('tahun_pajak');

            // Penjual (seller) — snapshotted from tenant at creation time
            $table->string('npwp_penjual', 20);
            $table->string('nama_penjual');
            $table->text('alamat_penjual');

            // Pembeli (buyer) — snapshotted from customer at creation time
            $table->string('npwp_pembeli', 20)->nullable(); // null = non-PKP → 00.000.000.0-000.000
            $table->string('nama_pembeli');
            $table->text('alamat_pembeli');

            // Tax amounts (stored as actual values, not cents)
            $table->decimal('dpp', 15, 2);          // Dasar Pengenaan Pajak (taxable base, excl. PPN)
            $table->decimal('ppn', 15, 2);          // PPN amount = dpp * ppn_rate / 100
            $table->decimal('ppnbm', 15, 2)->default(0);  // PPnBM (luxury goods tax, usually 0)
            $table->decimal('ppn_rate', 5, 2)->default(11.00);  // stored for audit (rate may change)

            // Status lifecycle: draft → submitted → approved/rejected
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'amended', 'cancelled'])
                  ->default('draft')
                  ->index();

            // DJP / PJAP submission tracking (Phase 2 fields, nullable for Phase 1)
            $table->string('djp_reference_number', 50)->nullable();
            $table->timestamp('djp_submitted_at')->nullable();
            $table->timestamp('djp_response_at')->nullable();
            $table->json('djp_response_payload')->nullable();

            $table->string('pjap_submission_id', 100)->nullable();
            $table->string('pjap_status', 50)->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();

            // Faktur Pengganti (amendment) tracking
            $table->boolean('is_pengganti')->default(false);
            $table->foreignId('original_faktur_id')->nullable()->constrained('faktur_pajak')->nullOnDelete();

            $table->timestamps();

            $table->index(['tenant_id', 'masa_pajak', 'tahun_pajak']);  // SPT Masa PPN queries
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faktur_pajak');
    }
};
