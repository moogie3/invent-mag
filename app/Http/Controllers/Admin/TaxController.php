<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use App\Services\TaxService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaxController extends Controller
{
    protected TaxService $taxService;

    public function __construct(TaxService $taxService)
    {
        $this->taxService = $taxService;
    }

    public function index(Request $request)
    {
        $entries = $request->input('entries', 10);
        $taxes = Tax::orderBy('name')->paginate($entries);
        $totalTaxes = Tax::count();

        return view('admin.tax.tax', compact('taxes', 'totalTaxes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('taxes', 'name')->where(fn ($q) => $q->where('tenant_id', app('currentTenant')->id)),
            ],
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'required|in:'.implode(',', Tax::TYPES),
            'description' => 'nullable|string|max:500',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        try {
            $this->taxService->create($validated);

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => __('messages.tax_created')]);
            }

            return redirect()->route('admin.setting.tax')->with('success', __('messages.tax_created'));
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, Tax $tax)
    {
        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('taxes', 'name')
                    ->where(fn ($q) => $q->where('tenant_id', app('currentTenant')->id))
                    ->ignore($tax->id),
            ],
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'required|in:'.implode(',', Tax::TYPES),
            'description' => 'nullable|string|max:500',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        try {
            $this->taxService->update($tax, $validated);

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => __('messages.tax_updated')]);
            }

            return redirect()->route('admin.setting.tax')->with('success', __('messages.tax_updated'));
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function destroy(Request $request, Tax $tax)
    {
        try {
            $this->taxService->delete($tax);

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => __('messages.tax_deleted')]);
            }

            return redirect()->route('admin.setting.tax')->with('success', __('messages.tax_deleted'));
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function setDefault(Request $request, Tax $tax)
    {
        try {
            $this->taxService->setDefault($tax);

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => __('messages.tax_default_set')]);
            }

            return redirect()->route('admin.setting.tax')->with('success', __('messages.tax_default_set'));
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function getTaxRate(Request $request)
    {
        $taxId = $request->input('tax_id');

        if (! $taxId) {
            $tax = $this->taxService->getDefault();
        } else {
            $tax = $this->taxService->findById($taxId);
        }

        return response()->json([
            'tax_rate' => $tax ? (float) $tax->rate : 0,
            'tax_type' => $tax ? $tax->type : 'none',
            'tax_name' => $tax ? $tax->name : null,
            'tax_id' => $tax ? $tax->id : null,
        ]);
    }
}
