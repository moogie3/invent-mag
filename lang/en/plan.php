<?php

return [
    // System Notification - Accounting Setup
    'notif_accounting_setup_title' => 'Accounting Setup Incomplete',
    'notif_accounting_setup_desc' => ':count default account(s) are not configured. Set them up to enable proper bookkeeping.',
    'notif_configure' => 'Configure',

    // System Notification - Welcome / Profile
    'notif_welcome_title' => 'Complete Your Profile',
    'notif_welcome_desc' => 'Your profile is missing: :fields. Complete your setup for a better experience.',
    'notif_complete_profile' => 'Complete Profile',
    'notif_field_shopname' => 'shop name',
    'notif_field_address' => 'address',
    'notif_field_avatar' => 'profile photo',

    // Notification Dropdown UI
    'notif_header' => 'Notifications',
    'notif_tab_orders' => 'PO & Sales',
    'notif_tab_inventory' => 'Inventory',
    'notif_tab_system' => 'System',
    'notif_purchase_orders' => 'Purchase Orders',
    'notif_sales' => 'Sales',
    'notif_low_stock' => 'Low Stock Alerts',
    'notif_expiring' => 'Expiring Products',
    'notif_system_updates' => 'System Updates',
    'notif_no_purchase' => 'No purchase notifications',
    'notif_no_sales' => 'No sales notifications',
    'notif_no_low_stock' => 'No low stock alerts',
    'notif_no_expiring' => 'No expiring products',
    'notif_no_system' => 'All systems are running smoothly',
    'notif_more_purchase' => '+ :count more purchase notifications',
    'notif_more_sales' => '+ :count more sales notifications',
    'notif_more_low_stock' => '+ :count more low stock alerts',
    'notif_more_expiring' => '+ :count more expiring product alerts',
    'notif_view_all' => 'View all notifications',

    // Product Expiry Notifications
    'notif_product_expiry_title' => 'Products Nearing Expiry',
    'notif_product_expiry_desc' => ':count item(s) in your inventory will expire within 30 days. Review and action before losses occur.',
    'notif_product_expiry_critical_desc' => ':critical item(s) expire within 7 days (:total total expiring within 30 days). Immediate action recommended.',
    'notif_view_products' => 'View Products',

    // Backup Status Notifications
    'notif_no_backup_title' => 'No Backup Found',
    'notif_no_backup_desc' => 'You have not created any backups yet. Run a backup to protect your data.',
    'notif_backup_recent_title' => 'Backup Completed Successfully',
    'notif_backup_recent_desc' => 'Your last backup was created :time. Your data is protected.',
    'notif_backup_overdue_title' => 'Backup Overdue',
    'notif_backup_overdue_desc' => 'Your last backup was :days day(s) ago. We recommend backing up your data regularly.',
    'notif_go_to_backup' => 'Go to Backup',
];
