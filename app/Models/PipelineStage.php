<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\PipelineStageFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PipelineStage extends Model
{
    use BelongsToTenant, HasFactory;

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
     * @return Factory
     */
    protected static function newFactory()
    {
        return PipelineStageFactory::new();
    }
}
