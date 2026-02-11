<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('is_contra')->default(false)->after('is_active');
            $table->string('normal_balance')->default('debit')->after('is_contra'); // debit or credit
            $table->decimal('opening_balance', 15, 2)->default(0)->after('normal_balance');
            $table->date('opening_balance_date')->nullable()->after('opening_balance');
            $table->string('currency', 3)->default('IDR')->after('opening_balance_date');
            $table->boolean('allow_manual_entry')->default(true)->after('currency');
            $table->string('tax_type')->nullable()->after('allow_manual_entry'); // PPN_IN, PPN_OUT, PPH, etc.
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn([
                'is_contra',
                'normal_balance',
                'opening_balance',
                'opening_balance_date',
                'currency',
                'allow_manual_entry',
                'tax_type'
            ]);
        });
    }
};
