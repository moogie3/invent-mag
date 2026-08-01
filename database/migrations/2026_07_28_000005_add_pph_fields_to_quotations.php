<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->boolean('pph_enabled')->default(false)->after('total');
            $table->decimal('pph_rate', 5, 2)->nullable()->after('pph_enabled');
            $table->decimal('pph_amount', 15, 2)->default(0)->after('pph_rate');
            $table->foreignId('pph_tax_id')->nullable()->after('pph_amount')->constrained('taxes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropForeign(['pph_tax_id']);
            $table->dropColumn(['pph_enabled', 'pph_rate', 'pph_amount', 'pph_tax_id']);
        });
    }
};
