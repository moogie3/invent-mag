<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOpportunity extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'sales_pipeline_id',
        'pipeline_stage_id',
        'name',
        'description',
        'amount',
        'expected_close_date',
        'status',
        'sales_id',
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

    /**
     * Calculate the total amount from items before saving.
     *
     * @param array $options
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
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Database\Factories\SalesOpportunityFactory::new();
    }
}
