<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\PlatformCurrencySetting;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_currency_settings', function (Blueprint $table) {
            $table->id();
            $table->string('currency_symbol')->default('$');
            $table->string('decimal_separator')->default('.');
            $table->string('thousand_separator')->default(',');
            $table->integer('decimal_places')->default(2);
            $table->string('position')->default('prefix');
            $table->string('currency_code')->default('USD');
            $table->timestamps();
        });

        PlatformCurrencySetting::create([
            'currency_symbol' => '$',
            'decimal_separator' => '.',
            'thousand_separator' => ',',
            'decimal_places' => 2,
            'position' => 'prefix',
            'currency_code' => 'USD',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_currency_settings');
    }
};
