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
        Schema::table('sales', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('total');
            $table->text('terms_conditions')->nullable()->after('notes');
        });

        Schema::table('sales_opportunities', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('description');
            $table->text('terms_conditions')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['notes', 'terms_conditions']);
        });

        Schema::table('sales_opportunities', function (Blueprint $table) {
            $table->dropColumn(['notes', 'terms_conditions']);
        });
    }
};
