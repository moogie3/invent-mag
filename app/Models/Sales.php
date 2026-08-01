<?php

namespace App\Models;

use App\Helpers\SalesHelper;
use App\Models\Concerns\BelongsToTenant;
use Carbon\Carbon;
use Database\Factories\SalesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

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
    use BelongsToTenant, HasFactory;

    protected $table = 'sales';

    public const TYPE_REGULAR = 'regular';

    public const TYPE_POS = 'pos';

    protected $fillable = [
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
        'tax_id',
        'tax_type',
        'amount_received',
        'change_amount',
        'type',
        'sales_opportunity_id',
        'warehouse_id',
        'customer_credit_used',
        'notes',
        'terms_conditions',
        // PPN / e-Faktur
        'ppn_enabled',
        'ppn_rate',
        'ppn_amount',
        // PPh / Withholding Tax
        'pph_enabled',
        'pph_rate',
        'pph_amount',
        'pph_tax_id',
    ];

    protected $guarded = [
        'id',
        'tenant_id',
    ];

    protected $appends = ['balance', 'total_paid', 'grand_total', 'grand_total_adjusted'];

    public function getGrandTotalAdjustedAttribute(): float
    {
        return $this->getAdjustedTotal();
    }

    protected $attributes = [
        'status' => 'Unpaid',
        'type' => self::TYPE_REGULAR,
    ];

    /**
     * Backward compatibility accessor for existing views that check $sale->is_pos
     */
    public function getIsPosAttribute(): bool
    {
        return $this->type === self::TYPE_POS;
    }

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
        'ewallet',
    ];

    // Relationships
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

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

    public function fakturPajak(): HasOne
    {
        return $this->hasOne(FakturPajak::class, 'sales_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    public function pphTax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'pph_tax_id');
    }

    /**
     * Determine if this sale uses a PPN (Indonesian VAT) tax.
     * Always reads from the stored snapshot ppn_enabled field.
     * Never reads from the live Tax.is_ppn relationship — the snapshot is the source of truth
     * for historical records. If a tax record's is_ppn flag is later changed, it must NOT
     * retroactively affect existing sales.
     */
    public function isPpn(): bool
    {
        return (bool) $this->ppn_enabled;
    }

    /**
     * Determine if this sale has PPh (withholding tax) applied.
     * Always reads from the stored snapshot pph_enabled field.
     * Never reads from the live Tax.is_pph relationship.
     */
    public function isPph(): bool
    {
        return (bool) $this->pph_enabled;
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    public function getTotalPaidAttribute()
    {
        return $this->payments->sum('amount');
    }

    public function getCustomerCreditUsedAttribute(): float
    {
        return (float) ($this->attributes['customer_credit_used'] ?? 0);
    }

    public function getGrandTotalAttribute()
    {
        if ($this->relationLoaded('salesItems') && $this->salesItems->isNotEmpty()) {
            $summary = SalesHelper::calculateInvoiceSummary(
                $this->salesItems,
                $this->order_discount,
                $this->order_discount_type,
                $this->tax_rate,
                $this->tax_type ?? 'exclusive',
                $this->pph_rate ?? 0
            );

            return round($summary['finalTotal'], 2);
        }

        // 'total' is always stored as the complete grand total (taxable + tax for exclusive,
        // or just taxable for inclusive/none) by createSale() and updateSale(). Return as-is.
        return round(($this->attributes['total'] ?? 0), 2);
    }

    public function getTotalAmountAttribute()
    {
        return $this->grand_total;
    }

    public function getBalanceAttribute()
    {
        return max(0, $this->getAdjustedTotal() - $this->total_paid - $this->customer_credit_used);
    }

    public function getTotalReturnedAmount(): float
    {
        if (! $this->relationLoaded('salesReturns')) {
            return 0;
        }

        return (float) $this->salesReturns->where('status', 'Completed')->sum('total_amount');
    }

    public function getReturnedQuantityForProduct(int $productId): int
    {
        if (! $this->relationLoaded('salesReturns')) {
            return 0;
        }

        return (int) $this->salesReturns
            ->where('status', 'Completed')
            ->flatMap(fn ($sr) => $sr->items ?? collect())
            ->where('product_id', $productId)
            ->sum('quantity');
    }

    public function getReturnedQuantityForSalesItem(int $salesItemId): int
    {
        if (! $this->relationLoaded('salesReturns')) {
            return 0;
        }

        return (int) $this->salesReturns
            ->where('status', 'Completed')
            ->flatMap(fn ($sr) => $sr->items ?? collect())
            ->where('sales_item_id', $salesItemId)
            ->sum('quantity');
    }

    public function getAdjustedTotal(): float
    {
        if (! $this->relationLoaded('salesItems')) {
            $this->load('salesItems');
        }

        if (! $this->relationLoaded('salesReturns')) {
            $this->load('salesReturns');
        }

        $summary = SalesHelper::calculateInvoiceSummaryWithReturns(
            $this->salesItems,
            $this->order_discount ?? 0,
            $this->order_discount_type ?? 'fixed',
            $this->tax_rate ?? 0,
            $this->salesReturns,
            $this->tax_type ?? 'exclusive',
            $this->pph_rate ?? 0
        );

        return $summary['finalTotal'];
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
        'customer_credit_used' => 'float',
        // PPN / e-Faktur
        'ppn_enabled' => 'boolean',
        'ppn_rate' => 'float',
        'ppn_amount' => 'float',
        // PPh / Withholding Tax
        'pph_enabled' => 'boolean',
        'pph_rate' => 'float',
        'pph_amount' => 'float',
    ];

    // Explicitly define the factory for the model
    protected static function newFactory()
    {
        return SalesFactory::new();
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
        if (! $value) {
            return null;
        }

        $convertedDate = Carbon::parse($value)->setTimezone($this->getUserTimezone());

        return $convertedDate;
    }

    public function setOrderDateAttribute($value)
    {
        if (! $value) {
            $this->attributes['order_date'] = null;

            return;
        }

        // If it's just a date with no time, add the current time in user's timezone
        if (strlen($value) <= 10) {
            $value = $value.' '.now($this->getUserTimezone())->format('H:i:s');
        }

        $this->attributes['order_date'] = Carbon::parse($value, $this->getUserTimezone())
            ->setTimezone('UTC');
    }

    // Due Date Accessors & Mutators
    public function getDueDateAttribute($value)
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->setTimezone($this->getUserTimezone());
    }

    public function setDueDateAttribute($value)
    {
        if (! $value) {
            $this->attributes['due_date'] = null;

            return;
        }

        // If it's just a date with no time, add the current time in user's timezone
        if (strlen($value) <= 10) {
            $value = $value.' '.now($this->getUserTimezone())->format('H:i:s');
        }

        $this->attributes['due_date'] = Carbon::parse($value, $this->getUserTimezone())
            ->setTimezone('UTC');
    }

    /**
     * Get the path to view the model.
     */
    public function path(): string
    {
        return route('admin.sales.show', $this);
    }
}
