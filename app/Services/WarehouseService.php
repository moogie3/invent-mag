<?php

namespace App\Services;

use App\Helpers\CurrencyHelper;
use App\Models\Warehouse;

use App\Models\User;
use Dompdf\Dompdf;

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
        if (Warehouse::whereRaw('LOWER(name) = ?', [strtolower($data['name'])])->exists()) {
            return ['success' => false, 'message' => 'This warehouse already exists.'];
        }

        if (isset($data['is_main']) && $data['is_main'] && Warehouse::hasMainWarehouse()) {
            return ['success' => false, 'message' => 'There is already a main warehouse defined. Please unset the current main warehouse first.'];
        }
        $warehouse = Warehouse::create($data);

        return ['success' => true, 'warehouse' => $warehouse];
    }

    public function updateWarehouse(Warehouse $warehouse, array $data)
    {
        if (isset($data['is_main']) && $data['is_main'] && Warehouse::hasMainWarehouse($warehouse->id)) {
            return ['success' => false, 'message' => 'There is already a main warehouse defined. Please unset the current main warehouse first.'];
        }

        $warehouse->update($data);

        return ['success' => true, 'warehouse' => $warehouse];
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

    public function exportAllWarehouses(string $exportOption)
    {
        $warehouses = Warehouse::all(); // Fetch all warehouses

        if ($exportOption === 'pdf') {
            $html = view('admin.warehouse.export-pdf', compact('warehouses'))->render();
            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            return $dompdf->stream('warehouses.pdf');
        }

        if ($exportOption === 'csv') {
            $headers = [
                'Content-type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename=warehouses.csv',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];

            $callback = function () use ($warehouses) {
                $file = fopen('php://output', 'w');
                fputcsv($file, [
                    'Name',
                    'Address',
                    'Description',
                    'Is Main',
                ]);

                foreach ($warehouses as $warehouse) {
                    fputcsv($file, [
                        $warehouse->name,
                        $warehouse->address,
                        $warehouse->description,
                        $warehouse->is_main ? 'Yes' : 'No',
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        return null;
    }
}
