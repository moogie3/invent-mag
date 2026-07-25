<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faktur_pajak_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();  // BelongsToTenant required
            $table->foreignId('faktur_pajak_id')->constrained('faktur_pajak')->cascadeOnDelete();

            $table->string('nama_barang');                    // product/service name
            $table->decimal('harga_satuan', 15, 2);
            $table->decimal('jumlah_barang', 15, 4);
            $table->decimal('harga_total', 15, 2);            // harga_satuan * jumlah_barang
            $table->decimal('diskon', 15, 2)->default(0);
            $table->decimal('dpp', 15, 2);                    // harga_total - diskon
            $table->decimal('ppn', 15, 2);

            // BKP/JKP classification code per DJP spec
            // 01 = BKP (Barang Kena Pajak), 04 = JKP (Jasa Kena Pajak)
            $table->string('kode_objek_pajak', 10)->default('01');

            $table->timestamps();

            $table->index('faktur_pajak_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faktur_pajak_items');
    }
};
