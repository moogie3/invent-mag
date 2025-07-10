<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Sales extends Model
{
    use HasFactory;

    protected $table = 'sales';
    protected $fillable = [
        'invoice',
        'customer_id',
        'user_id',
        'order_date',
        'due_date',
        'payment_date',
        'payment_type',
        'order_discount',
        'order_discount_type',
        'total',
        'status',
        'tax_rate',
        'total_tax',
        'amount_received',
        'change_amount',
        'is_pos'
    ];

    protected $with = ['salesItems'];

    protected $attributes = [
        'status' => 'Unpaid',
        'is_pos' => 'false',
    ];

    public static $paymentStatus = [
        'Paid',
        'Partial',
        'Unpaid',
    ];

    public static $paymentTypes = [
        'cash',
        'card',
        'transfer',
        'ewallet'
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function salesItems(): HasMany
    {
        return $this->hasMany(SalesItem::class, 'sales_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    protected $casts = [
        'total' => 'float',
        'total_tax' => 'float',
        'tax_rate' => 'float',
        'order_discount' => 'float',
        'amount_received' => 'float',
        'change_amount' => 'float',
        'is_pos' => 'boolean'
        // Removed datetime casts here since we'll handle them with accessors/mutators
    ];

    // Get user timezone or fallback to app timezone
    protected function getUserTimezone()
    {
        if (Auth::check()) {
            return Auth::user()->timezone ?? config('app.timezone');
        }
        return config('app.timezone');
    }

    // Order Date Accessors & Mutators
    public function getOrderDateAttribute($value)
{
    if (!$value) return null;

    $convertedDate = \Carbon\Carbon::parse($value)->setTimezone($this->getUserTimezone());

    return $convertedDate;
}


    public function setOrderDateAttribute($value)
    {
        if (!$value) {
            $this->attributes['order_date'] = null;
            return;
        }

        // If it's just a date with no time, add the current time in user's timezone
        if (strlen($value) <= 10) {
            $value = $value . ' ' . now($this->getUserTimezone())->format('H:i:s');
        }

        $this->attributes['order_date'] = \Carbon\Carbon::parse($value, $this->getUserTimezone())
            ->setTimezone('UTC');
    }

    // Due Date Accessors & Mutators
    public function getDueDateAttribute($value)
    {
        if (!$value) return null;
        return \Carbon\Carbon::parse($value)->setTimezone($this->getUserTimezone());
    }

    public function setDueDateAttribute($value)
    {
        if (!$value) {
            $this->attributes['due_date'] = null;
            return;
        }

        // If it's just a date with no time, add the current time in user's timezone
        if (strlen($value) <= 10) {
            $value = $value . ' ' . now($this->getUserTimezone())->format('H:i:s');
        }

        $this->attributes['due_date'] = \Carbon\Carbon::parse($value, $this->getUserTimezone())
            ->setTimezone('UTC');
    }

    // Payment Date Accessors & Mutators
    public function getPaymentDateAttribute($value)
    {
        if (!$value) return null;
        return \Carbon\Carbon::parse($value)->setTimezone($this->getUserTimezone());
    }

    public function setPaymentDateAttribute($value)
    {
        if (!$value) {
            $this->attributes['payment_date'] = null;
            return;
        }

        $this->attributes['payment_date'] = \Carbon\Carbon::parse($value, $this->getUserTimezone())
            ->setTimezone('UTC');
    }
}