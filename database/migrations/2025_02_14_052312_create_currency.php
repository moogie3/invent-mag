<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('currency_settings', function (Blueprint $table) {
            $table->id();
            $table->string('currency_symbol')->default('Rp');
            $table->string('decimal_separator')->default(',');
            $table->string('thousand_separator')->default('.');
            $table->integer('decimal_places')->default(0);
            $table->timestamps();
        });

        // Insert default settings
        DB::table('currency_settings')->insert([
            'currency_symbol' => 'Rp',
            'decimal_separator' => ',',
            'thousand_separator' => '.',
            'decimal_places' => 0,
        ]);
    }

    public function down() {
        Schema::dropIfExists('currency_settings');
    }
};
