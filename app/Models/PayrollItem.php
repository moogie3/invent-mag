<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    use BelongsToTenant, HasFactory;

    protected $guarded = [];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'allowances' => 'array',
        'deductions' => 'array',
        'overtime_pay' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'working_days' => 'integer',
        'present_days' => 'integer',
        'absent_days' => 'integer',
        'overtime_hours' => 'decimal:2',
        'pdf_generated_at' => 'datetime',
    ];

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function calculateGross(): float
    {
        $allowanceTotal = collect($this->allowances)->sum('amount');
        return $this->base_salary + $allowanceTotal + $this->overtime_pay;
    }

    public function calculateNet(): float
    {
        $deductionTotal = collect($this->deductions)->sum('amount');
        return $this->calculateGross() - $deductionTotal;
    }
}