<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class FeatureController extends Controller
{
    public function index()
    {
        $plans = Plan::ordered()->get();
        $allFeatures = $this->getAllFeatures();

        return view('superadmin.features.index', compact('plans', 'allFeatures'));
    }

    public function update(Request $request)
    {
        $features = $request->input('features', []);

        foreach ($features as $planId => $planFeatures) {
            $plan = Plan::findOrFail($planId);
            $plan->update(['features' => array_values($planFeatures)]);
        }

        // Plans not submitted get empty features
        $submittedPlanIds = array_keys($features);
        Plan::whereNotIn('id', $submittedPlanIds)->update(['features' => []]);

        return redirect()->route('superadmin.features.index')
            ->with('success', 'Feature assignments updated successfully.');
    }

    private function getAllFeatures(): array
    {
        return [
            'purchase_order'    => 'Purchase Order',
            'purchase_return'   => 'Purchase Return',
            'sales_order'       => 'Sales Order',
            'sales_return'      => 'Sales Return',
            'pos'               => 'Point of Sale',
            'product'           => 'Product Management',
            'customer'          => 'Customer Management',
            'supplier'          => 'Supplier Management',
            'warehouse'         => 'Warehouse Management',
            'stock_opname'      => 'Stock Opname',
            'stock_transfer'    => 'Stock Transfer',
            'stock_adjustment'  => 'Stock Adjustment',
            'category'          => 'Category Management',
            'unit'              => 'Unit Management',
            'tax'               => 'Tax Management',
            'multi_currency'    => 'Multi-Currency',
            'financial_reports' => 'Financial Reports',
            'crm'               => 'CRM & Sales Pipeline',
            'srm'               => 'Supplier Relationship Mgmt',
            'hr'                => 'HR & Payroll',
            'accounting'        => 'Accounting',
            'audit_compliance'  => 'Audit & Compliance',
            'backup'            => 'Backup & Restore',
        ];
    }
}
