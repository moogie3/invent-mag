<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->string('status')->default('posted')->after('description');
            $table->text('notes')->nullable()->after('status');
            $table->unsignedBigInteger('reversed_entry_id')->nullable()->after('notes');
            $table->string('entry_type')->default('standard')->after('reversed_entry_id');
            $table->unsignedBigInteger('approved_by')->nullable()->after('entry_type');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropColumn(['status', 'notes', 'reversed_entry_id', 'entry_type', 'approved_by', 'approved_at']);
        });
    }
};
