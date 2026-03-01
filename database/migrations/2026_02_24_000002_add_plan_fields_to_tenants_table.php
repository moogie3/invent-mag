<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('domain')->constrained('plans')->nullOnDelete();
            $table->string('plan_status')->default('active')->after('plan_id'); // active, trialing, expired, cancelled
            $table->timestamp('trial_ends_at')->nullable()->after('plan_status');
            $table->timestamp('plan_expires_at')->nullable()->after('trial_ends_at');
            $table->timestamp('plan_changed_at')->nullable()->after('plan_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn([
                'plan_id',
                'plan_status',
                'trial_ends_at',
                'plan_expires_at',
                'plan_changed_at',
            ]);
        });
    }
};
