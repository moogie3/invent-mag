<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remote-first Shopee covers: the CDN URL is stored so the UI can
     * render immediately; products.image fills in once the background
     * download has cached the file locally.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('cover_url')
                ->nullable()
                ->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('cover_url');
        });
    }
};
