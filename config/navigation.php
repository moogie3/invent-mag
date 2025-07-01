<?php

return [
    'menu' => [
        [
            'title' => 'Dashboard',
            'route' => 'admin.dashboard',
            'icon' => 'ti ti-home',
            'permission' => 'access-dashboard',
        ],
        [
            'title' => 'POS',
            'route' => 'admin.pos',
            'icon' => 'ti ti-cash',
            'permission' => 'view-pos',
        ],
        [
            'title' => 'Sales',
            'route' => 'admin.sales',
            'icon' => 'ti ti-report-money',
            'permission' => 'view-sales',
        ],
        [
            'title' => 'Purchase Order',
            'route' => 'admin.po',
            'icon' => 'ti ti-shopping-cart',
            'permission' => 'view-po',
        ],
        [
            'title' => 'Product',
            'route' => 'admin.product',
            'icon' => 'ti ti-package',
            'permission' => 'view-products',
        ],
        [
            'title' => 'Customer',
            'route' => 'admin.customer',
            'icon' => 'ti ti-users',
            'permission' => 'view-customer',
        ],
        [
            'title' => 'Supplier',
            'route' => 'admin.supplier',
            'icon' => 'ti ti-truck',
            'permission' => 'view-supplier',
        ],
        [
            'title' => 'Warehouse',
            'route' => 'admin.warehouse',
            'icon' => 'ti ti-building-warehouse',
            'permission' => 'view-warehouse',
        ],
    ],
];
