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
            throw new \App\Exceptions\WarehouseException('This warehouse already exists.', 422);
        }

        if (isset($data['is_main']) && $data['is_main'] && Warehouse::hasMainWarehouse()) {
            throw new \App\Exceptions\WarehouseException('There is already a main warehouse defined. Please unset the current main warehouse first.', 422);
        }

        $warehouse = Warehouse::create($data);

        return ['warehouse' => $warehouse];
    }

    public function updateWarehouse(Warehouse $warehouse, array $data)
    {
        if (isset($data['is_main']) && $data['is_main'] && Warehouse::hasMainWarehouse($warehouse->id)) {
            throw new \App\Exceptions\WarehouseException('There is already a main warehouse defined. Please unset the current main warehouse first.', 422);
        }

        $warehouse->update($data);

        return ['warehouse' => $warehouse];
    }

    public function deleteWarehouse(Warehouse $warehouse)
    {
        if ($warehouse->is_main) {
            throw new \App\Exceptions\WarehouseException('Cannot delete the main warehouse. Please set another warehouse as main first.', 422);
        }

        $warehouse->delete();

        return ['message' => 'Warehouse deleted successfully.'];
    }

    public function setMainWarehouse(Warehouse $warehouse)
    {
        return \DB::transaction(function () use ($warehouse) {
            Warehouse::where('is_main', true)->update(['is_main' => false]); // Unsets all existing main warehouses
            $warehouse->is_main = true;
            $warehouse->save();
            $warehouse->refresh(); // Refresh the model
            return ['message' => 'Main warehouse updated successfully.'];
        });
    }

    public function unsetMainWarehouse(Warehouse $warehouse)
    {
        return \DB::transaction(function () use ($warehouse) {
            if ($warehouse->is_main) {
                $warehouse->is_main = false;
                $warehouse->save();
                $warehouse->refresh(); // Refresh the model
                return ['message' => 'Main warehouse status removed.'];
            }

            throw new \App\Exceptions\WarehouseException('This is not the main warehouse.', 422);
        });
    }
}
