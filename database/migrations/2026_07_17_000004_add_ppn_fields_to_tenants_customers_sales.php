<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add PKP / PPN fields to tenants table
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('npwp', 20)->nullable()->after('domain');
            $table->boolean('pkp_status')->default(false)->after('npwp');
            $table->date('pkp_date')->nullable()->after('pkp_status');
            $table->decimal('ppn_rate', 5, 2)->default(11.00)->after('pkp_date');
            // Phase 2 fields (Klikpajak PJAP) — stored here so Phase 2 migration is just a settings form
            $table->string('klikpajak_api_key')->nullable()->after('ppn_rate');
            $table->string('klikpajak_secret')->nullable()->after('klikpajak_api_key');
            $table->boolean('klikpajak_use_sandbox')->default(true)->after('klikpajak_secret');
        });

        // Add NPWP / PKP fields to customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->string('npwp', 20)->nullable()->after('email');
            $table->boolean('pkp_status')->default(false)->after('npwp');
            $table->date('pkp_date')->nullable()->after('pkp_status');
            // Address as registered with DJP (may differ from operational address)
            $table->text('alamat_npwp')->nullable()->after('pkp_date');
        });

        // Add PPN fields to sales table
        // Note: existing tax_rate/total_tax stays for generic sales tax.
        // PPN (DJP) is a separate, independent field.
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('ppn_enabled')->default(false)->after('total_tax');
            $table->decimal('ppn_rate', 5, 2)->nullable()->after('ppn_enabled');
            $table->decimal('ppn_amount', 15, 2)->default(0)->after('ppn_rate');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['npwp', 'pkp_status', 'pkp_date', 'ppn_rate', 'klikpajak_api_key', 'klikpajak_secret', 'klikpajak_use_sandbox']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['npwp', 'pkp_status', 'pkp_date', 'alamat_npwp']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['ppn_enabled', 'ppn_rate', 'ppn_amount']);
        });
    }
};
