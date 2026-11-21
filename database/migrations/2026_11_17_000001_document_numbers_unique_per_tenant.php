<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Document numbers are per-tenant sequences: tenant A's INV-0001 and
     * tenant B's INV-0001 are different documents in different ledgers.
     * The global unique keys made whichever tenant reached that number
     * second fail on an integrity violation instead of filing its own
     * document (the accounts table already went through this same fix).
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_invoice_unique');
            $table->unique(['tenant_id', 'invoice'], 'sales_tenant_invoice_unique');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropUnique('quotations_quotation_number_unique');
            $table->unique(['tenant_id', 'quotation_number'], 'quotations_tenant_number_unique');
        });

        Schema::table('sales_pipelines', function (Blueprint $table) {
            $table->dropUnique('sales_pipelines_name_unique');
            $table->unique(['tenant_id', 'name'], 'sales_pipelines_tenant_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_tenant_invoice_unique');
            $table->unique('invoice', 'sales_invoice_unique');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropUnique('quotations_tenant_number_unique');
            $table->unique('quotation_number', 'quotations_quotation_number_unique');
        });

        Schema::table('sales_pipelines', function (Blueprint $table) {
            $table->dropUnique('sales_pipelines_tenant_name_unique');
            $table->unique('name', 'sales_pipelines_name_unique');
        });
    }
};
