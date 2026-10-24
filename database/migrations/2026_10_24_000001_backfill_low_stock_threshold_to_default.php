<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Products created before the low-stock fix (e.g. Shopee imports) carry a
     * NULL threshold. Normalize them to the app-wide default so every count,
     * modal and export shows the same "10" the UI already falls back to.
     */
    public function up(): void
    {
        DB::table('products')
            ->whereNull('low_stock_threshold')
            ->update(['low_stock_threshold' => 10]);
    }

    public function down(): void
    {
        DB::table('products')
            ->where('low_stock_threshold', 10)
            ->update(['low_stock_threshold' => null]);
    }
};
