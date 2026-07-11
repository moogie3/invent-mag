<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete()->after('total_tax');
            $table->string('tax_type', 20)->nullable()->after('tax_id');
        });

        Schema::table('po', function (Blueprint $table) {
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete()->after('total');
            $table->decimal('tax_rate', 5, 2)->default(0)->after('tax_id');
            $table->decimal('total_tax', 15, 2)->default(0)->after('tax_rate');
            $table->string('tax_type', 20)->nullable()->after('total_tax');
        });

        if (Schema::hasTable('quotations')) {
            Schema::table('quotations', function (Blueprint $table) {
                $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete()->after('tax_amount');
            });
        }

        Schema::table('sales_returns', function (Blueprint $table) {
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete()->after('total_amount');
            $table->decimal('tax_rate', 5, 2)->default(0)->after('tax_id');
            $table->decimal('tax_amount', 15, 2)->default(0)->after('tax_rate');
            $table->string('tax_type', 20)->nullable()->after('tax_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $t) {
            $t->dropForeign(['tax_id']);
            $t->dropColumn(['tax_id', 'tax_type']);
        });

        Schema::table('po', function (Blueprint $t) {
            $t->dropForeign(['tax_id']);
            $t->dropColumn(['tax_id', 'tax_rate', 'total_tax', 'tax_type']);
        });

        if (Schema::hasTable('quotations')) {
            Schema::table('quotations', function (Blueprint $t) {
                $t->dropForeign(['tax_id']);
                $t->dropColumn(['tax_id']);
            });
        }

        Schema::table('sales_returns', function (Blueprint $t) {
            $t->dropForeign(['tax_id']);
            $t->dropColumn(['tax_id', 'tax_rate', 'tax_amount', 'tax_type']);
        });
    }
};
