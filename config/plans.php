<?php

return [

    'default_plan' => 'starter',

    'legacy_full_access' => true,

    'features' => [
        'products' => 'Products',
        'inventory' => 'Inventory Management',
        'sales' => 'Sales Orders',
        'purchases' => 'Purchase Orders',
        'invoices' => 'Invoice Generation',
        'dashboard' => 'Dashboard',
        'basic_reports' => 'Basic Reports',
        'customers' => 'Customer Management',
        'suppliers' => 'Supplier Management',

        'pos' => 'Point of Sale (POS)',
        'stock_opname' => 'Stock Opname (Physical Count)',
        'accounting' => 'Full Accounting Suite',
        'crm' => 'CRM & Sales Pipeline',
        'sales_pipeline' => 'Sales Pipeline (Kanban)',
        'financial_reports' => 'Financial Reports & Multi-Currency',
        'multi_currency' => 'Multi-Currency Support',
        'sales_returns' => 'Sales Returns',
        'purchase_returns' => 'Purchase Returns',
        'hr' => 'HR Module (Employees, Attendance & Payroll)',
        'cash_flow' => 'Cash Flow Statement',
        'quotations' => 'Quotations',
        'ppn' => 'e-Faktur PPN (Indonesian Tax Invoice)',

        'sales_forecasting' => 'Sales Forecasting (Holt-Winters)',
        'api_access' => 'RESTful API Access',
        'audit_compliance' => 'Audit Trail & Compliance Logging',
        'bank_reconciliation' => 'Bank Reconciliation',
    ],

    'feature_routes' => [
        'stock_opname' => [
            'admin.stock-opname.*',
        ],
        'pos' => [
            'admin.pos',
            'admin.pos.*',
        ],
        'accounting' => [
            'admin.accounting.*',
            'admin.setting.accounting',
            'admin.setting.accounting.*',
            'admin.setting.reset-coa-default',
            'admin.accounting.opening-balance.*',
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
        'cash_flow' => [
            'admin.reports.cash-flow',
            'admin.reports.cash-flow.*',
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
        'api_access' => [
            'admin.setting.profile.token',
        ],
        'hr' => [
            'admin.hr.*',
        ],
        'quotations' => [
            'admin.quotations.*',
        ],
        'ppn' => [
            'admin.efaktur.*',
        ],
        'bank_reconciliation' => [
            'admin.bank-reconciliation.*',
        ],
    ],

    'upgrade_messages' => [
        'stock_opname' => 'Stock Opname is available on the Professional plan and above.',
        'pos' => 'Point of Sale (POS) is available on the Professional plan and above.',
        'accounting' => 'The full Accounting Suite is available on the Professional plan and above.',
        'crm' => 'CRM & Sales Pipeline is available on the Professional plan and above.',
        'sales_pipeline' => 'Sales Pipeline is available on the Professional plan and above.',
        'financial_reports' => 'Financial Reports are available on the Professional plan and above.',
        'multi_currency' => 'Multi-Currency support is available on the Professional plan and above.',
        'sales_returns' => 'Sales Returns management is available on the Professional plan and above.',
        'purchase_returns' => 'Purchase Returns management is available on the Professional plan and above.',
        'cash_flow' => 'Cash Flow Statement is available on the Professional plan and above.',
        'sales_forecasting' => 'Sales Forecasting is available on the Enterprise plan.',
        'api_access' => 'API Access is available on the Enterprise plan.',
        'audit_compliance' => 'Audit Trail & Compliance Logging is available on the Enterprise plan.',
        'hr' => 'The HR Module (Employees, Attendance & Payroll) is available on the Professional plan and above.',
        'quotations' => 'Quotations are available on the Professional plan and above.',
        'ppn' => 'e-Faktur PPN compliance is available on the Professional plan and above.',
        'bank_reconciliation' => 'Bank Reconciliation is available on the Professional plan and above.',
    ],

];
