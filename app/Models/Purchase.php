<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    use HasFactory;
    protected $table = 'po';

    protected $fillable = [
        'invoice',
        'supplier_id',
        'order_date',
        'due_date',
        'payment_type',
        'total',
        'status'
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function items() {
        return $this->hasMany(POItem::class, 'po_id');
    }

    protected $casts = [
        'total' => 'double',
    ];
}