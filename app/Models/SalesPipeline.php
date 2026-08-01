<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\SalesPipelineFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesPipeline extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_default',
    ];

    protected $guarded = [
        'id',
        'tenant_id',
    ];

    public function stages()
    {
        return $this->hasMany(PipelineStage::class)->orderBy('position');
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
        return SalesPipelineFactory::new();
    }
}
