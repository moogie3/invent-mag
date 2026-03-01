<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();           // starter, professional, enterprise
            $table->string('name');                      // Starter, Professional, Enterprise
            $table->decimal('price', 8, 2);              // 19.00, 49.00, 89.00
            $table->string('billing_cycle')->default('monthly'); // monthly
            $table->text('description')->nullable();
            $table->integer('max_users')->default(-1);   // -1 = unlimited
            $table->integer('max_warehouses')->default(-1);
            $table->json('features');                    // array of feature slugs
            $table->integer('trial_days')->default(0);   // 0 = no trial, 30 = 30 days
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
