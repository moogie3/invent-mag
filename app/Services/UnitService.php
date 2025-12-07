<?php

namespace App\Services;

use App\Models\Unit;

class UnitService
{
    public function getUnitIndexData(int $entries)
    {
        $units = Unit::paginate($entries);
        $totalunit = Unit::count();
        return compact('units', 'entries', 'totalunit');
    }

    public function createUnit(array $data)
    {
        if (Unit::whereRaw('LOWER(name) = ?', [strtolower($data['name'])])->first()) {
            return ['success' => false, 'message' => 'This unit already exists.'];
        }
        $unit = Unit::create($data);

        return ['success' => true, 'message' => 'Unit created successfully.', 'unit' => $unit];
    }

    public function updateUnit(Unit $unit, array $data)
    {
        $unit->update($data);

        return ['success' => true, 'message' => 'Unit updated successfully.', 'unit' => $unit];
    }

    public function deleteUnit(Unit $unit)
    {
        $unit->delete();

        return ['success' => true, 'message' => 'Unit deleted successfully.'];
    }
}
