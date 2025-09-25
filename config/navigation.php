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
            'title' => 'messages.purchase_order',
            'route' => 'admin.po',
            'icon' => 'ti ti-shopping-cart',
            'permission' => 'view-po',
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
            'title' => 'messages.reports',
            'icon' => 'ti ti-report',
            'permission' => 'view-reports',
            'children' => [
                [
                    'title' => 'messages.adjustment_log',
                    'route' => 'admin.reports.adjustment-log',
                    'permission' => 'view-reports',
                    'icon' => 'ti ti-clipboard-list',
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