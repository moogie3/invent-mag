<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('frequency'); // daily, weekly, monthly, quarterly, yearly
            $table->integer('interval')->default(1);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('last_generated_at')->nullable();
            $table->date('next_generation_date');
            $table->json('transactions'); // Array of {account_code, type, amount}
            $table->string('template_account_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_journal_entries');
    }
};
