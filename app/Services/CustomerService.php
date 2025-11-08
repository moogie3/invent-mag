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

        Customer::create($data);

        return ['success' => true, 'message' => 'Customer created successfully.'];
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
            if ($customer->image) {
                Storage::delete('public/image/' . basename($customer->image));
            }
            $data['image'] = $this->storeImage($data['image']);
        }

        $customer->update($data);

        return ['success' => true, 'message' => 'Customer updated successfully.'];
    }

    public function deleteCustomer(Customer $customer)
    {
        if ($customer->image) {
            Storage::delete('public/image/' . basename($customer->image));
        }

        $customer->delete();

        return ['success' => true, 'message' => 'Customer deleted successfully.'];
    }

    public function getHistoricalPurchases(Customer $customer)
    {
        $sales = Sales::where('customer_id', $customer->id)
            ->with('salesItems.product')
            ->orderBy('order_date', 'desc')
            ->get();

        $historicalPurchases = [];
        $latestProductPrices = [];

        foreach ($sales as $sale) {
            foreach ($sale->salesItems as $item) {
                if ($item->product) {
                    $productId = $item->product->id;
                    $purchaseDate = $sale->order_date;

                    $historicalPurchases[] = [
                        'sale_id' => $sale->id,
                        'invoice' => $sale->invoice,
                        'order_date' => $purchaseDate,
                        'product_id' => $productId,
                        'product_name' => $item->product->name,
                        'quantity' => $item->quantity,
                        'price_at_purchase' => $item->price,
                        'line_total' => $item->total,
                    ];

                    if (!isset($latestProductPrices[$productId]) || $purchaseDate > $latestProductPrices[$productId]['date']) {
                        $latestProductPrices[$productId] = [
                            'price' => $item->price,
                            'date' => $purchaseDate,
                        ];
                    }
                }
            }
        }

        foreach ($historicalPurchases as &$purchase) {
            $productId = $purchase['product_id'];
            if (isset($latestProductPrices[$productId])) {
                $purchase['customer_latest_price'] = $latestProductPrices[$productId]['price'];
            } else {
                $purchase['customer_latest_price'] = $purchase['price_at_purchase'];
            }
        }

        return $historicalPurchases;
    }

    private function storeImage($image): string
    {
        $imageName = Str::random(10) . '_' . $image->getClientOriginalName();
        $image->storeAs('public/image', $imageName, 'public'); // Explicitly specify the 'public' disk
        return $imageName;
    }

    public function getCustomerMetrics()
    {
        return [
            'totalcustomer' => Customer::count(),
        ];
    }
}
