<?php

return [
    'menu' => [
        [
            'title' => 'messages.dashboard',
            'route' => 'admin.dashboard',
            'icon' => 'ti ti-home',
            'permission' => 'access-dashboard'
        ],
        [
            'title' => 'Purchasing',
            'icon' => 'ti ti-shopping-cart',
            'permission' => 'view-po',
            'key' => 'purchasing',
            'children' => [
                [
                    'title' => 'messages.purchase_order',
                    'route' => 'admin.po',
                    'permission' => 'view-po',
                    'icon' => 'ti ti-shopping-cart'
                ],
                [
                    'title' => 'messages.purchase_returns',
                    'route' => 'admin.por.index',
                    'permission' => 'view-purchase-returns',
                    'icon' => 'ti ti-receipt-refund'
                ]
            ]
        ],
        [
            'title' => 'Sales',
            'icon' => 'ti ti-report-money',
            'permission' => 'view-sales',
            'key' => 'sales',
            'children' => [
                [
                    'title' => 'messages.pos',
                    'route' => 'admin.pos',
                    'permission' => 'view-pos',
                    'icon' => 'ti ti-http-post'
                ],
                [
                    'title' => 'messages.sales',
                    'route' => 'admin.sales',
                    'permission' => 'view-sales',
                    'icon' => 'ti ti-report-money'
                ],
                [
                    'title' => 'messages.sales_pipeline',
                    'route' => 'admin.sales_pipeline.index',
                    'permission' => 'view-sales-pipeline',
                    'icon' => 'ti ti-chart-arrows-vertical'
                ],
                [
                    'title' => 'messages.sales_returns',
                    'route' => 'admin.sales-returns.index',
                    'permission' => 'view-sales-returns',
                    'icon' => 'ti ti-arrow-back'
                ],
                [
                    'title' => 'messages.quotations',
                    'route' => 'admin.quotations',
                    'permission' => 'view-quotations',
                    'icon' => 'ti ti-file-description'
                ]
            ]
        ],
        [
            'title' => 'messages.product',
            'route' => 'admin.product',
            'icon' => 'ti ti-package',
            'permission' => 'view-products'
        ],
        [
            'title' => 'messages.customer',
            'route' => 'admin.customer',
            'icon' => 'ti ti-users',
            'permission' => 'view-customer'
        ],
        [
            'title' => 'messages.supplier',
            'route' => 'admin.supplier',
            'icon' => 'ti ti-truck',
            'permission' => 'view-supplier'
        ],
        [
            'title' => 'messages.warehouse',
            'route' => 'admin.warehouse',
            'icon' => 'ti ti-building-warehouse',
            'permission' => 'view-warehouse'
        ],
        [
            'title' => 'Stock Opname',
            'route' => 'admin.stock-opname.index',
            'icon' => 'ti ti-clipboard-check',
            'permission' => 'view-stock-opname'
        ],
        [
            'title' => 'messages.accounting',
            'icon' => 'ti ti-calculator',
            'permission' => 'view-accounting',
            'key' => 'accounting',
            'children' => [
                [
                    'title' => 'messages.financial_health',
                    'route' => 'admin.accounting.health',
                    'permission' => 'view-accounting',
                    'icon' => 'ti ti-heartbeat'
                ],
                [
                    'title' => 'messages.ledger',
                    'route' => 'admin.accounting.ledger',
                    'permission' => 'view-accounting',
                    'icon' => 'ti ti-book'
                ],
                [
                    'title' => 'messages.journal',
                    'route' => 'admin.accounting.journal',
                    'permission' => 'view-accounting',
                    'icon' => 'ti ti-notebook'
                ],
                [
                    'title' => 'messages.manual_journal_entries',
                    'route' => 'admin.accounting.journal-entries.index',
                    'permission' => 'view-manual-journal',
                    'icon' => 'ti ti-notebook'
                ],
                [
                    'title' => 'messages.coa',
                    'route' => 'admin.accounting.accounts.index',
                    'permission' => 'view-accounting',
                    'icon' => 'ti ti-list-details'
                ],
                [
                    'title' => 'messages.trialbalance',
                    'route' => 'admin.accounting.trial_balance',
                    'permission' => 'view-accounting',
                    'icon' => 'ti ti-scale'
                ],
                [
                    'title' => 'messages.opening_balance',
                    'route' => 'admin.accounting.opening-balance.index',
                    'permission' => 'manage-opening-balance',
                    'icon' => 'ti ti-list-check'
                ]
            ]
        ],
        [
            'title' => 'messages.efaktur',
            'icon' => 'ti ti-file-invoice',
            'route' => 'admin.efaktur.index',
            'permission' => 'view-efaktur',
            'jurisdiction' => 'ID',   // Only visible for Indonesian tenants
        ],
        [
            'title' => 'messages.reports',
            'icon' => 'ti ti-report',
            'permission' => 'view-reports',
            'key' => 'reports',
            'children' => [
                [
                    'title' => 'messages.income_statement',
                    'route' => 'admin.reports.income-statement',
                    'permission' => 'view-reports',
                    'icon' => 'ti ti-file-analytics'
                ],
                [
                    'title' => 'messages.balance_sheet',
                    'route' => 'admin.reports.balance-sheet',
                    'permission' => 'view-reports',
                    'icon' => 'ti ti-file-text'
                ],
                [
                    'title' => 'messages.cash_flow_statement',
                    'route' => 'admin.reports.cash-flow',
                    'permission' => 'view-reports',
                    'icon' => 'ti ti-currency-dollar'
                ],
                [
                    'title' => 'messages.bank_reconciliation',
                    'route' => 'admin.bank-reconciliation.index',
                    'permission' => 'view-accounting',
                    'icon' => 'ti ti-building-bank'
                ],
                [
                    'title' => 'messages.aged_receivables_report',
                    'route' => 'admin.reports.aged-receivables',
                    'permission' => 'view-reports',
                    'icon' => 'ti ti-receipt-2'
                ],
                [
                    'title' => 'messages.aged_payables_report',
                    'route' => 'admin.reports.aged-payables',
                    'permission' => 'view-reports',
                    'icon' => 'ti ti-receipt-tax'
                ],
                [
                    'title' => 'messages.adjustment_log',
                    'route' => 'admin.reports.adjustment-log',
                    'permission' => 'view-reports',
                    'icon' => 'ti ti-clipboard-list'
                ],
                [
                    'title' => 'messages.stock_transfer',
                    'route' => 'admin.reports.stock-transfer.page',
                    'permission' => 'adjust-stock',
                    'icon' => 'ti ti-forklift'
                ],
                [
                    'title' => 'messages.recent_transactions',
                    'route' => 'admin.reports.recent-transactions',
                    'permission' => 'view-reports',
                    'icon' => 'ti ti-history'
                ]
            ]
        ],
        [
            'title' => 'messages.administration',
            'icon' => 'ti ti-shield-cog',
            'permission' => 'view-hr',
            'key' => 'administration',
            'children' => [
                [
                    'title' => 'messages.hr_employees',
                    'route' => 'admin.hr.employee.index',
                    'permission' => 'view-hr',
                    'icon' => 'ti ti-id-badge'
                ],
                [
                    'title' => 'messages.hr_attendance',
                    'route' => 'admin.hr.attendance.index',
                    'permission' => 'view-hr',
                    'icon' => 'ti ti-calendar-check'
                ],
                [
                    'title' => 'messages.hr_payroll',
                    'route' => 'admin.hr.payroll.index',
                    'permission' => 'view-hr',
                    'icon' => 'ti ti-wallet'
                ],
                [
                    'title' => 'messages.user_management',
                    'route' => 'admin.users.index',
                    'permission' => 'view-users',
                    'icon' => 'ti ti-users'
                ],
                [
                    'title' => 'messages.audit_log',
                    'route' => 'admin.audit-log.index',
                    'permission' => 'view-audit-log',
                    'icon' => 'ti ti-shield-check'
                ]
            ]
        ]
    ]
];
