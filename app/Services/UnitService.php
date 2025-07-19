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
        if (Unit::where('name', $data['name'])->exists()) {
            return ['success' => false, 'message' => 'This unit already exists.'];
        }

        Unit::create($data);

        return ['success' => true, 'message' => 'Unit created successfully.'];
    }

    public function updateUnit(Unit $unit, array $data)
    {
        $unit->update($data);

        return ['success' => true, 'message' => 'Unit updated successfully.'];
    }

    public function deleteUnit(Unit $unit)
    {
        $unit->delete();

        return ['success' => true, 'message' => 'Unit deleted successfully.'];
    }
}
