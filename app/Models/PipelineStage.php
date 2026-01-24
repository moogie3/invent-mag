<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class PipelineStage extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'sales_pipeline_id',
        'name',
        'position',
        'is_closed',
    ];

    protected $guarded = [
        'id',
        'tenant_id',
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