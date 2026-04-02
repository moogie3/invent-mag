<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->date('billing_period_start')->nullable()->after('plan_changed_at');
            $table->date('billing_period_end')->nullable()->after('billing_period_start');
            
            $table->foreignId('pending_plan_id')->nullable()->constrained('plans')->nullOnDelete()->after('plan_id');
            $table->timestamp('pending_plan_effective_at')->nullable()->after('pending_plan_id');
            
            $table->decimal('proration_credit', 15, 2)->nullable()->after('pending_plan_effective_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['pending_plan_id']);
            $table->dropColumn([
                'billing_period_start',
                'billing_period_end',
                'pending_plan_id',
                'pending_plan_effective_at',
                'proration_credit',
            ]);
        });
    }
};