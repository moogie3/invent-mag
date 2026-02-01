<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            // Drop the existing unique constraint on name
            $table->dropUnique('accounts_name_unique');
            
            // Add a composite unique constraint on (name, tenant_id)
            // This allows the same account name (translation key) to exist for different tenants
            $table->unique(['name', 'tenant_id'], 'accounts_name_tenant_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            // Drop the composite unique constraint
            $table->dropUnique('accounts_name_tenant_unique');
            
            // Restore the original unique constraint on name only
            $table->unique('name', 'accounts_name_unique');
        });
    }
};
