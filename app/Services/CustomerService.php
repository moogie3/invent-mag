<?php

namespace App\Services;

use App\Helpers\CurrencyHelper;
use App\Models\Customer;
use App\Models\Sales;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CustomerService
{
    public function getCustomerIndexData(int $entries)
    {
        $customers = Customer::paginate($entries);
        $totalcustomer = Customer::count();
        return compact('customers', 'entries', 'totalcustomer');
    }

    public function createCustomer(array $data)
    {
        if (Customer::whereRaw('LOWER(name) = ?', [strtolower($data['name'])])->first()) {
            return ['success' => false, 'message' => 'This customer already exists.'];
        }

        if (isset($data['image'])) {
            $data['image'] = $this->storeImage($data['image']);
        }

        $customer = Customer::create($data);

        return ['success' => true, 'message' => 'Customer created successfully.', 'customer' => $customer];
    }

    public function quickCreateCustomer(array $data)
    {
        if (isset($data['image'])) {
            $data['image'] = $this->storeImage($data['image']);
        }

        $customer = Customer::create($data);

        return ['success' => true, 'message' => 'Customer created successfully.', 'customer' => $customer];
    }

    public function updateCustomer(Customer $customer, array $data)
    {
        if (isset($data['image'])) {
            $oldImage = $customer->getRawOriginal('image');
            if ($oldImage) {
                Storage::disk('public')->delete('image/' . $oldImage);
            }
            $data['image'] = $this->storeImage($data['image']);
        }

        $customer->update($data);

        return ['success' => true, 'message' => 'Customer updated successfully.', 'customer' => $customer];
    }

    public function deleteCustomer(Customer $customer)
    {
        $image = $customer->getRawOriginal('image');
        if ($image) {
            Storage::disk('public')->delete('image/' . $image);
        }

        $customer->delete();

        return ['success' => true, 'message' => 'Customer deleted successfully.'];
    }

    

    private function storeImage($image): string
    {
        $imageName = Str::random(10) . '_' . $image->getClientOriginalName();
        $image->storeAs('image', $imageName, 'public');
        return $imageName;
    }

    public function getCustomerMetrics()
    {
        return [
            'totalcustomer' => Customer::count(),
        ];
    }

    public function exportAllCustomers(string $exportOption)
    {
        $customers = Customer::all(); // Fetch all customers

        if ($exportOption === 'pdf') {
            $html = view('admin.customer.export-pdf', compact('customers'))->render();
            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            return $dompdf->stream('customers.pdf');
        }

        if ($exportOption === 'csv') {
            $headers = [
                'Content-type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename=customers.csv',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];

            $callback = function () use ($customers) {
                $file = fopen('php://output', 'w');
                fputcsv($file, [
                    'Name',
                    'Address',
                    'Phone Number',
                    'Payment Terms',
                    'Email',
                ]);

                foreach ($customers as $customer) {
                    fputcsv($file, [
                        $customer->name,
                        $customer->address,
                        $customer->phone_number,
                        $customer->payment_terms,
                        $customer->email,
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        return null;
    }
}
