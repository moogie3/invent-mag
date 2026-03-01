<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Plan
    |--------------------------------------------------------------------------
    |
    | The plan slug assigned to new tenants when no plan is specified during
    | registration. This ensures backward compatibility.
    |
    */

    'default_plan' => 'starter',

    /*
    |--------------------------------------------------------------------------
    | Backward Compatibility
    |--------------------------------------------------------------------------
    |
    | When true, tenants without an assigned plan_id (legacy tenants created
    | before the plan system) will be treated as having enterprise-level
    | access. Set to false to force all tenants to have a plan.
    |
    */

    'legacy_full_access' => true,

    /*
    |--------------------------------------------------------------------------
    | Feature Definitions
    |--------------------------------------------------------------------------
    |
    | Maps feature slugs (stored in plans.features JSON) to human-readable
    | labels. Used for display in the upgrade prompt and plan comparison.
    |
    */

    'features' => [
        // Core features (all plans)
        'products'        => 'Product & Inventory Management',
        'inventory'       => 'Stock Tracking',
        'sales'           => 'Sales & Purchase Orders',
        'purchases'       => 'Purchase Order Management',
        'invoices'        => 'Invoice Generation (PDF)',
        'dashboard'       => 'Dashboard & Basic Reports',
        'basic_reports'   => 'Basic Reports',
        'customers'       => 'Customer Management',
        'suppliers'       => 'Supplier Management',

        // Professional features
        'pos'               => 'Point of Sale (POS)',
        'accounting'        => 'Full Accounting Suite',
        'crm'               => 'CRM & Sales Pipeline',
        'sales_pipeline'    => 'Sales Pipeline (Kanban)',
        'financial_reports' => 'Financial Reports & Multi-Currency',
        'multi_currency'    => 'Multi-Currency Support',
        'sales_returns'     => 'Sales Returns',
        'purchase_returns'  => 'Purchase Returns',

        // Enterprise features
        'sales_forecasting' => 'Sales Forecasting (Holt-Winters)',
        'api_access'        => 'RESTful API Access',
        'audit_compliance'  => 'Audit Trail & Compliance Logging',
        'custom_roles'      => 'Custom Roles & 79 Permissions',
        'dedicated_support' => 'Dedicated Support',
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature-to-Route Group Mapping
    |--------------------------------------------------------------------------
    |
    | Maps feature slugs to the route name prefixes they protect.
    | Used by the CheckPlanFeature middleware to determine which feature
    | is required for a given route.
    |
    */

    'feature_routes' => [
        'pos' => [
            'admin.pos',
            'admin.pos.*',
        ],
        'accounting' => [
            'admin.accounting.*',
            'admin.setting.accounting',
            'admin.setting.accounting.*',
            'admin.setting.reset-coa-default',
        ],
        'crm' => [
            'admin.sales_pipeline.*',
        ],
        'sales_pipeline' => [
            'admin.sales_pipeline.*',
        ],
        'financial_reports' => [
            'admin.reports.income-statement',
            'admin.reports.income-statement.*',
            'admin.reports.balance-sheet',
            'admin.reports.balance-sheet.*',
            'admin.reports.aged-receivables',
            'admin.reports.aged-receivables.*',
            'admin.reports.aged-payables',
            'admin.reports.aged-payables.*',
        ],
        'multi_currency' => [
            'admin.setting.currency.*',
        ],
        'sales_returns' => [
            'admin.sales-returns.*',
        ],
        'purchase_returns' => [
            'admin.por.*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Upgrade Prompt Messages
    |--------------------------------------------------------------------------
    |
    | Messages shown when a tenant tries to access a feature not included
    | in their current plan.
    |
    */

    'upgrade_messages' => [
        'pos'               => 'Point of Sale (POS) is available on the Professional plan and above.',
        'accounting'        => 'The full Accounting Suite is available on the Professional plan and above.',
        'crm'               => 'CRM & Sales Pipeline is available on the Professional plan and above.',
        'sales_pipeline'    => 'Sales Pipeline is available on the Professional plan and above.',
        'financial_reports' => 'Financial Reports are available on the Professional plan and above.',
        'multi_currency'    => 'Multi-Currency support is available on the Professional plan and above.',
        'sales_returns'     => 'Sales Returns management is available on the Professional plan and above.',
        'purchase_returns'  => 'Purchase Returns management is available on the Professional plan and above.',
        'sales_forecasting' => 'Sales Forecasting is available on the Enterprise plan.',
        'api_access'        => 'API Access is available on the Enterprise plan.',
        'audit_compliance'  => 'Audit Trail & Compliance Logging is available on the Enterprise plan.',
        'custom_roles'      => 'Custom Roles & Permissions management is available on the Enterprise plan.',
    ],

];
