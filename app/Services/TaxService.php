<?php

namespace App\Services;

use App\Models\Tax;

class TaxService
{
    public function getTaxData()
    {
        return Tax::first();
    }

    public function updateTax(array $data)
    {
        $tax = Tax::firstOrNew();
        $tax->name = $data['name'];
        $tax->rate = $data['rate'];
        $tax->is_active = $data['is_active'];
        $tax->save();

        return ['success' => true, 'message' => 'Tax settings updated successfully!'];
    }
}