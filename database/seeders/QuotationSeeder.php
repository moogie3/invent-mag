<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Tax;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class QuotationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantId = app('currentTenant')->id;

        $customers = Customer::where('tenant_id', $tenantId)->get();
        $products = Product::where('tenant_id', $tenantId)->get();
        $tax = Tax::where('tenant_id', $tenantId)->where('is_default', true)->first()
            ?? Tax::where('tenant_id', $tenantId)->where('is_active', true)->first();

        if ($customers->isEmpty() || $products->isEmpty()) {
            $this->command?->warn('Skipping QuotationSeeder for tenant '.app('currentTenant')->name.': Missing customers or products.');

            return;
        }

        $statuses = ['draft', 'sent', 'accepted', 'rejected', 'expired'];

        for ($i = 1; $i <= 5; $i++) {
            $customer = $customers->random();
            $selectedProducts = $products->random(min(3, $products->count()));

            $items = [];
            $subtotal = 0;

            foreach ($selectedProducts as $product) {
                $qty = rand(1, 5);
                $price = $product->selling_price ?? $product->price ?? 100;
                $subtotal += $qty * $price;
                $items[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'quantity' => $qty,
                    'price' => $price,
                    'discount' => 0,
                    'total' => $qty * $price,
                ];
            }

            $taxRate = $tax ? (float) $tax->rate : 0;
            $taxType = $tax ? $tax->type : 'none';
            $taxAmount = 0;

            if ($taxType === 'exclusive' && $taxRate > 0) {
                $taxAmount = round($subtotal * ($taxRate / 100), 2);
                $total = $subtotal + $taxAmount;
            } elseif ($taxType === 'inclusive' && $taxRate > 0) {
                $taxAmount = round($subtotal - ($subtotal / (1 + $taxRate / 100)), 2);
                $total = $subtotal;
            } else {
                $total = $subtotal;
            }

            $quotationDate = Carbon::now()->subDays(rand(1, 60));
            $prefix = 'QTN-'.$quotationDate->format('Ymd');
            $last = Quotation::withoutGlobalScopes()
                ->where('quotation_number', 'like', "{$prefix}-%")
                ->orderBy('id', 'desc')
                ->first();
            $newNum = $last ? (int) substr($last->quotation_number, -4) + 1 : 1;
            $quotationNumber = "{$prefix}-".str_pad($newNum, 4, '0', STR_PAD_LEFT);

            Quotation::create([
                'quotation_number' => $quotationNumber,
                'customer_id' => $customer->id,
                'user_id' => User::first()?->id,
                'quotation_date' => $quotationDate,
                'valid_until' => $quotationDate->copy()->addDays(30),
                'subtotal' => $subtotal,
                'discount_type' => 'fixed',
                'discount_value' => 0,
                'discount_amount' => 0,
                'tax_id' => $tax?->id,
                'tax_type' => $taxType,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'status' => $statuses[array_rand($statuses)],
                'items' => $items,
                'notes' => null,
                'terms_conditions' => null,
            ]);
        }
    }
}
