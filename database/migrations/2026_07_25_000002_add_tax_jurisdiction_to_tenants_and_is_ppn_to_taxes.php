<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('tax_jurisdiction', 10)->nullable()->after('ppn_rate');
        });

        Schema::table('taxes', function (Blueprint $table) {
            $table->boolean('is_ppn')->default(false)->after('is_default');
        });

        DB::table('taxes')
            ->where('name', 'LIKE', '%PPN%')
            ->update(['is_ppn' => true]);
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('tax_jurisdiction');
        });

        Schema::table('taxes', function (Blueprint $table) {
            $table->dropColumn('is_ppn');
        });
    }
};
