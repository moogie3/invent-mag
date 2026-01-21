<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use App\Models\Concerns\BelongsToTenant;

/**
 * @property int $id
 * @property string $invoice
 * @property \Illuminate\Support\Carbon $order_date
 * @property float $total
 * @property string $order_discount_type
 * @property float $order_discount
 * @property float $tax_rate
 * @property float $amount_received
 * @property float $total_tax
 * @property \Illuminate\Support\Carbon $due_date
 * @property string $payment_type
 * @property \Illuminate\Support\Carbon $created_at
 * @property string $status
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Sales extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'sales';
    protected $fillable = [
        'tenant_id',
        'invoice',
        'customer_id',
        'user_id',
        'order_date',
        'due_date',
        'payment_type',
        'order_discount',
        'order_discount_type',
        'total',
        'status',
        'tax_rate',
        'total_tax',
        'amount_received',
        'change_amount',
        'is_pos',
        'sales_opportunity_id'
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
        'Returned',
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

    public function salesReturns(): HasMany
    {
        return $this->hasMany(SalesReturn::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    public function getTotalPaidAttribute()
    {
        return $this->payments->sum('amount');
    }

    public function getGrandTotalAttribute()
    {
        // If the items relationship is loaded, calculate precisely from items + order-level tax/discount.
        if ($this->relationLoaded('salesItems')) {
            $summary = \App\Helpers\SalesHelper::calculateInvoiceSummary($this->salesItems, $this->order_discount, $this->order_discount_type, $this->tax_rate);
            return round($summary['finalTotal'], 2);
        }

        // Otherwise, fall back to the stored total.
        return round(($this->attributes['total'] ?? 0), 2);
    }

    public function getTotalAmountAttribute()
    {
        return $this->grand_total;
    }

    public function getBalanceAttribute()
    {
        return $this->total - $this->total_paid;
    }

    public function salesOpportunity(): BelongsTo
    {
        return $this->belongsTo(SalesOpportunity::class, 'sales_opportunity_id');
    }

    protected $casts = [
        'total' => 'float',
        'total_tax' => 'float',
        'tax_rate' => 'float',
        'order_discount' => 'float',
        'amount_received' => 'float',
        'change_amount' => 'float',
        'is_pos' => 'boolean',
    ];

    // Explicitly define the factory for the model
    protected static function newFactory()
    {
        return \Database\Factories\SalesFactory::new();
    }

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

    /**
     * Get the path to view the model.
     *
     * @return string
     */
    public function path(): string
    {
        return route('admin.sales.view', $this);
    }
}