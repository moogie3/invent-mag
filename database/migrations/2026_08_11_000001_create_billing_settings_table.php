<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Singleton row holding the platform's USD → IDR billing exchange rate.
     * The rate is managed by the super admin and used to compute the IDR
     * gross amount charged via the payment gateway at checkout.
     */
    public function up(): void
    {
        Schema::create('billing_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('usd_idr_rate', 12, 2)->default(16000.00);
            $table->timestamp('rate_updated_at')->nullable();
            $table->timestamps();
        });

        DB::table('billing_settings')->insert([
            'usd_idr_rate' => config('billing.fallback_rate', 16000.00),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_settings');
    }
};
