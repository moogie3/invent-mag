<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CmsController extends Controller
{
    /**
     * Get marketing-site CMS content.
     *
     * Returns the landing-page copy (hero, features, pricing, CTA, footer)
     * served to the Astro marketing site at build time. Responses are cached
     * for 1 hour.
     *
     * @group CMS Content
     * Public landing-page content served to the Astro marketing site at build time.
     *
     * @unauthenticated
     */
    public function show(): JsonResponse
    {
        $defaults = $this->getDefaults();

        $path = storage_path('app/cms.json');
        if (! file_exists($path)) {
            return response()->json($defaults);
        }

        $stored = json_decode(file_get_contents($path), true) ?? [];
        $content = array_merge($defaults, $stored);

        return response()->json($content)->header('Cache-Control', 'public, max-age=3600');
    }

    private function getDefaults(): array
    {
        return [
            'hero_title' => 'The All-In-One ERP to Streamline Your Business',
            'hero_subtitle' => 'From inventory and sales to a full accounting suite, Invent-MAG provides the tools you need to make smarter, data-driven decisions and accelerate your growth.',
            'hero_cta_text' => 'Start Free',
            'hero_cta_url' => '/admin/login',
            'hero_cta_demo_text' => 'Open App',
            'hero_cta_demo_url' => '/admin/login',
            'features_title' => 'Everything You Need',
            'features_subtitle' => 'A Better Way to Manage Your Business',
            'features_description' => 'Invent-MAG isn\'t just another tool, it\'s a complete ecosystem designed to give you a bird\'s-eye view of your entire operation, from inventory to accounting.',
            'features_enterprise_heading' => 'Enterprise Features',
            'feature_card_1_title' => 'Inventory & Product Management',
            'feature_card_1_desc' => 'Full product cataloging, real-time stock tracking across multiple warehouses, physical stock opname with variance tracking, warehouse-to-warehouse stock transfers, low-stock alerts, barcode support, and expiry tracking.',
            'feature_card_2_title' => 'Sales, CRM & Quotations',
            'feature_card_2_desc' => 'Complete sales order management, Point of Sale (POS), customer database with purchase history, sales pipeline for opportunity tracking, and quotation generation with full lifecycle management including approval workflows, revision history, and seamless conversion to invoices or sales orders.',
            'feature_card_3_title' => 'Purchase & SRM',
            'feature_card_3_desc' => 'Full purchase order lifecycle, supplier management with historical data, and efficient purchase return processing.',
            'feature_card_4_title' => 'Financial & Accounting Suite',
            'feature_card_4_desc' => 'Chart of Accounts, General Ledger, Manual Journal Entries, Trial Balance, Income Statement, Balance Sheet, AR/AP Aging Reports, and Bank Reconciliation.',
            'feature_card_5_title' => 'HR & Payroll',
            'feature_card_5_desc' => 'Comprehensive employee attendance tracking, automated salaried proration, customizable allowances, and automated two-step accounting sync.',
            'feature_card_6_title' => 'Multi-Currency Support',
            'feature_card_6_desc' => 'Configure your currency display with custom symbols, thousands and decimal separators, and decimal places for a consistent, localized experience.',
            'feature_card_7_title' => 'Returns Management',
            'feature_card_7_desc' => 'Complete sales and purchase return workflows with automatic stock adjustments and accounting entries.',
            'feature_card_8_title' => 'User & Role Management',
            'feature_card_8_desc' => 'Secure access control with granular permissions. Assign roles like Superuser, Staff, or POS operator.',
            'feature_card_9_title' => 'Sales Forecasting AI',
            'feature_card_9_desc' => 'AI-powered sales predictions using Holt-Winters algorithms and linear regression for accurate demand forecasting.',
            'feature_card_10_title' => 'RESTful API Access',
            'feature_card_10_desc' => 'Full API access with Sanctum authentication. Integrate with external systems and build custom integrations.',
            'feature_card_11_title' => 'Audit Trail & Compliance',
            'feature_card_11_desc' => 'Comprehensive audit logging for all system changes. Track user actions, modifications, and maintain compliance records.',
            'pricing_badge' => 'Personal use',
            'pricing_title' => 'One platform, every tool you need',
            'pricing_subtitle' => 'From inventory tracking to full double-entry accounting — all enterprise features included.',
            'pricing_no_credit_badge' => 'No subscription',
            'pricing_trial_badge' => 'Free forever',
            'pricing_cancel_badge' => 'No renewals',
            'pricing_most_popular_badge' => 'included',
            'pricing_trial_included' => 'All features included',
            'pricing_enterprise_support' => 'Enterprise features unlocked',
            'billing_rate' => 0,
            'cta_title' => 'Ready to manage your business?',
            'cta_description' => 'Run inventory, sales, and accounting in one place.',
            'contact_email' => config('app.contact_email'),
            'footer_text' => '© '.date('Y').' Invent-MAG. All rights reserved.',
        ];
    }
}
