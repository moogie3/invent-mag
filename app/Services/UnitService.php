<?php

namespace App\Services;


use App\Services\Concerns\BuildsListSearch;
use App\Models\Unit;

class UnitService
{
    use BuildsListSearch;
    public function getUnitIndexData(array $filters, int $entries)
    {
        $query = Unit::query();

        // Whole-catalog search — the paginator's COUNT(*) shares this WHERE.
        $this->applyListSearch($query, $filters, ['units.name', 'units.symbol']);

        $units = $query->paginate($entries);
        $totalunit = Unit::count();

        return compact('units', 'entries', 'totalunit');
    }

    public function createUnit(array $data)
    {
        $tenantId = auth()->user()->tenant_id;
        if (Unit::where('tenant_id', $tenantId)->whereRaw('LOWER(name) = ?', [strtolower($data['name'])])->first()) {
            return ['success' => false, 'message' => 'This unit already exists.'];
        }
        $unit = Unit::create($data);

        return ['success' => true, 'message' => 'Unit created successfully.', 'unit' => $unit];
    }

    public function updateUnit(Unit $unit, array $data)
    {
        $tenantId = auth()->user()->tenant_id;
        if (Unit::where('tenant_id', $tenantId)->where('id', '!=', $unit->id)->whereRaw('LOWER(name) = ?', [strtolower($data['name'])])->first()) {
            return ['success' => false, 'message' => 'This unit already exists.'];
        }
        $unit->update($data);

        return ['success' => true, 'message' => 'Unit updated successfully.', 'unit' => $unit];
    }

    public function deleteUnit(Unit $unit)
    {
        $unit->delete();

        return ['success' => true, 'message' => 'Unit deleted successfully.'];
    }
}
