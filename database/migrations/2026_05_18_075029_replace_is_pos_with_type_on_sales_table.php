<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // We use string because it's more flexible than an enum and standard in modern Laravel.
            $table->string('type', 20)->default('regular')->after('status');
        });

        // Migrate existing data
        DB::table('sales')->where('is_pos', 1)->update(['type' => 'pos']);
        DB::table('sales')->where('is_pos', 0)->update(['type' => 'regular']);

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('is_pos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('is_pos')->default(false)->after('change_amount');
        });

        DB::table('sales')->where('type', 'pos')->update(['is_pos' => 1]);
        DB::table('sales')->where('type', '!=', 'pos')->update(['is_pos' => 0]);

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
