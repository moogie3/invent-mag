<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\SalesOpportunityFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOpportunity extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'customer_id',
        'sales_pipeline_id',
        'pipeline_stage_id',
        'name',
        'description',
        'notes',
        'terms_conditions',
        'amount',
        'expected_close_date',
        'status',
        'sales_id',
    ];

    protected $guarded = [
        'id',
        'tenant_id',
    ];

    protected $casts = [
        'expected_close_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function pipeline()
    {
        return $this->belongsTo(SalesPipeline::class, 'sales_pipeline_id');
    }

    public function stage()
    {
        return $this->belongsTo(PipelineStage::class, 'pipeline_stage_id');
    }

    public function sales()
    {
        return $this->hasOne(Sales::class, 'sales_opportunity_id');
    }

    public function items()
    {
        return $this->hasMany(SalesOpportunityItem::class);
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class, 'sales_opportunity_id');
    }

    /**
     * Calculate the total amount from items before saving.
     *
     * @return bool
     */
    public function save(array $options = [])
    {
        if ($this->relationLoaded('items')) {
            $this->amount = $this->items->sum(function ($item) {
                return $item->quantity * $item->price;
            });
        }

        return parent::save($options);
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory()
    {
        return SalesOpportunityFactory::new();
    }
}
