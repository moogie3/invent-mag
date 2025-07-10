<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Customer;
use App\Models\Sales;
use App\Models\Product;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request){
        $entries = $request->input('entries', 10);
        $customers = Customer::paginate($entries);
        $totalcustomer = Customer::count();
        return view ('admin.customer.index', compact('customers','entries','totalcustomer'));
    }

    public function store(Request $request) {
        $request->validate([
            'name' => 'required',
            'address' => 'required',
            'phone_number' => 'required',
            'payment_terms' => 'required',
            'email' => 'nullable|email',
            'image' => 'nullable|image|mimes:jpeg,jpg,png',
        ]);

        $isCustomerExists = Customer::where('name', $request->name)->exists();

        if ($isCustomerExists) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'This customer already exists.', 'errors' => ['name' => ['This customer already exists.']]], 422);
            }
            return back()
            ->withErrors([
                'name' => 'This customer already exist'
            ])
            ->withInput();
        }

        $data = $request->except(['_token', 'image']);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = Str::random(10) . '_' . $image->getClientOriginalName();
            $image->storeAs('public/image', $imageName);
            $data['image'] = $imageName;
        }

        Customer::create($data);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Customer created successfully.']);
        }
        return redirect()->route('admin.customer')->with('success', 'Customer created');
    }

    /**
     * Quick create a customer from POS page
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function quickCreate(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'address' => 'required',
            'phone_number' => 'required',
            'payment_terms' => 'required',
            'email' => 'nullable|email',
            'image' => 'nullable|image|mimes:jpeg,jpg,png',
        ]);

        $isCustomerExists = Customer::where('name', $request->name)->exists();

        if ($isCustomerExists) {
            return response()->json([
                'success' => false,
                'message' => 'This customer already exists'
            ], 422);
        }

        $data = $request->except(['_token', 'image']);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = Str::random(10) . '_' . $image->getClientOriginalName();
            $image->storeAs('public/image', $imageName);
            $data['image'] = $imageName;
        }

        $customer = Customer::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully',
            'customer' => $customer
        ]);
    }

    public function update(Request $request, $id){
        $data = $request->except(["_token", 'image']);
        $request->validate([
            'name' => 'required',
            'address' => 'required',
            'phone_number' => 'required',
            'payment_terms' => 'required',
            'email' => 'nullable|email',
            'image' => 'nullable|image|mimes:jpeg,jpg,png',
        ]);

        $customers = Customer::find($id);

        if ($request->hasFile('image')) {
            $oldImagePath = 'public/image/' . basename($customers->image);

            if (!empty($customers->image) && Storage::exists($oldImagePath)) {
                Storage::delete($oldImagePath);
            }

            $image = $request->file('image');
            $imageName = Str::random(10) . '_' . $image->getClientOriginalName();
            $image->storeAs('public/image', $imageName);

            $data['image'] = $imageName;
        }

        $customers->update($data);
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Customer updated successfully.']);
        }
        return redirect()->route('admin.customer')->with('success', 'Customer updated');
    }

    public function destroy($id)
    {
        $customer = Customer::find($id);

        if (!empty($customer->image)) {
            Storage::delete('public/image/' . basename($customer->image));
        }

        $customer->delete();

        return redirect()->route('admin.customer')->with('success', 'Customer deleted');
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

                    // Store all purchases for the customer
                    $historicalPurchases[] = [
                        'sale_id' => $sale->id,
                        'invoice' => $sale->invoice,
                        'order_date' => $purchaseDate,
                        'product_id' => $productId, // Add product_id here
                        'product_name' => $item->product->name,
                        'quantity' => $item->quantity,
                        'price_at_purchase' => $item->price, // Price at the time of this specific purchase
                        'line_total' => $item->total,
                    ];

                    // Determine the latest price for this product for this customer
                    if (!isset($latestProductPrices[$productId]) || $purchaseDate > $latestProductPrices[$productId]['date']) {
                        $latestProductPrices[$productId] = [
                            'price' => $item->price,
                            'date' => $purchaseDate,
                        ];
                    }
                }
            }
        }

        // Add the latest price to each historical purchase entry
        foreach ($historicalPurchases as &$purchase) {
            $productId = $purchase['product_id'];
            if (isset($latestProductPrices[$productId])) {
                $purchase['customer_latest_price'] = $latestProductPrices[$productId]['price'];
            } else {
                $purchase['customer_latest_price'] = $purchase['price_at_purchase']; // Fallback
            }
        }


        return response()->json([
            'success' => true,
            'historical_purchases' => $historicalPurchases,
        ]);
    }
}