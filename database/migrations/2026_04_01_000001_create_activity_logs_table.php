<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name');
            $table->string('user_role')->nullable();
            $table->string('module');         // e.g. Product, Sales, Purchase Order
            $table->string('action');          // create, update, delete, etc.
            $table->string('entity_type')->nullable(); // model class name
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('entity_label')->nullable(); // human-readable name e.g. product name
            $table->text('description');       // human-readable log line
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'module']);
            $table->index(['tenant_id', 'action']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
