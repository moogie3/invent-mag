<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PipelineStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_pipeline_id',
        'name',
        'position',
        'is_closed',
    ];

    public function pipeline()
    {
        return $this->belongsTo(SalesPipeline::class);
    }

    public function opportunities()
    {
        return $this->hasMany(SalesOpportunity::class);
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Database\Factories\PipelineStageFactory::new();
    }
}