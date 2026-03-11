<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->index('payment_date');
            $table->index(['paymentable_id', 'paymentable_type']);
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->index('date');
            $table->index(['sourceable_id', 'sourceable_type']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->index('location');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index('customer_type');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index('parent_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('category_id');
            $table->index('supplier_id');
            $table->index('selling_price');
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->index('is_main');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['payments_payment_date_index']);
            $table->dropIndex(['payments_paymentable_id_paymentable_type_index']);
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropIndex(['journal_entries_date_index']);
            $table->dropIndex(['journal_entries_sourceable_id_sourceable_type_index']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex(['suppliers_location_index']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['customers_customer_type_index']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['categories_parent_id_index']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['products_category_id_index']);
            $table->dropIndex(['products_supplier_id_index']);
            $table->dropIndex(['products_selling_price_index']);
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropIndex(['warehouses_is_main_index']);
        });
    }
};
