<?php

namespace App\Services;


use App\Services\Concerns\BuildsListSearch;
use App\Models\Customer;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomerService
{
    use BuildsListSearch;
    public function getCustomerIndexData(array $filters, int $entries)
    {
        $query = Customer::query();

        // Whole-catalog search — the paginator's COUNT(*) shares this WHERE.
        $this->applyListSearch($query, $filters, [
            'customers.name',
            'customers.phone_number',
            'customers.email',
            'customers.address',
            'customers.npwp',
        ]);

        $customers = $query->paginate($entries);
        $totalcustomer = Customer::count();

        $customerCredits = [];
        foreach ($customers as $customer) {
            $customerCredits[$customer->id] = $customer->activeCredits()->sum('available_amount');
        }

        return compact('customers', 'entries', 'totalcustomer', 'customerCredits');
    }

    public function createCustomer(array $data)
    {
        Validator::make($data, [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('customers', 'name')->where('tenant_id', auth()->user()->tenant_id),
            ],
            'npwp' => 'nullable|string|max:20',
            'pkp_status' => 'nullable|boolean',
            'pkp_date' => 'nullable|date',
            'alamat_npwp' => 'nullable|string|max:500',
        ])->validate();

        if (isset($data['image'])) {
            $data['image'] = $this->storeImage($data['image']);
        }

        $customer = Customer::create($data);

        return ['success' => true, 'message' => 'Customer created successfully.', 'customer' => $customer];
    }

    public function quickCreateCustomer(array $data)
    {
        Validator::make($data, [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('customers', 'name')->where('tenant_id', auth()->user()->tenant_id),
            ],
            'npwp' => 'nullable|string|max:20',
            'pkp_status' => 'nullable|boolean',
            'pkp_date' => 'nullable|date',
            'alamat_npwp' => 'nullable|string|max:500',
        ])->validate();

        if (isset($data['image'])) {
            $data['image'] = $this->storeImage($data['image']);
        }

        $customer = Customer::create($data);

        return ['success' => true, 'message' => 'Customer created successfully.', 'customer' => $customer];
    }

    public function updateCustomer(Customer $customer, array $data)
    {
        Validator::make($data, [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('customers', 'name')->where('tenant_id', auth()->user()->tenant_id)->ignore($customer->id),
            ],
            'npwp' => 'nullable|string|max:20',
            'pkp_status' => 'nullable|boolean',
            'pkp_date' => 'nullable|date',
            'alamat_npwp' => 'nullable|string|max:500',
        ])->validate();

        if (isset($data['image'])) {
            $oldImage = $customer->getRawOriginal('image');
            if ($oldImage) {
                Storage::disk('public')->delete('image/'.$oldImage);
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
            Storage::disk('public')->delete('image/'.$image);
        }

        $customer->delete();

        return ['success' => true, 'message' => 'Customer deleted successfully.'];
    }

    private function storeImage($image): string
    {
        $imageName = Str::random(10).'_'.$image->getClientOriginalName();
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
            $dompdf = new Dompdf;
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
                    'NPWP',
                    'PKP Status',
                    'PKP Date',
                    'Alamat NPWP',
                ]);

                foreach ($customers as $customer) {
                    fputcsv($file, [
                        $customer->name,
                        $customer->address,
                        $customer->phone_number,
                        $customer->payment_terms,
                        $customer->email,
                        $customer->npwp,
                        $customer->pkp_status ? 'Yes' : 'No',
                        $customer->pkp_date?->format('Y-m-d'),
                        $customer->alamat_npwp,
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        return null;
    }
}
