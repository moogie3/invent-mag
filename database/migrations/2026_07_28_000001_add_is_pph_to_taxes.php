<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            $table->boolean('is_pph')->default(false)->after('is_ppn');
        });

        DB::table('taxes')
            ->where('name', 'LIKE', '%PPh%')
            ->update(['is_pph' => true]);
    }

    public function down(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            $table->dropColumn('is_pph');
        });
    }
};
