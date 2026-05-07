<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('attendance_periods')->cascadeOnDelete();
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'sick', 'leave', 'holiday', 'half_day'])->default('present');
            $table->decimal('overtime_hours', 4, 2)->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'employee_id', 'date']);
            $table->index(['period_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
