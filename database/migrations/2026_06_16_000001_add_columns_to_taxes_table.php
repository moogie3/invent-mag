<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            // Tax application type:
            //   'exclusive' = tax added on top of subtotal (most common, e.g. US sales tax)
            //   'inclusive' = tax is already contained within the price (e.g. Indonesian PPN Inclusive)
            //   'none'      = zero-rated or tax-exempt — tax_amount is always 0
            $table->string('type', 20)->default('exclusive')->after('rate')
                ->comment('exclusive | inclusive | none');

            $table->text('description')->nullable()->after('type');

            // Only one tax per tenant can be the default.
            // The default tax is pre-selected on new sales, purchases, and quotation forms.
            $table->boolean('is_default')->default(false)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            $table->dropColumn(['type', 'description', 'is_default']);
        });
    }
};
