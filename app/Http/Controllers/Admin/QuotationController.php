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

        return view('admin.quotations.quotation-create', compact('customers', 'products', 'taxes', 'quotation'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'quotation_date' => 'required|date',
            'valid_until' => 'nullable|date',
            'products' => 'required|json',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'discount_total' => 'nullable|numeric|min:0',
            'discount_total_type' => 'nullable|in:fixed,percentage',
            'tax_id' => 'nullable|exists:taxes,id',
            'pph_tax_id' => 'nullable|exists:taxes,id',
        ]);

        try {
            DB::beginTransaction();

            $products = json_decode($request->products, true);
            $subtotal = 0;
            foreach ($products as $item) {
                $subtotal += ($item['quantity'] ?? 1) * ($item['price'] ?? 0);
            }

            $discountType = $request->discount_total_type ?? 'percentage';
            $discountValue = $request->discount_total ?? 0;
            $discountAmount = $discountType === 'percentage' ? ($subtotal * $discountValue / 100) : $discountValue;

            $taxId = $request->tax_id;
            $tax = $taxId ? Tax::find($taxId) : (Tax::default()->first() ?? Tax::active()->first());
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
            $pphRate = $pphTax ? (float) $pphTax->rate : 0;
            $pphAmount = SalesHelper::calculatePphAmount($taxableAmount, $pphRate);
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
        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $taxes = Tax::active()->orderBy('name')->get();

        return view('admin.quotations.quotation-edit', compact('quotation', 'customers', 'products', 'taxes'));
    }

    public function update(Request $request, $id)
    {
        $quotation = Quotation::findOrFail($id);

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'quotation_date' => 'required|date',
            'valid_until' => 'nullable|date',
            'products' => 'required|json',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'discount_total' => 'nullable|numeric|min:0',
            'discount_total_type' => 'nullable|in:fixed,percentage',
            'tax_id' => 'nullable|exists:taxes,id',
            'pph_tax_id' => 'nullable|exists:taxes,id',
        ]);

        try {
            DB::beginTransaction();

            $products = json_decode($request->products, true);
            $subtotal = 0;
            foreach ($products as $item) {
                $subtotal += ($item['quantity'] ?? 1) * ($item['price'] ?? 0);
            }

            $discountType = $request->discount_total_type ?? 'percentage';
            $discountValue = $request->discount_total ?? 0;
            $discountAmount = $discountType === 'percentage' ? ($subtotal * $discountValue / 100) : $discountValue;

            $taxId = $request->tax_id;
            $tax = $taxId ? Tax::find($taxId) : (Tax::default()->first() ?? Tax::active()->first());
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
            $pphRate = $pphTax ? (float) $pphTax->rate : 0;
            $pphAmount = SalesHelper::calculatePphAmount($taxableAmount, $pphRate);
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
            $productsData = [];
            foreach ($items as $item) {
                $productsData[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'discount' => $item['discount'] ?? 0,
                ];
            }

            $sales = Sales::create([
                'customer_id' => $quotation->customer_id,
                'user_id' => auth()->id(),
                'warehouse_id' => Product::getMainWarehouseId(),
                'order_date' => now(),
                'due_date' => now()->addDays(30),
                'payment_type' => 'credit',
                'order_discount' => $quotation->discount_amount,
                'order_discount_type' => $quotation->discount_type,
                'tax_id' => $quotation->tax_id,
                'tax_rate' => $quotation->tax_rate,
                'total_tax' => $quotation->tax_amount,
                'tax_type' => $quotation->tax_type,
                'total' => $quotation->total,
                'status' => 'pending',
                'invoice' => $this->salesService->generateInvoiceNumber(),
                'type' => Sales::TYPE_REGULAR,
                'notes' => $quotation->notes,
                'terms_conditions' => $quotation->terms_conditions,
            ]);

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

    private function generateQuotationNumber(): string
    {
        $prefix = 'QTN-'.date('Ymd');
        $last = Quotation::where('quotation_number', 'like', "{$prefix}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($last) {
            $lastNum = (int) substr($last->quotation_number, -4);
            $newNum = $lastNum + 1;
        } else {
            $newNum = 1;
        }

        return "{$prefix}-".str_pad($newNum, 4, '0', STR_PAD_LEFT);
    }
}
