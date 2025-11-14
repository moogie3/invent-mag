<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Sales;
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
        if (Customer::where('name', $data['name'])->exists()) {
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
        if (Customer::where('name', $data['name'])->exists()) {
            return ['success' => false, 'message' => 'This customer already exists.'];
        }

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
}
