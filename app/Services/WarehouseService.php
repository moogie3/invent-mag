<?php

namespace App\Services;

use App\Models\Warehouse;

use App\Models\User;

class WarehouseService
{
    public function getWarehouseIndexData(int $entries)
    {
        $wos = Warehouse::paginate($entries);
        $totalwarehouse = Warehouse::count();
        $mainWarehouse = Warehouse::where('is_main', true)->first();
        $shopname = User::whereNotNull('shopname')->value('shopname');
        $address = User::whereNotNull('address')->value('address');

        return compact('shopname', 'address', 'wos', 'entries', 'totalwarehouse', 'mainWarehouse');
    }

    public function createWarehouse(array $data)
    {
        if (Warehouse::where('name', $data['name'])->exists()) {
            return ['success' => false, 'message' => 'This warehouse already exists.'];
        }

        if (isset($data['is_main']) && $data['is_main'] && Warehouse::hasMainWarehouse()) {
            return ['success' => false, 'message' => 'There is already a main warehouse defined. Please unset the current main warehouse first.'];
        }

        Warehouse::create($data);

        return ['success' => true, 'message' => 'Warehouse created successfully.'];
    }

    public function updateWarehouse(Warehouse $warehouse, array $data)
    {
        if (isset($data['is_main']) && $data['is_main'] && Warehouse::hasMainWarehouse($warehouse->id)) {
            return ['success' => false, 'message' => 'There is already a main warehouse defined. Please unset the current main warehouse first.'];
        }

        $warehouse->update($data);

        return ['success' => true, 'message' => 'Warehouse updated successfully.'];
    }

    public function deleteWarehouse(Warehouse $warehouse)
    {
        if ($warehouse->is_main) {
            return ['success' => false, 'message' => 'Cannot delete the main warehouse. Please set another warehouse as main first.'];
        }

        $warehouse->delete();

        return ['success' => true, 'message' => 'Warehouse deleted successfully.'];
    }

    public function setMainWarehouse(Warehouse $warehouse)
    {
        return \DB::transaction(function () use ($warehouse) {
            Warehouse::where('is_main', true)->update(['is_main' => false]); // Unsets all existing main warehouses
            $warehouse->is_main = true;
            $warehouse->save();
            $warehouse->refresh(); // Refresh the model
            return ['success' => true, 'message' => 'Main warehouse updated successfully.'];
        });
    }

    public function unsetMainWarehouse(Warehouse $warehouse)
    {
        return \DB::transaction(function () use ($warehouse) {
            if ($warehouse->is_main) {
                $warehouse->is_main = false;
                $warehouse->save();
                $warehouse->refresh(); // Refresh the model
                return ['success' => true, 'message' => 'Main warehouse status removed.'];
            }

            return ['success' => false, 'message' => 'This is not the main warehouse.'];
        });
    }
}
