<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesPipeline extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_default',
    ];

    public function stages()
    {
        return $this->hasMany(PipelineStage::class);
    }

    public function opportunities()
    {
        return $this->hasMany(SalesOpportunity::class);
    }
}
