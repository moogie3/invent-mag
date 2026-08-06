<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('po', function (Blueprint $table) {
            $table->boolean('ppn_enabled')->default(false)->after('pph_tax_id');
            $table->decimal('ppn_rate', 5, 2)->nullable()->after('ppn_enabled');
            $table->decimal('ppn_amount', 15, 2)->default(0)->after('ppn_rate');
        });
    }

    public function down(): void
    {
        Schema::table('po', function (Blueprint $table) {
            $table->dropColumn(['ppn_enabled', 'ppn_rate', 'ppn_amount']);
        });
    }
};
