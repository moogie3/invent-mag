<?php

namespace App\Services;

use App\Models\Tax;

class TaxService
{
    public function getTaxData()
    {
        return Tax::firstOrCreate([]);
    }

    public function updateTax(array $data)
    {
        Tax::updateOrCreate([], [
            'name' => $data['name'],
            'rate' => $data['rate'],
            'is_active' => $data['is_active'] ?? false,
        ]);

        return ['success' => true, 'message' => 'Tax settings updated successfully!'];
    }
}