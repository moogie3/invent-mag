<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SupplierService
{
    public function getSupplierIndexData(int $entries)
    {
        $suppliers = Supplier::paginate($entries);
        $inCount = Supplier::where('location', 'IN')->count();
        $outCount = Supplier::where('location', 'OUT')->count();
        $totalsupplier = Supplier::count();
        return compact('suppliers', 'entries', 'totalsupplier', 'inCount', 'outCount');
    }

    public function createSupplier(array $data)
    {
        if (Supplier::where('name', $data['name'])->exists()) {
            return ['success' => false, 'message' => 'This supplier already exists.'];
        }

        if (isset($data['image'])) {
            $data['image'] = $this->storeImage($data['image']);
        }

        Supplier::create($data);

        return ['success' => true, 'message' => 'Supplier created successfully.'];
    }

    public function updateSupplier(Supplier $supplier, array $data)
    {
        if (isset($data['image'])) {
            if ($supplier->image) {
                Storage::delete('public/image/' . basename($supplier->image));
            }
            $data['image'] = $this->storeImage($data['image']);
        }

        $supplier->update($data);

        return ['success' => true, 'message' => 'Supplier updated successfully.'];
    }

    public function deleteSupplier(Supplier $supplier)
    {
        if ($supplier->image) {
            Storage::delete('public/image/' . basename($supplier->image));
        }

        $supplier->delete();

        return ['success' => true, 'message' => 'Supplier deleted successfully.'];
    }

    private function storeImage($image): string
    {
        $imageName = Str::random(10) . '_' . $image->getClientOriginalName();
        $image->storeAs('public/image', $imageName);
        return $imageName;
    }

    public function getSupplierMetrics()
    {
        return [
            'totalsupplier' => Supplier::count(),
            'inCount' => Supplier::where('location', 'IN')->count(),
            'outCount' => Supplier::where('location', 'OUT')->count(),
        ];
    }
}
