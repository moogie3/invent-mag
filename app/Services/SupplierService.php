<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Dompdf\Dompdf;
use App\Helpers\CurrencyHelper;

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
        \Log::debug('createSupplier: Starting with data', $data);
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $data['image'] = $this->storeImage($data['image']);
        }

        $supplier = Supplier::create($data);
        \Log::debug('createSupplier: Supplier created', ['supplier' => $supplier]);

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

    public function exportAllSuppliers(string $exportOption)
    {
        $suppliers = Supplier::all(); // Fetch all suppliers

        if ($exportOption === 'pdf') {
            $html = view('admin.supplier.export-pdf', compact('suppliers'))->render();
            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            return $dompdf->stream('suppliers.pdf');
        }

        if ($exportOption === 'csv') {
            $headers = [
                'Content-type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename=suppliers.csv',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];

            $callback = function () use ($suppliers) {
                $file = fopen('php://output', 'w');
                fputcsv($file, [
                    'Code',
                    'Name',
                    'Address',
                    'Phone Number',
                    'Location',
                    'Payment Terms',
                    'Email',
                ]);

                foreach ($suppliers as $supplier) {
                    fputcsv($file, [
                        $supplier->code,
                        $supplier->name,
                        $supplier->address,
                        $supplier->phone_number,
                        $supplier->location,
                        $supplier->payment_terms,
                        $supplier->email,
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        return null;
    }
}
