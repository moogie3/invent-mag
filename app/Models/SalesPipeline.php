<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesPipeline extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_default',
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
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Database\Factories\SalesPipelineFactory::new();
    }
}
