<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\SalesHelper;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Sales;
use App\Models\Tax;
use App\Models\User;
use App\Rules\LineDiscountWithinPrice;
use App\Rules\TaxIsSelectable;
use App\Services\SalesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuotationController extends Controller
{
    protected $salesService;

    public function __construct(SalesService $salesService)
    {
        $this->salesService = $salesService;
    }

    public function index(Request $request)
    {
        $entries = $request->input('entries', 10);
        $query = Quotation::with('customer');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('quotation_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('quotation_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('quotation_date', '<=', $dateTo);
        }

        $quotations = $query->orderBy('created_at', 'desc')->paginate($entries);
        $quotations->appends($request->all());

        $allQuotations = Quotation::all();
        $statusCounts = [
            'draft' => $allQuotations->where('status', 'draft')->count(),
            'sent' => $allQuotations->where('status', 'sent')->count(),
            'accepted' => $allQuotations->where('status', 'accepted')->count(),
            'rejected' => $allQuotations->where('status', 'rejected')->count(),
            'expired' => $allQuotations->where('status', 'expired')->count(),
            'converted' => $allQuotations->where('status', 'converted')->count(),
        ];
        $totalAmount = $allQuotations->sum('total');

        return view('admin.quotations.index', compact('quotations', 'statusCounts', 'totalAmount'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $taxes = Tax::active()->orderBy('name')->get();
        $quotation = new Quotation;
        // Resolved, not the stored flag: this has to be the same tax the
        // service would book with if the form sent no tax_id at all.
        $defaultTax = Tax::effectiveDefault();

        return view('admin.quotations.quotation-create', compact('customers', 'products', 'taxes', 'quotation', 'defaultTax'));
    }

    public function store(Request $request)
    {
        // The quotation form posts its lines as `items[...][field]` inputs,
        // while sales and purchases post one hidden `products` JSON blob.
        // Accept both: `products` when present (existing clients), the item
        // inputs otherwise, so the form works with or without JavaScript.
        $items = $request->input('items');
        if (! $request->filled('products') && is_array($items) && $items !== []) {
            $request->merge(['products' => json_encode(array_values($items))]);
        }

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'quotation_date' => 'required|date',
            'valid_until' => 'nullable|date',
            'products' => ['required', 'json', new LineDiscountWithinPrice],
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'discount_total' => 'nullable|numeric|min:0',
            'discount_total_type' => 'nullable|in:fixed,percentage',
            'tax_id' => ['nullable', new TaxIsSelectable],
            'pph_tax_id' => ['nullable', new TaxIsSelectable(enforceJurisdiction: false)],
        ]);

        try {
            DB::beginTransaction();

            $products = json_decode($request->products, true);
            [$products, $subtotal] = $this->priceItems($products);

            $discountType = $request->discount_total_type ?? 'percentage';
            $discountValue = $request->discount_total ?? 0;
            $discountAmount = $discountType === 'percentage' ? ($subtotal * $discountValue / 100) : $discountValue;

            $taxId = $request->tax_id;
            $tax = $taxId ? Tax::find($taxId) : Tax::effectiveDefault();
            $taxRate = $tax ? (float) $tax->rate : 0;
            $taxType = $tax ? $tax->type : 'none';
            $afterDiscount = $subtotal - $discountAmount;
            $taxAmount = SalesHelper::calculateTaxAmount($afterDiscount, $taxRate, $taxType);

            if ($taxType === 'inclusive') {
                $total = $afterDiscount;
            } else {
                $total = $afterDiscount + $taxAmount;
            }

            $taxableAmount = $afterDiscount;

            $pphTaxId = $request->pph_tax_id ?? null;
            $pphTax = $pphTaxId ? Tax::find($pphTaxId) : null;
            // Gate like the snapshot below: for a non-Indonesian tenant the
            // total must not shrink, since pph_amount is recorded as 0.
            $pphApplied = app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false);
            $pphRate = $pphApplied && $pphTax ? (float) $pphTax->rate : 0;
            $pphAmount = $pphApplied ? SalesHelper::calculatePphAmount($taxableAmount, $pphRate) : 0;
            $total = max(0, $total - $pphAmount);

            $quotation = Quotation::create([
                'quotation_number' => $this->generateQuotationNumber(),
                'customer_id' => $request->customer_id,
                'user_id' => auth()->id(),
                'quotation_date' => $request->quotation_date,
                'valid_until' => $request->valid_until,
                'subtotal' => $subtotal,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $discountAmount,
                'tax_id' => $tax?->id,
                'tax_type' => $taxType,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total' => max(0, $total),
                'status' => 'draft',
                'items' => $products,
                'notes' => $request->notes,
                'terms_conditions' => $request->terms_conditions,
                'pph_enabled' => app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false),
                'pph_rate' => app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false) ? $pphRate : null,
                'pph_amount' => app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false) ? $pphAmount : 0,
                'pph_tax_id' => app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false) ? $pphTax?->id : null,
            ]);

            DB::commit();

            return redirect()->route('admin.quotations.show', $quotation->id)
                ->with('success', 'Quotation created successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Quotation creation failed: '.$e->getMessage(), ['exception' => $e]);

            return back()->withErrors(['error' => 'Something went wrong: '.$e->getMessage()])->withInput();
        }
    }

    public function show($id)
    {
        $quotation = Quotation::with('customer', 'user', 'convertedToSales')->findOrFail($id);

        return view('admin.quotations.show', compact('quotation'));
    }

    public function edit($id)
    {
        $quotation = Quotation::with('customer')->findOrFail($id);

        // A converted quotation is the source document of a sale that is
        // already on the books and already linked from this row. Editing it
        // now would only make the quote disagree with the sale it produced,
        // so the view is where it goes rather than the form.
        if ($quotation->converted_to_sales_id) {
            return redirect()->route('admin.quotations.show', $id);
        }

        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $taxes = Tax::active()->orderBy('name')->get();
        $defaultTax = Tax::effectiveDefault();

        return view('admin.quotations.quotation-edit', compact('quotation', 'customers', 'products', 'taxes', 'defaultTax'));
    }

    public function update(Request $request, $id)
    {
        $quotation = Quotation::findOrFail($id);

        // Same refusal as edit(): the sale already exists from this
        // quotation, so changing the quotation would leave the two linked
        // documents reporting different totals. Refused before validation
        // so the message is the reason rather than a field error.
        if ($quotation->converted_to_sales_id) {
            return redirect()->back()->with('error', 'This quotation has already been converted to a sales order and can no longer be edited.');
        }

        // Same shape as store(): the form posts `items[...]` inputs, the
        // legacy clients post `products` JSON. Normalize before validating.
        $items = $request->input('items');
        if (! $request->filled('products') && is_array($items) && $items !== []) {
            $request->merge(['products' => json_encode(array_values($items))]);
        }

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'quotation_date' => 'required|date',
            'valid_until' => 'nullable|date',
            'products' => ['required', 'json', new LineDiscountWithinPrice],
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'discount_total' => 'nullable|numeric|min:0',
            'discount_total_type' => 'nullable|in:fixed,percentage',
            'tax_id' => ['nullable', new TaxIsSelectable],
            'pph_tax_id' => ['nullable', new TaxIsSelectable(enforceJurisdiction: false)],
        ]);

        try {
            DB::beginTransaction();

            $products = json_decode($request->products, true);
            [$products, $subtotal] = $this->priceItems($products);

            $discountType = $request->discount_total_type ?? 'percentage';
            $discountValue = $request->discount_total ?? 0;
            $discountAmount = $discountType === 'percentage' ? ($subtotal * $discountValue / 100) : $discountValue;

            $taxId = $request->tax_id;
            $tax = $taxId ? Tax::find($taxId) : Tax::effectiveDefault();
            $taxRate = $tax ? (float) $tax->rate : 0;
            $taxType = $tax ? $tax->type : 'none';
            $afterDiscount = $subtotal - $discountAmount;
            $taxAmount = SalesHelper::calculateTaxAmount($afterDiscount, $taxRate, $taxType);

            if ($taxType === 'inclusive') {
                $total = $afterDiscount;
            } else {
                $total = $afterDiscount + $taxAmount;
            }

            $taxableAmount = $afterDiscount;

            $pphTaxId = $request->pph_tax_id ?? null;
            $pphTax = $pphTaxId ? Tax::find($pphTaxId) : null;
            // Gate like the snapshot below: for a non-Indonesian tenant the
            // total must not shrink, since pph_amount is recorded as 0.
            $pphApplied = app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false);
            $pphRate = $pphApplied && $pphTax ? (float) $pphTax->rate : 0;
            $pphAmount = $pphApplied ? SalesHelper::calculatePphAmount($taxableAmount, $pphRate) : 0;
            $total = max(0, $total - $pphAmount);

            $quotation->update([
                'customer_id' => $request->customer_id,
                'quotation_date' => $request->quotation_date,
                'valid_until' => $request->valid_until,
                'subtotal' => $subtotal,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $discountAmount,
                'tax_id' => $tax?->id,
                'tax_type' => $taxType,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total' => max(0, $total),
                'items' => $products,
                'notes' => $request->notes,
                'terms_conditions' => $request->terms_conditions,
                'pph_enabled' => app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false),
                'pph_rate' => app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false) ? $pphRate : null,
                'pph_amount' => app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false) ? $pphAmount : 0,
                'pph_tax_id' => app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false) ? $pphTax?->id : null,
            ]);

            DB::commit();

            return redirect()->route('admin.quotations.show', $quotation->id)
                ->with('success', 'Quotation updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Quotation update failed: '.$e->getMessage(), ['exception' => $e]);

            return back()->withErrors(['error' => 'Something went wrong: '.$e->getMessage()])->withInput();
        }
    }

    public function destroy($id)
    {
        $quotation = Quotation::findOrFail($id);

        try {
            $quotation->delete();

            return redirect()->route('admin.quotations')
                ->with('success', 'Quotation deleted successfully.');
        } catch (\Throwable $e) {
            return redirect()->route('admin.quotations')
                ->with('error', 'Error deleting quotation. Please try again.');
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:draft,sent,accepted,rejected,expired,converted',
        ]);

        $quotation = Quotation::findOrFail($id);
        $quotation->update(['status' => $request->status]);

        return redirect()->back()->with('success', 'Quotation status updated.');
    }

    public function convertToSales($id)
    {
        $quotation = Quotation::with('customer')->findOrFail($id);

        if ($quotation->status !== 'accepted') {
            return redirect()->back()->with('error', 'Only accepted quotations can be converted to sales orders.');
        }

        if ($quotation->converted_to_sales_id) {
            return redirect()->back()->with('error', 'This quotation has already been converted to a sales order.');
        }

        try {
            DB::beginTransaction();

            $items = $quotation->items ?? [];
            $lineItems = [];
            foreach ($items as $item) {
                $lineItems[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'discount' => $item['discount'] ?? 0,
                    'discount_type' => $item['discount_type'] ?? $item['discountType'] ?? 'fixed',
                ];
            }

            if ($lineItems === []) {
                throw new \Exception('This quotation has no line items to convert.');
            }

            // PPN snapshot — same gate as createSale(): the e-Faktur fields
            // only apply when the tenant is Indonesian AND the quotation's
            // tax is a PPN tax. pph_* are copied from the quotation, which
            // already applied the identical gate when it was saved (the
            // quotation's total is net of pph_amount, so the sale must carry
            // it too or the journal would not balance against the total).
            $tax = $quotation->tax_id ? Tax::find($quotation->tax_id) : null;
            $ppnApplied = app('currentTenant')->isIndonesian() && ($tax?->is_ppn ?? false);

            $sales = Sales::create([
                'customer_id' => $quotation->customer_id,
                'user_id' => auth()->id(),
                'warehouse_id' => Product::getMainWarehouseId(),
                'order_date' => now(),
                'due_date' => now()->addDays(30),
                'payment_type' => 'credit',
                // Store the discount the way createSale() and updateSale()
                // do: the raw value together with the type that describes it.
                // discount_amount is the WORKED-OUT figure (10% of 1,000 =
                // 100), so pairing it with discount_type='percentage' told
                // every re-calculating reader to take 100% off instead: the
                // sales view, invoice PDF and email all showed 0, balance due
                // showed 0, and saving that sale re-journaled it as zero
                // revenue against the full cost of goods.
                'order_discount' => $quotation->discount_value ?? 0,
                'order_discount_type' => $quotation->discount_type ?? 'fixed',
                'tax_id' => $quotation->tax_id,
                'tax_rate' => $quotation->tax_rate,
                'total_tax' => $quotation->tax_amount,
                'tax_type' => $quotation->tax_type,
                'total' => $quotation->total,
                // 'Unpaid' like every other sale path — the old 'pending'
                // status was a dead end (no transition existed and the
                // dashboards only count Unpaid/Partial).
                'status' => 'Unpaid',
                'invoice' => $this->salesService->generateInvoiceNumber(),
                'type' => Sales::TYPE_REGULAR,
                'customer_credit_used' => 0,
                'notes' => $quotation->notes,
                'terms_conditions' => $quotation->terms_conditions,
                'ppn_enabled' => $ppnApplied,
                'ppn_rate' => $ppnApplied ? $quotation->tax_rate : null,
                'ppn_amount' => $ppnApplied ? $quotation->tax_amount : 0,
                'pph_enabled' => (bool) $quotation->pph_enabled,
                'pph_rate' => $quotation->pph_rate,
                'pph_amount' => $quotation->pph_amount ?? 0,
                'pph_tax_id' => $quotation->pph_tax_id,
            ]);

            // Items, stock movement, COGS and the sale journal — without
            // this call the converted sale reached the sales table but
            // never the ledger (and had no line items at all).
            $this->salesService->finalizeConvertedSale($sales, $lineItems);

            $quotation->update([
                'status' => 'converted',
                'converted_to_sales_id' => $sales->id,
            ]);

            DB::commit();

            return redirect()->route('admin.sales.show', $sales->id)
                ->with('success', 'Quotation converted to sales order successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Quotation conversion failed: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Failed to convert quotation: '.$e->getMessage());
        }
    }

    public function print($id)
    {
        $quotation = Quotation::with('customer', 'user')->findOrFail($id);
        $shopname = User::whereNotNull('shopname')->value('shopname');
        $address = User::whereNotNull('address')->value('address');

        return view('admin.quotations.quotation-print-pdf', compact('quotation', 'shopname', 'address'));
    }

    public function getProductDetails($id)
    {
        $product = Product::findOrFail($id);

        return response()->json([
            'price' => $product->selling_price,
            'name' => $product->name,
        ]);
    }

    public function modalViews($id)
    {
        try {
            $quotation = Quotation::with('customer', 'user')->findOrFail($id);

            return view('admin.layouts.modals.quotations.quotationsmodals-view', compact('quotation'));
        } catch (\Throwable $e) {
            Log::error("Error loading quotation modal view for ID {$id}: ".$e->getMessage());

            return response('<div class="alert alert-danger">Error loading quotation details.</div>', 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:quotations,id',
        ]);

        try {
            Quotation::whereIn('id', $request->ids)->delete();

            return response()->json(['success' => true, 'message' => 'Quotations deleted successfully.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error deleting quotations.'], 500);
        }
    }

    public function bulkExport(Request $request)
    {
        $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'integer|exists:quotations,id',
            'export_option' => 'required|string|in:pdf,csv',
        ]);

        try {
            $query = Quotation::with('customer');
            if ($request->ids) {
                $query->whereIn('id', $request->ids);
            }
            $quotations = $query->get();

            if ($request->export_option === 'csv') {
                $csv = "Quotation Number,Customer,Date,Total,Status\n";
                foreach ($quotations as $q) {
                    $csv .= "\"{$q->quotation_number}\",\"{$q->customer?->name}\",\"{$q->quotation_date?->format('Y-m-d')}\",{$q->total},\"{$q->status}\"\n";
                }

                return response($csv, 200, [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="quotations.csv"',
                ]);
            }

            $first = $quotations->first();
            if ($first) {
                return redirect()->route('admin.quotations.print', $first->id);
            }

            return back()->with('error', 'No quotations to export.');
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Export failed.'], 500);
        }
    }

    /**
     * Price the lines exactly as the sales and purchase documents do: a
     * fixed discount comes off every unit, a percentage comes off every
     * unit's price, and the line total is the net unit price times the
     * quantity. The subtotal is the sum of those totals, so a quotation
     * keeps agreeing with the sale it converts into once line discounts
     * are on the table (the old loop counted quantity times price and
     * silently ignored the discount on every line).
     *
     * `name` and `total` are worked out in the same pass because the
     * print, show and edit views read them from the stored JSON and the
     * raw request carries neither.
     *
     * @param  array<int|string, array<string, mixed>>  $products
     * @return array{0: array<int, array<string, mixed>>, 1: float}
     */
    private function priceItems(array $products): array
    {
        $productIds = array_filter(array_column($products, 'product_id'));
        $names = $productIds === [] ? [] : Product::whereIn('id', $productIds)->pluck('name', 'id');

        $items = [];
        $subtotal = 0.0;

        foreach (array_values($products) as $item) {
            $price = (float) ($item['price'] ?? 0);
            $quantity = (float) ($item['quantity'] ?? 1);
            $discount = (float) ($item['discount'] ?? 0);
            $discountType = $item['discount_type'] ?? $item['discountType'] ?? 'fixed';

            $lineTotal = round(SalesHelper::calculateTotal($price, $quantity, $discount, $discountType), 2);
            $subtotal += $lineTotal;

            $productId = $item['product_id'] ?? null;
            $item['name'] = ($item['name'] ?? '') !== '' ? $item['name'] : ($names[$productId] ?? null);
            $item['total'] = $lineTotal;
            $items[] = $item;
        }

        return [$items, round($subtotal, 2)];
    }

    private function generateQuotationNumber(): string
    {
        $prefix = 'QTN-'.date('Ymd');
        $last = Quotation::where('quotation_number', 'like', "{$prefix}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($last) {
            // Everything after the last dash, not the last four characters:
            // QTN-20261115-10000 reads as 10000, not 0000, so the sequence
            // does not wrap back to 0001 and trip the unique constraint once
            // a day passes ten thousand quotations.
            $lastNum = (int) substr($last->quotation_number, strrpos($last->quotation_number, '-') + 1);
            $newNum = $lastNum + 1;
        } else {
            $newNum = 1;
        }

        // Step past anything already on file so the sequence never proposes
        // a number this tenant already holds.
        do {
            $quotationNumber = "{$prefix}-".str_pad($newNum, 4, '0', STR_PAD_LEFT);
            $newNum++;
        } while (Quotation::where('quotation_number', $quotationNumber)->exists());

        return $quotationNumber;
    }
}
