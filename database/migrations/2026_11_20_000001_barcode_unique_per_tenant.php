<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Barcodes are unique within a tenant, not across all of them.
 *
 * The original index was global, so two tenants could never carry the
 * same EAN even though their catalogues never meet: tenant B's product
 * creation died on tenant A's row with a database error the store path
 * could only report as "Error creating product", while the update rule
 * rejected the edit with a false "already taken". The validation rules
 * now scope the same way (Rule::unique(...)->where('tenant_id', ...)),
 * so index and rules finally agree.
 *
 * Existing data needs no cleanup: a global unique index means every
 * barcode currently stored is already distinct, which satisfies the
 * narrower per-tenant index as well. Products without a barcode keep
 * multiple NULLs, which a composite unique index allows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_barcode_unique');
            $table->unique(['tenant_id', 'barcode']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'barcode']);
            $table->unique('barcode');
        });
    }
};
