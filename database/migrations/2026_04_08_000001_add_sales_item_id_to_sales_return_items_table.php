<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_return_items', function (Blueprint $table) {
            $table->foreignId('sales_item_id')
                ->nullable()
                ->constrained('sales_items')
                ->onDelete('cascade')
                ->after('sales_return_id');
        });
    }

    public function down(): void
    {
        Schema::table('sales_return_items', function (Blueprint $table) {
            $table->dropForeign(['sales_item_id']);
            $table->dropColumn('sales_item_id');
        });
    }
};
