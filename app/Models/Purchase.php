<?php

namespace App\Models;

use App\Helpers\PurchaseHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Tax;

/**
 * @property int $id
 * @property string $status
 * @property string $discount_total_type
 * @property float $discount_total
 * @property string $invoice
 * @property \Illuminate\Support\Carbon $order_date
 * @property \Illuminate\Support\Carbon $due_date
 * @property string $payment_type
 * @property float $total
 * @property float $total_amount
 * @property float $sub_total
 * @property float $grand_total
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Purchase extends Model
{
        use HasFactory, BelongsToTenant;
        protected $table = 'po';
        protected $fillable = [
            'invoice',
            'supplier_id',
            'user_id',
            'warehouse_id',
            'order_date',
            'due_date',
            'payment_type',
            'discount_total',
            'discount_total_type',
            'tax_id',
            'tax_rate',
            'total_tax',
            'tax_type',
            'total',
            'status',
        ];

        protected $guarded = [
            'id',
            'tenant_id',
        ];

        public static array $statuses = [
            'Unpaid',
            'Partial',
            'Paid',
            'Returned', // Added new status
        ];

        protected $appends = ['sub_total', 'grand_total', 'balance', 'total_paid', 'grand_total_adjusted'];

    public function getGrandTotalAdjustedAttribute(): float
    {
        return $this->getAdjustedTotal();
    }

        protected $attributes = [
        'status' => 'Unpaid',
        'payment_type' => '-',
        ];

        public function warehouse(): BelongsTo
        {
            return $this->belongsTo(Warehouse::class, 'warehouse_id');
        }

        public function supplier(): BelongsTo
        {
            return $this->belongsTo(Supplier::class, 'supplier_id');
        }

        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class, 'user_id');
        }

        public function items() {
            return $this->hasMany(POItem::class, 'po_id');
        }

        // Add hasMany relationship to PurchaseReturn
        public function purchaseReturns()
        {
            return $this->hasMany(PurchaseReturn::class);
        }

        public function tax(): BelongsTo
        {
            return $this->belongsTo(Tax::class, 'tax_id');
        }

        public function getSubTotalAttribute()
        {
            return $this->items->sum('total');
        }

        public function payments()
        {
            return $this->morphMany(Payment::class, 'paymentable');
        }

        public function getTotalPaidAttribute()
        {
            return $this->payments->sum('amount');
        }

            public function getBalanceAttribute()
            {
                return max(0, round($this->getAdjustedTotal() - $this->total_paid, 2));
            }

                public function getGrandTotalAttribute()
                {
                    if ($this->relationLoaded('items') && $this->items->isNotEmpty()) {
                        $subTotal = $this->items->sum('total');
                        $orderDiscount = PurchaseHelper::calculateDiscount(
                            $subTotal,
                            $this->discount_total,
                            $this->discount_total_type
                        );
                        $afterDiscount = $subTotal - $orderDiscount;
                        $taxAmount = $this->total_tax ?? PurchaseHelper::calculateTaxAmount(
                            $afterDiscount,
                            $this->tax_rate ?? 0,
                            $this->tax_type ?? 'none'
                        );
                        if (($this->tax_type ?? 'none') === 'inclusive') {
                            return round($afterDiscount, 2);
                        }
                        return round($afterDiscount + $taxAmount, 2);
                    }

                    $baseTotal = round($this->attributes['total'] ?? 0, 2);
                    // 'total' is stored as the complete grand total (after discount + tax) by
                    // createPurchase() / updatePurchase(). Return as-is for all tax types.
                    return $baseTotal;
                }

        public function getTotalReturnedAmount(): float
        {
            if (!$this->relationLoaded('purchaseReturns')) {
                return 0;
            }
            return (float) $this->purchaseReturns->where('status', 'Completed')->sum('total_amount');
        }

        public function getReturnedQuantityForProduct(int $productId): int
        {
            if (!$this->relationLoaded('purchaseReturns')) {
                return 0;
            }
            return (int) $this->purchaseReturns
                ->where('status', 'Completed')
                ->flatMap(fn($pr) => $pr->items ?? collect())
                ->where('product_id', $productId)
                ->sum('quantity');
        }

        public function getAdjustedTotal(): float
        {
            if (!$this->relationLoaded('items')) {
                $this->load('items');
            }

            $summary = PurchaseHelper::calculateInvoiceSummaryWithReturns(
                $this->items,
                $this->discount_total ?? 0,
                $this->discount_total_type ?? 'fixed',
                $this->relationLoaded('purchaseReturns') ? $this->purchaseReturns : collect(),
                (float) ($this->tax_rate ?? 0),
                $this->tax_type ?? 'none'
            );

            return $summary['finalTotal'];
        }

        public function getTotalAmountAttribute()
        {
            return $this->grand_total;
        }

        protected $casts = [
        'total' => 'float',
        'discount_total' => 'float',
        'tax_rate' => 'float',
        'total_tax' => 'float',
        'order_date' => 'datetime',
        'due_date' => 'datetime',
        ];

        // Explicitly define the factory for the model
        protected static function newFactory()
        {
            return \Database\Factories\PurchaseFactory::new();
        }

        /**
         * Get the path to view the model.
         *
         * @return string
         */
        public function path(): string
        {
            return route('admin.po.show', $this);
        }
    }
