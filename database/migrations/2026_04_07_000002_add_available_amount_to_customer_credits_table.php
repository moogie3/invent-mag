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
        Schema::table('customer_credits', function (Blueprint $table) {
            $table->decimal('available_amount', 15, 2)->nullable()->after('used_amount')->comment('Cached available amount (amount - used_amount)');
        });

        // Populate existing records
        \DB::table('customer_credits')->update([
            'available_amount' => \DB::raw('amount - used_amount')
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_credits', function (Blueprint $table) {
            $table->dropColumn('available_amount');
        });
    }
};
