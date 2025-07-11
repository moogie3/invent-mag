<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PipelineStage extends Model
{
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
}