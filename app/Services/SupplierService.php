<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

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
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $data['image'] = $this->storeImage($data['image']);
        }

        $supplier = Supplier::create($data);

        return ['success' => true, 'message' => 'Supplier created successfully.', 'supplier' => $supplier];
    }

    public function updateSupplier(Supplier $supplier, array $data)
    {
        if (isset($data['image'])) {
            $oldImage = $supplier->getRawOriginal('image');
            if ($oldImage && !filter_var($oldImage, FILTER_VALIDATE_URL)) {
                Storage::disk('public')->delete('image/' . $oldImage);
            }
            if ($data['image'] instanceof UploadedFile) {
                $data['image'] = $this->storeImage($data['image']);
            }
        }

        $supplier->update($data);

        return ['success' => true, 'message' => 'Supplier updated successfully.', 'supplier' => $supplier];
    }

    public function deleteSupplier(Supplier $supplier)
    {
        $image = $supplier->getRawOriginal('image');
        if ($image && !filter_var($image, FILTER_VALIDATE_URL)) {
            Storage::disk('public')->delete('image/' . $image);
        }

        $supplier->delete();

        return ['success' => true, 'message' => 'Supplier deleted successfully.'];
    }

    private function storeImage($image): string
    {
        $imageName = Str::random(10) . '_' . $image->getClientOriginalName();
        $image->storeAs('image', $imageName, 'public');
        return $imageName;
    }

    public function getSupplierMetrics()
    {
        $inCount = Supplier::where('location', 'IN')->count();
        $outCount = Supplier::where('location', 'OUT')->count();
        $totalsupplier = Supplier::count();
        return [
            'totalsupplier' => $totalsupplier,
            'inCount' => $inCount,
            'outCount' => $outCount,
        ];
    }
}
