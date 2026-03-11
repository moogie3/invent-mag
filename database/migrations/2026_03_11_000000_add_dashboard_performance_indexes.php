<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->index('customer_id');
            $table->index('due_date');
            $table->index(['status', 'due_date']);
        });

        Schema::table('po', function (Blueprint $table) {
            $table->index('supplier_id');
            $table->index('due_date');
            $table->index(['status', 'due_date']);
        });

        Schema::table('sales_items', function (Blueprint $table) {
            $table->index('product_id');
            $table->index(['sales_id', 'product_id']);
        });

        Schema::table('po_items', function (Blueprint $table) {
            $table->index('expiry_date');
            $table->index(['po_id', 'expiry_date']);
        });

        Schema::table('product_warehouse', function (Blueprint $table) {
            $table->index('quantity');
            $table->index(['product_id', 'quantity']);
            $table->index(['warehouse_id', 'quantity']);
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['sales_customer_id_index']);
            $table->dropIndex(['sales_due_date_index']);
            $table->dropIndex(['sales_status_due_date_index']);
        });

        Schema::table('po', function (Blueprint $table) {
            $table->dropIndex(['po_supplier_id_index']);
            $table->dropIndex(['po_due_date_index']);
            $table->dropIndex(['po_status_due_date_index']);
        });

        Schema::table('sales_items', function (Blueprint $table) {
            $table->dropIndex(['sales_items_product_id_index']);
            $table->dropIndex(['sales_items_sales_id_product_id_index']);
        });

        Schema::table('po_items', function (Blueprint $table) {
            $table->dropIndex(['po_items_expiry_date_index']);
            $table->dropIndex(['po_items_po_id_expiry_date_index']);
        });

        Schema::table('product_warehouse', function (Blueprint $table) {
            $table->dropIndex(['product_warehouse_quantity_index']);
            $table->dropIndex(['product_warehouse_product_id_quantity_index']);
            $table->dropIndex(['product_warehouse_warehouse_id_quantity_index']);
        });
    }
};
