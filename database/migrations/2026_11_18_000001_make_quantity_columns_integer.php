<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The last six float columns in the schema, and every one of them holds a
 * quantity. Everything else that counts units is already an integer, and
 * the opname counts are validated as integers, so a float here only ever
 * disagreed with the rest of the app: the item row truncated 2.5 to 2
 * while the stock move used the raw 2.5.
 *
 * No money moves and no journal changes: these columns never feed a
 * ledger line. Live data was checked first and holds no fractional value
 * in any of the six columns, so the conversion rounds nothing away.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->integer('quantity_before')->change();
            $table->integer('quantity_after')->change();
            $table->integer('adjustment_amount')->change();
        });

        Schema::table('stock_opname_items', function (Blueprint $table) {
            $table->integer('expected_qty')->default(0)->change();
            $table->integer('counted_qty')->nullable()->change();
            $table->integer('variance')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->float('quantity_before')->change();
            $table->float('quantity_after')->change();
            $table->float('adjustment_amount')->change();
        });

        Schema::table('stock_opname_items', function (Blueprint $table) {
            $table->float('expected_qty')->default(0)->change();
            $table->float('counted_qty')->nullable()->change();
            $table->float('variance')->nullable()->change();
        });
    }
};
