<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('action');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('description');
            $table->unsignedBigInteger('user_id');
            $table->string('user_name');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $indexes = [
                ['tenant_id', 'entity_type', 'entity_id'],
                ['tenant_id', 'created_at'],
                ['user_id'],
                ['action'],
            ];

            foreach ($indexes as $index) {
                $table->index($index);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_audit_logs');
    }
};
