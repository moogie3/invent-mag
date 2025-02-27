<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailySales extends Model
{
    use HasFactory;

    protected $table = 'daily_sales';

    protected $fillable = [
        'date',
        'total'
    ];

    protected $casts = [
        'total' => 'double',
        'date' => 'datetime'
    ];
}