<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->string('sourceable_type')->nullable()->change();
            $table->unsignedBigInteger('sourceable_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->string('sourceable_type')->nullable(false)->change();
            $table->unsignedBigInteger('sourceable_id')->nullable(false)->change();
        });
    }
};
