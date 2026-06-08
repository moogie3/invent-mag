<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CmsController extends Controller
{
    public function index()
    {
        $content = $this->getCmsContent();

        return view('superadmin.cms.index', compact('content'));
    }

    public function update(Request $request)
    {
        $rules = [
            'hero_title' => 'nullable|string|max:255',
            'hero_subtitle' => 'nullable|string|max:500',
            'hero_cta_text' => 'nullable|string|max:100',
            'hero_cta_url' => 'nullable|string|max:255',
            'hero_cta_demo_text' => 'nullable|string|max:100',
            'hero_cta_demo_url' => 'nullable|string|max:255',
            'features_title' => 'nullable|string|max:255',
            'features_subtitle' => 'nullable|string|max:500',
            'features_description' => 'nullable|string|max:1000',
            'features_enterprise_heading' => 'nullable|string|max:255',
            'pricing_badge' => 'nullable|string|max:255',
            'pricing_title' => 'nullable|string|max:255',
            'pricing_subtitle' => 'nullable|string|max:500',
            'pricing_no_credit_badge' => 'nullable|string|max:255',
            'pricing_trial_badge' => 'nullable|string|max:255',
            'pricing_cancel_badge' => 'nullable|string|max:255',
            'pricing_most_popular_badge' => 'nullable|string|max:255',
            'pricing_trial_included' => 'nullable|string|max:255',
            'pricing_enterprise_support' => 'nullable|string|max:255',
            'cta_title' => 'nullable|string|max:255',
            'cta_description' => 'nullable|string|max:500',
            'contact_email' => 'nullable|email|max:255',
            'footer_text' => 'nullable|string|max:500',
        ];

        for ($i = 1; $i <= 11; $i++) {
            $rules["feature_card_{$i}_title"] = 'nullable|string|max:255';
            $rules["feature_card_{$i}_desc"] = 'nullable|string|max:1000';
        }

        $data = $request->validate($rules);

        $path = storage_path('app/cms.json');
        $existing = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
        $merged = array_merge($existing, $data);

        file_put_contents($path, json_encode($merged, JSON_PRETTY_PRINT));

        return redirect()->route('superadmin.cms.index')
            ->with('success', 'Landing page content updated successfully.');
    }

    private function getCmsContent(): array
    {
        $defaults = [
            'hero_title' => 'The All-In-One ERP to Streamline Your Business',
            'hero_subtitle' => 'From inventory and sales to a full accounting suite, Invent-MAG provides the tools you need to make smarter, data-driven decisions and accelerate your growth.',
            'hero_cta_text' => 'Start 7-Day Free Trial',
            'hero_cta_url' => '/signup',
            'hero_cta_demo_text' => 'Try Live Demo',
            'hero_cta_demo_url' => 'https://demo-pro.invent-mag.web.id/admin/login',
            'features_title' => 'Everything You Need',
            'features_subtitle' => 'A Better Way to Manage Your Business',
            'features_description' => 'Invent-MAG isn\'t just another tool, it\'s a complete ecosystem designed to give you a bird\'s-eye view of your entire operation, from inventory to accounting.',
            'features_enterprise_heading' => 'Enterprise Features',
            'feature_card_1_title' => 'Inventory & Product Management',
            'feature_card_1_desc' => 'Full product cataloging, real-time stock tracking across multiple warehouses, physical stock opname with variance tracking, warehouse-to-warehouse stock transfers, low-stock alerts, barcode support, and expiry tracking.',
            'feature_card_2_title' => 'Sales & CRM',
            'feature_card_2_desc' => 'Complete sales order management, Point of Sale (POS), customer database with purchase history, and sales pipeline for opportunity tracking.',
            'feature_card_3_title' => 'Purchase & SRM',
            'feature_card_3_desc' => 'Full purchase order lifecycle, supplier management with historical data, and efficient purchase return processing.',
            'feature_card_4_title' => 'Financial & Accounting Suite',
            'feature_card_4_desc' => 'Chart of Accounts, General Ledger, Manual Journal Entries, Trial Balance, Income Statement, Balance Sheet, and AR/AP Aging Reports.',
            'feature_card_5_title' => 'HR & Payroll',
            'feature_card_5_desc' => 'Comprehensive employee attendance tracking, automated salaried proration, customizable allowances, and automated two-step accounting sync.',
            'feature_card_6_title' => 'Multi-Currency Support',
            'feature_card_6_desc' => 'Handle transactions in multiple currencies with configurable settings for symbols, decimal places, and exchange rates.',
            'feature_card_7_title' => 'Returns Management',
            'feature_card_7_desc' => 'Complete sales and purchase return workflows with automatic stock adjustments and accounting entries.',
            'feature_card_8_title' => 'User & Role Management',
            'feature_card_8_desc' => 'Secure access control with 79 granular permissions. Assign roles like Superuser, Staff, or POS operator.',
            'feature_card_9_title' => 'Sales Forecasting AI',
            'feature_card_9_desc' => 'AI-powered sales predictions using Holt-Winters algorithms and linear regression for accurate demand forecasting.',
            'feature_card_10_title' => 'RESTful API Access',
            'feature_card_10_desc' => 'Full API access with Sanctum authentication. Integrate with external systems and build custom integrations.',
            'feature_card_11_title' => 'Audit Trail & Compliance',
            'feature_card_11_desc' => 'Comprehensive audit logging for all system changes. Track user actions, modifications, and maintain compliance records.',
            'pricing_badge' => 'Simple, transparent pricing',
            'pricing_title' => 'One platform, every tool your business needs',
            'pricing_subtitle' => 'From inventory tracking to full double-entry accounting &mdash; pick the plan that matches where your business is today.',
            'pricing_no_credit_badge' => 'No credit card required',
            'pricing_trial_badge' => '7-day free trial',
            'pricing_cancel_badge' => 'Cancel anytime',
            'pricing_most_popular_badge' => 'most popular',
            'pricing_trial_included' => '7 days free trial included',
            'pricing_enterprise_support' => 'Enterprise support & SLA',
            'cta_title' => 'Ready to streamline your business?',
            'cta_description' => 'Join thousands of businesses using Invent-MAG to manage inventory, sales, and accounting in one place.',
            'contact_email' => 'jefrydwijaya3@gmail.com',
            'footer_text' => '© ' . date('Y') . ' Invent-MAG. All rights reserved.',
        ];

        $path = storage_path('app/cms.json');
        if (!file_exists($path)) {
            return $defaults;
        }

        return array_merge($defaults, json_decode(file_get_contents($path), true) ?? []);
    }
}
