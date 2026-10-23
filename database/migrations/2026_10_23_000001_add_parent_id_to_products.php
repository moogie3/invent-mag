<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Family grouping for Shopee-variant splits: variant #2..N point at
     * variant #1 (which keeps the parent Shopee SKU as its code).
     * Purely additive — nullable, so existing rows are unaffected.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
