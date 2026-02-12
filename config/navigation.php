<?php

return [
    'menu' => [
        [
            'title' => 'messages.dashboard',
            'route' => 'admin.dashboard',
            'icon' => 'ti ti-home',
            'permission' => 'access-dashboard',
        ],
        [
            'title' => 'messages.pos',
            'route' => 'admin.pos',
            'icon' => 'ti ti-http-post',
            'permission' => 'view-pos',
        ],
        [
            'title' => 'messages.sales',
            'route' => 'admin.sales',
            'icon' => 'ti ti-report-money',
            'permission' => 'view-sales',
        ],
        [
            'title' => 'messages.sales_returns',
            'route' => 'admin.sales-returns.index',
            'icon' => 'ti ti-arrow-back',
            'permission' => 'view-sales-returns',
        ],
        [
            'title' => 'messages.purchase_order',
            'route' => 'admin.po',
            'icon' => 'ti ti-shopping-cart',
            'permission' => 'view-po',
        ],
        [
            'title' => 'messages.purchase_returns',
            'route' => 'admin.por.index',
            'icon' => 'ti ti-receipt-refund',
            'permission' => 'view-purchase-returns',
        ],
        [
            'title' => 'messages.sales_pipeline',
            'route' => 'admin.sales_pipeline.index',
            'icon' => 'ti ti-chart-arrows-vertical',
            'permission' => 'view-sales-pipeline',
        ],
        [
            'title' => 'messages.product',
            'route' => 'admin.product',
            'icon' => 'ti ti-package',
            'permission' => 'view-products',
        ],
        [
            'title' => 'messages.customer',
            'route' => 'admin.customer',
            'icon' => 'ti ti-users',
            'permission' => 'view-customer',
        ],
        [
            'title' => 'messages.supplier',
            'route' => 'admin.supplier',
            'icon' => 'ti ti-truck',
            'permission' => 'view-supplier',
        ],
        [
            'title' => 'messages.warehouse',
            'route' => 'admin.warehouse',
            'icon' => 'ti ti-building-warehouse',
            'permission' => 'view-warehouse',
        ],
        [
            'title' => 'messages.accounting',
            'icon' => 'ti ti-calculator',
            'permission' => 'view-accounting',
            'key' => 'accounting',
            'children' => [
                [
                    'title' => 'messages.ledger',
                    'route' => 'admin.accounting.ledger',
                    'permission' => 'view-accounting',
                    'icon' => 'ti ti-book',
                ],
                [
                    'title' => 'messages.journal',
                    'route' => 'admin.accounting.journal',
                    'permission' => 'view-accounting',
                    'icon' => 'ti ti-notebook',
                ],
                [
                    'title' => 'messages.manual_journal_entries',
                    'route' => 'admin.accounting.journal-entries.index',
                    'permission' => 'view-manual-journal',
                    'icon' => 'ti ti-notebook',
                ],
                [
                    'title' => 'messages.coa',
                    'route' => 'admin.accounting.chart',
                    'permission' => 'view-accounting',
                    'icon' => 'ti ti-list-details',
                ],
                [
                    'title' => 'messages.trialbalance',
                    'route' => 'admin.accounting.trial_balance',
                    'permission' => 'view-accounting',
                    'icon' => 'ti ti-scale',
                ],
            ],
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
                    'icon' => 'ti ti-file-analytics',
                ],
                [
                    'title' => 'messages.balance_sheet',
                    'route' => 'admin.reports.balance-sheet',
                    'permission' => 'view-reports',
                    'icon' => 'ti ti-file-text',
                ],
                [
                    'title' => 'messages.aged_receivables_report',
                    'route' => 'admin.reports.aged-receivables',
                    'permission' => 'view-reports',
                    'icon' => 'ti ti-receipt-2',
                ],
                [
                    'title' => 'messages.aged_payables_report',
                    'route' => 'admin.reports.aged-payables',
                    'permission' => 'view-reports',
                    'icon' => 'ti ti-receipt-tax',
                ],
                [
                    'title' => 'messages.adjustment_log',
                    'route' => 'admin.reports.adjustment-log',
                    'permission' => 'view-reports',
                    'icon' => 'ti ti-clipboard-list',
                ],
                [
                    'title' => 'messages.stock_transfer',
                    'route' => 'admin.reports.stock-transfer.page',
                    'permission' => 'adjust-stock',
                    'icon' => 'ti ti-forklift',
                ],
                [
                    'title' => 'messages.recent_transactions',
                    'route' => 'admin.reports.recent-transactions',
                    'permission' => 'view-reports',
                    'icon' => 'ti ti-history',
                ],
            ],
        ],
    ],
];
