<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('backup_password', 100)->nullable()->after('proration_credit');
            $table->boolean('backup_encrypted')->default(false)->after('backup_password');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['backup_password', 'backup_encrypted']);
        });
    }
};