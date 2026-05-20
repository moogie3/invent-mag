<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tutorial Sub-Topics Grid
    |--------------------------------------------------------------------------
    | Data for the "What to explore?" grid view.
    |
    */
    'topics' => [
        'configuration' => [
            'label' => 'Configuration',
            'subtitle' => 'System setup and preferences',
            'color' => 'primary',
            'icon' => 'ti-adjustments',
            'items' => [
                ['icon' => 'ti-settings', 'label' => 'System Settings', 'route' => 'admin.setting.index'],
                ['icon' => 'ti-ruler-2', 'label' => 'Units of Measure', 'route' => 'admin.setting.unit'],
                ['icon' => 'ti-tag', 'label' => 'Categories', 'route' => 'admin.setting.category'],
                ['icon' => 'ti-receipt-tax', 'label' => 'Taxes', 'route' => 'admin.setting.tax'],
                ['icon' => 'ti-currency-dollar', 'label' => 'Currencies', 'route' => 'admin.setting.currency.edit'],
                ['icon' => 'ti-building', 'label' => 'Company Profile', 'route' => 'admin.setting.profile.edit'],
                ['icon' => 'ti-calculator', 'label' => 'Accounting Setup', 'route' => 'admin.setting.accounting'],
            ]
        ],
        'masterdata' => [
            'label' => 'Master Data',
            'subtitle' => 'Core business entities',
            'color' => 'success',
            'icon' => 'ti-packages',
            'items' => [
                ['icon' => 'ti-packages', 'label' => 'Products', 'route' => 'admin.product'],
                ['icon' => 'ti-building-warehouse', 'label' => 'Warehouses', 'route' => 'admin.warehouse'],
                ['icon' => 'ti-users', 'label' => 'Customers', 'route' => 'admin.customer'],
                ['icon' => 'ti-truck', 'label' => 'Suppliers', 'route' => 'admin.supplier'],
                ['icon' => 'ti-id-badge', 'label' => 'Employees', 'route' => 'admin.hr.employee.index'],
                ['icon' => 'ti-calendar-check', 'label' => 'Attendance', 'route' => 'admin.hr.attendance.index'],
                ['icon' => 'ti-cash-banknote', 'label' => 'Payroll', 'route' => 'admin.hr.payroll.index'],
            ]
        ],
        'operations' => [
            'label' => 'Operations',
            'subtitle' => 'Daily business transactions',
            'color' => 'warning',
            'icon' => 'ti-shopping-cart',
            'items' => [
                ['icon' => 'ti-report-money', 'label' => 'Sales Orders', 'route' => 'admin.sales'],
                ['icon' => 'ti-truck-delivery', 'label' => 'Purchase Orders', 'route' => 'admin.po'],
                ['icon' => 'ti-cash', 'label' => 'Point of Sale (POS)', 'route' => 'admin.pos'],
                ['icon' => 'ti-receipt-refund', 'label' => 'Sales Returns', 'route' => 'admin.sales-returns.index'],
                ['icon' => 'ti-arrow-back-up', 'label' => 'Purchase Returns', 'route' => 'admin.por.index'],
                ['icon' => 'ti-chart-line', 'label' => 'Sales Pipeline', 'route' => 'admin.sales_pipeline.index'],
                ['icon' => 'ti-clipboard-check', 'label' => 'Stock Opname', 'route' => 'admin.stock-opname.index'],
            ]
        ],
        'accounting' => [
            'label' => 'Accounting',
            'subtitle' => 'Financial records and journals',
            'color' => 'info',
            'icon' => 'ti-calculator',
            'items' => [
                ['icon' => 'ti-building-bank', 'label' => 'Chart of Accounts', 'route' => 'admin.accounting.accounts.index'],
                ['icon' => 'ti-book', 'label' => 'General Ledger', 'route' => 'admin.accounting.ledger'],
                ['icon' => 'ti-notebook', 'label' => 'General Journal', 'route' => 'admin.accounting.journal'],
                ['icon' => 'ti-pencil', 'label' => 'Manual Journal Entries', 'route' => 'admin.accounting.journal-entries.index'],
                ['icon' => 'ti-scale', 'label' => 'Trial Balance', 'route' => 'admin.accounting.trial_balance'],
                ['icon' => 'ti-scale', 'label' => 'Opening Balance', 'route' => 'admin.accounting.opening-balance.index'],
            ]
        ],
        'hr' => [
            'label' => 'HR & Payroll',
            'subtitle' => 'Employee management and payroll',
            'color' => 'pink',
            'icon' => 'ti-users-group',
            'items' => [
                ['icon' => 'ti-id-badge', 'label' => 'Employees', 'route' => 'admin.hr.employee.index'],
                ['icon' => 'ti-calendar-check', 'label' => 'Attendance', 'route' => 'admin.hr.attendance.index'],
                ['icon' => 'ti-cash-banknote', 'label' => 'Payroll', 'route' => 'admin.hr.payroll.index'],
            ]
        ],
        'reports' => [
            'label' => 'Reports',
            'subtitle' => 'Financial and inventory reports',
            'color' => 'indigo',
            'icon' => 'ti-report-analytics',
            'items' => [
                ['icon' => 'ti-file-analytics', 'label' => 'Income Statement', 'route' => 'admin.reports.income-statement'],
                ['icon' => 'ti-file-text', 'label' => 'Balance Sheet', 'route' => 'admin.reports.balance-sheet'],
                ['icon' => 'ti-receipt-2', 'label' => 'Aged Receivables', 'route' => 'admin.reports.aged-receivables'],
                ['icon' => 'ti-receipt-tax', 'label' => 'Aged Payables', 'route' => 'admin.reports.aged-payables'],
                ['icon' => 'ti-history', 'label' => 'Recent Transactions', 'route' => 'admin.reports.recent-transactions'],
                ['icon' => 'ti-forklift', 'label' => 'Stock Transfer', 'route' => 'admin.reports.stock-transfer.page'],
                ['icon' => 'ti-file-text', 'label' => 'Adjustment Log', 'route' => 'admin.reports.adjustment-log'],
            ]
        ],
        'administration' => [
            'label' => 'Administration',
            'subtitle' => 'System monitoring and access control',
            'color' => 'purple',
            'icon' => 'ti-shield-lock',
            'items' => [
                ['icon' => 'ti-shield-lock', 'label' => 'Audit Log', 'route' => 'admin.audit-log.index'],
                ['icon' => 'ti-users', 'label' => 'User Management', 'route' => 'admin.users.index'],
                ['icon' => 'ti-database', 'label' => 'Backup', 'route' => 'admin.setting.backup.index'],
            ]
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Route-specific Tutorial Contexts
    |--------------------------------------------------------------------------
    | Uses wildcard matching (Str::is). The first matching route determines the context.
    | Order matters: place more specific rules BEFORE catch-alls.
    |
    */
    'contexts' => [
        'admin.dashboard' => [
            'title' => 'tutorial.dashboard',
            'icon'  => 'ti-dashboard',
            'desc'  => 'tutorial.dashboard_desc',
            'steps' => ['tutorial.key_metrics', 'tutorial.sales_purchase_chart', 'tutorial.sales_forecast', 'tutorial.accounting_summary', 'tutorial.recent_transactions', 'tutorial.quick_actions', 'tutorial.system_alerts'],
        ],
        'admin.product.create' => [
            'title' => 'tutorial.create_product',
            'icon'  => 'ti-plus',
            'desc'  => 'tutorial.product_desc',
            'steps' => ['tutorial.basic_info_create', 'tutorial.category_supplier_create', 'tutorial.unit_measure_create', 'tutorial.pricing_create', 'tutorial.stock_info_create', 'tutorial.has_expiry_create', 'tutorial.additional_details_create'],
        ],
        'admin.product.edit' => [
            'title' => 'tutorial.edit_product',
            'icon'  => 'ti-edit',
            'desc'  => 'tutorial.edit_product_desc',
            'steps' => ['tutorial.basic_info_edit', 'tutorial.category_supplier_edit', 'tutorial.unit_edit', 'tutorial.pricing_edit', 'tutorial.stock_warehouse_edit', 'tutorial.stock_transfer_edit'],
        ],
        'admin.product*' => [
            'title' => 'tutorial.products_master',
            'icon'  => 'ti-packages',
            'desc'  => 'tutorial.products_master_desc',
            'steps' => ['tutorial.create_product_btn', 'tutorial.warehouse_filter', 'tutorial.stock_status', 'tutorial.expiry_status', 'tutorial.bulk_actions', 'tutorial.table_actions', 'tutorial.multi_warehouse_flow'],
        ],
        'admin.reports.stock-transfer*' => [
            'title' => 'tutorial.stock_transfer',
            'icon'  => 'ti-forklift',
            'desc'  => 'tutorial.stock_transfer_desc',
            'steps' => ['tutorial.select_source', 'tutorial.select_destination', 'tutorial.select_product_transfer', 'tutorial.enter_quantity', 'tutorial.add_reason', 'tutorial.submit_transfer', 'tutorial.view_history'],
        ],
        'admin.reports.adjustment-log*' => [
            'title' => 'tutorial.adjustment_log',
            'icon'  => 'ti-file-text',
            'desc'  => 'tutorial.adjustment_log_desc',
            'steps' => ['tutorial.view_all_adjustments', 'tutorial.filter_by_type', 'tutorial.filter_by_product', 'tutorial.filter_by_user', 'tutorial.filter_by_date', 'tutorial.export', 'tutorial.quick_actions_adj'],
        ],
        'admin.stock-opname.create' => [
            'title' => 'tutorial.plan_stock_opname',
            'icon'  => 'ti-plus',
            'desc'  => 'tutorial.plan_stock_opname_desc',
            'steps' => ['tutorial.plan_stock_opname_step_1', 'tutorial.plan_stock_opname_step_2', 'tutorial.plan_stock_opname_step_3'],
        ],
        'admin.stock-opname.show' => [
            'title' => 'tutorial.execute_stock_opname',
            'icon'  => 'ti-check',
            'desc'  => 'tutorial.execute_stock_opname_desc',
            'steps' => ['tutorial.execute_stock_opname_step_1', 'tutorial.execute_stock_opname_step_2', 'tutorial.execute_stock_opname_step_3', 'tutorial.execute_stock_opname_step_4'],
        ],
        'admin.stock-opname*' => [
            'title' => 'messages.stock_opname',
            'icon'  => 'ti-clipboard-check',
            'desc'  => 'tutorial.stock_opname_desc',
            'steps' => ['tutorial.stock_opname_step_1', 'tutorial.stock_opname_step_2', 'tutorial.stock_opname_step_3', 'tutorial.stock_opname_step_4'],
        ],
        'admin.hr.employee*' => [
            'title' => 'tutorial.hr_employee',
            'icon'  => 'ti-id-badge',
            'desc'  => 'tutorial.hr_employee_desc',
            'steps' => ['tutorial.hr_employee_step_1', 'tutorial.hr_employee_step_2', 'tutorial.hr_employee_step_3', 'tutorial.hr_employee_step_4'],
        ],
        'admin.hr.attendance.show' => [
            'title' => 'tutorial.hr_attendance_details',
            'icon'  => 'ti-calendar-time',
            'desc'  => 'tutorial.hr_attendance_details_desc',
            'steps' => ['tutorial.hr_attendance_details_step_1', 'tutorial.hr_attendance_details_step_2', 'tutorial.hr_attendance_details_step_3', 'tutorial.hr_attendance_details_step_4'],
        ],
        'admin.hr.attendance*' => [
            'title' => 'tutorial.hr_attendance',
            'icon'  => 'ti-calendar-check',
            'desc'  => 'tutorial.hr_attendance_desc',
            'steps' => ['tutorial.hr_attendance_step_1', 'tutorial.hr_attendance_step_2', 'tutorial.hr_attendance_step_3'],
        ],
        'admin.hr.payroll.show' => [
            'title' => 'tutorial.hr_payroll_details',
            'icon'  => 'ti-receipt',
            'desc'  => 'tutorial.hr_payroll_details_desc',
            'steps' => ['tutorial.hr_payroll_details_step_1', 'tutorial.hr_payroll_details_step_2', 'tutorial.hr_payroll_details_step_3', 'tutorial.hr_payroll_details_step_4', 'tutorial.hr_payroll_details_step_5'],
        ],
        'admin.hr.payroll*' => [
            'title' => 'tutorial.hr_payroll',
            'icon'  => 'ti-cash-banknote',
            'desc'  => 'tutorial.hr_payroll_desc',
            'steps' => ['tutorial.hr_payroll_step_1', 'tutorial.hr_payroll_step_2'],
        ],
        'admin.warehouse*' => [
            'title' => 'tutorial.warehouse_management',
            'icon' => 'ti-building-warehouse',
            'desc'  => 'tutorial.warehouse_management_desc',
            'steps' => ['tutorial.create_warehouse', 'tutorial.main_warehouse', 'tutorial.warehouse_list', 'tutorial.view_details_warehouse', 'tutorial.export_warehouse', 'tutorial.table_actions_warehouse', 'tutorial.stock_tracking'],
        ],
        'admin.customer*' => [
            'title' => 'tutorial.customer_management',
            'icon'  => 'ti-users',
            'desc'  => 'tutorial.customer_management_desc',
            'steps' => ['tutorial.create_customer', 'tutorial.customer_crm', 'tutorial.customer_credits_view', 'tutorial.customer_list', 'tutorial.export', 'tutorial.table_actions_customer'],
        ],
        'admin.supplier*' => [
            'title' => 'tutorial.supplier_management',
            'icon'  => 'ti-truck',
            'desc'  => 'tutorial.supplier_management_desc',
            'steps' => ['tutorial.create_supplier', 'tutorial.supplier_srm', 'tutorial.supplier_list', 'tutorial.export', 'tutorial.table_actions_supplier'],
        ],
        'admin.sales.create' => [
            'title' => 'tutorial.create_sales_order',
            'icon'  => 'ti-plus',
            'desc'  => 'tutorial.create_sales_order_desc',
            'steps' => ['tutorial.sales_info', 'tutorial.customer_credit', 'tutorial.product_selection', 'tutorial.auto_pricing', 'tutorial.quantity_discount', 'tutorial.invoice_discount', 'tutorial.order_summary', 'tutorial.credit_applied', 'tutorial.submit_sales'],
        ],
        'admin.sales.edit' => [
            'title' => 'tutorial.edit_sales_order',
            'icon'  => 'ti-edit',
            'desc'  => 'tutorial.edit_sales_order_desc',
            'steps' => ['tutorial.sales_info_edit', 'tutorial.customer_info_edit', 'tutorial.customer_credit', 'tutorial.fully_paid_warning', 'tutorial.edit_items', 'tutorial.invoice_discount', 'tutorial.record_payment', 'tutorial.payment_info', 'tutorial.track_returns_sales', 'tutorial.save_sales'],
        ],
        'admin.sales.show' => [
            'title' => 'tutorial.view_sales_order',
            'icon'  => 'ti-eye',
            'desc'  => 'tutorial.view_sales_order_desc',
            'steps' => ['tutorial.order_information', 'tutorial.customer_credit_view', 'tutorial.items_table', 'tutorial.returned_items', 'tutorial.amount_display', 'tutorial.returned_adjusted', 'tutorial.create_return_link', 'tutorial.actions_sales'],
        ],
        'admin.po.create' => [
            'title' => 'tutorial.create_purchase_order',
            'icon'  => 'ti-plus',
            'desc'  => 'tutorial.create_purchase_order_desc',
            'steps' => ['tutorial.purchase_info', 'tutorial.product_selection_po', 'tutorial.supplier_price_po', 'tutorial.autofill_info', 'tutorial.quantity_po', 'tutorial.invoice_discount_po', 'tutorial.order_summary_po', 'tutorial.submit_po'],
        ],
        'admin.po.edit' => [
            'title' => 'tutorial.edit_purchase_order',
            'icon'  => 'ti-edit',
            'desc'  => 'tutorial.edit_purchase_order_desc',
            'steps' => ['tutorial.purchase_info_edit', 'tutorial.supplier_info', 'tutorial.fully_paid_warning_po', 'tutorial.edit_items_po', 'tutorial.invoice_discount_po_edit', 'tutorial.record_payment_po', 'tutorial.payment_info_po', 'tutorial.track_returns_po', 'tutorial.save_po'],
        ],
        'admin.po.show' => [
            'title' => 'tutorial.view_purchase_order',
            'icon'  => 'ti-eye',
            'desc'  => 'tutorial.view_purchase_order_desc',
            'steps' => ['tutorial.order_info_po_view', 'tutorial.supplier_info_view', 'tutorial.items_table_po_view', 'tutorial.returned_items_po_view', 'tutorial.amount_display_po_view', 'tutorial.returned_adjusted_po', 'tutorial.create_return_po', 'tutorial.actions_po_view'],
        ],
        'admin.po*' => [
            'title' => 'tutorial.purchase_orders',
            'icon'  => 'ti-truck-delivery',
            'desc'  => 'tutorial.purchase_orders_desc',
            'steps' => ['tutorial.create_purchase_order', 'tutorial.view_details_po', 'tutorial.payment_status_po', 'tutorial.returned_indicator_po', 'tutorial.purchase_returns_btn', 'tutorial.accounting_po', 'tutorial.bulk_actions_po', 'tutorial.supplier_association', 'tutorial.goods_receipt', 'tutorial.export_po'],
        ],
        'admin.sales-returns.create' => [
            'title' => 'tutorial.create_sales_return',
            'icon'  => 'ti-plus',
            'desc'  => 'tutorial.create_sales_return_desc',
            'steps' => ['tutorial.select_invoice', 'tutorial.return_date', 'tutorial.reason_ret', 'tutorial.select_items_ret', 'tutorial.return_quantity', 'tutorial.credit_note_condition', 'tutorial.status_ret', 'tutorial.save_return'],
        ],
        'admin.sales-returns.edit' => [
            'title' => 'tutorial.edit_sales_return',
            'icon'  => 'ti-edit',
            'desc'  => 'tutorial.edit_sales_return_desc',
            'steps' => ['tutorial.return_info', 'tutorial.return_date_edit', 'tutorial.reason_edit', 'tutorial.edit_items_ret', 'tutorial.return_quantity_edit', 'tutorial.status_edit', 'tutorial.credit_note_condition', 'tutorial.save_return_edit'],
        ],
        'admin.sales-returns.show' => [
            'title' => 'tutorial.sales_return_details',
            'icon' => 'ti-eye',
            'desc'  => 'tutorial.sales_return_details_desc',
            'steps' => ['tutorial.return_details', 'tutorial.items_ret', 'tutorial.status_ret_view', 'tutorial.credit_info', 'tutorial.print_ret'],
        ],
        'admin.sales-returns*' => [
            'title' => 'tutorial.sales_returns_list',
            'icon'  => 'ti-receipt-refund',
            'desc'  => 'tutorial.sales_returns_list_desc',
            'steps' => ['tutorial.view_returns', 'tutorial.create_return', 'tutorial.return_status', 'tutorial.credit_note', 'tutorial.completed_returns', 'tutorial.customer_credit_ret', 'tutorial.view_credit', 'tutorial.bulk_actions_returns', 'tutorial.export_returns'],
        ],
        'admin.por.create' => [
            'title' => 'tutorial.create_purchase_return',
            'icon'  => 'ti-plus',
            'desc'  => 'tutorial.create_purchase_return_desc',
            'steps' => ['tutorial.select_invoice_por', 'tutorial.return_date_por', 'tutorial.reason_por', 'tutorial.select_items_por', 'tutorial.return_quantity_por', 'tutorial.debit_note_condition', 'tutorial.status_por', 'tutorial.save_por'],
        ],
        'admin.por.edit' => [
            'title' => 'tutorial.edit_purchase_return',
            'icon'  => 'ti-edit',
            'desc'  => 'tutorial.edit_purchase_return_desc',
            'steps' => ['tutorial.return_info_por', 'tutorial.return_date_edit_por', 'tutorial.reason_edit_por', 'tutorial.edit_items_por', 'tutorial.return_quantity_edit_por', 'tutorial.status_edit_por', 'tutorial.debit_note_condition', 'tutorial.save_por_edit'],
        ],
        'admin.por.show' => [
            'title' => 'tutorial.purchase_return_details',
            'icon'  => 'ti-eye',
            'desc'  => 'tutorial.purchase_return_details_desc',
            'steps' => ['tutorial.return_details_por', 'tutorial.items_por', 'tutorial.status_por_view', 'tutorial.debit_info_por', 'tutorial.print_por'],
        ],
        'admin.por*' => [
            'title' => 'tutorial.purchase_returns_list',
            'icon'  => 'ti-receipt-refund',
            'desc'  => 'tutorial.purchase_returns_list_desc',
            'steps' => ['tutorial.view_por', 'tutorial.create_por', 'tutorial.por_status', 'tutorial.supplier_debit', 'tutorial.completed_por', 'tutorial.supplier_debit_info', 'tutorial.bulk_actions_por', 'tutorial.export_por'],
        ],
        'admin.sales_pipeline*' => [
            'title' => 'tutorial.sales_pipeline',
            'icon'  => 'ti-chart-line',
            'desc'  => 'tutorial.sales_pipeline_desc',
            'steps' => ['tutorial.kanban_intro', 'tutorial.step1_pipeline', 'tutorial.step2_pipeline', 'tutorial.step3_pipeline', 'tutorial.kanban_board', 'tutorial.edit_pipeline_info', 'tutorial.edit_delete_pipeline', 'tutorial.convert_sales'],
        ],
        'admin.sales*' => [
            'title' => 'tutorial.sales_orders',
            'icon'  => 'ti-shopping-cart',
            'desc'  => 'tutorial.sales_orders_desc',
            'steps' => ['tutorial.create_sales_order_btn', 'tutorial.view_details_sales', 'tutorial.payment_status', 'tutorial.returned_indicator', 'tutorial.sales_returns_btn', 'tutorial.accounting_sales', 'tutorial.bulk_actions_sales', 'tutorial.export_sales'],
        ],
        'admin.pos*' => [
            'title' => 'tutorial.pos',
            'icon'  => 'ti-cash',
            'desc'  => 'tutorial.pos_desc',
            'steps' => ['tutorial.product_catalog', 'tutorial.quick_create_product', 'tutorial.shopping_cart', 'tutorial.customer_selection', 'tutorial.sale_summary', 'tutorial.payment_methods', 'tutorial.complete_sale'],
        ],
        'admin.setting.unit*' => [
            'title' => 'tutorial.unit_management',
            'icon'  => 'ti-ruler-2',
            'desc'  => 'tutorial.unit_management_desc',
            'steps' => ['tutorial.create_units', 'tutorial.unit_symbols', 'tutorial.base_units'],
        ],
        'admin.setting.category*' => [
            'title' => 'tutorial.product_categories',
            'icon'  => 'ti-tags',
            'desc'  => 'tutorial.product_categories_desc',
            'steps' => ['tutorial.create_categories', 'tutorial.filtering_cat', 'tutorial.reporting_cat'],
        ],
        'admin.setting.tax*' => [
            'title' => 'tutorial.tax_configuration',
            'icon'  => 'ti-receipt-tax',
            'desc'  => 'tutorial.tax_configuration_desc',
            'steps' => ['tutorial.define_tax_rates', 'tutorial.sales_application'],
        ],
        'admin.setting.currency*' => [
            'title' => 'tutorial.currency_settings',
            'icon'  => 'ti-coin',
            'desc'  => 'tutorial.currency_settings_desc',
            'steps' => ['tutorial.primary_currency', 'tutorial.formatting_currency', 'tutorial.thousands_separator'],
        ],
        'admin.setting.profile*' => [
            'title' => 'tutorial.my_profile',
            'icon'  => 'ti-user-circle',
            'desc'  => 'tutorial.my_profile_desc',
            'steps' => ['tutorial.account_info', 'tutorial.password_management', 'tutorial.api_tokens'],
        ],
        'admin.setting.plan*' => [
            'title' => 'tutorial.subscription_plan',
            'icon'  => 'ti-crown',
            'desc'  => 'tutorial.subscription_plan_desc',
            'steps' => ['tutorial.current_plan', 'tutorial.usage_statistics', 'tutorial.plan_features'],
        ],
        'admin.setting.accounting*' => [
            'title' => 'tutorial.accounting_setup',
            'icon'  => 'ti-calculator',
            'desc'  => 'tutorial.accounting_setup_desc',
            'steps' => ['tutorial.default_accounts', 'tutorial.cash_receivables', 'tutorial.reset_option'],
        ],
        'admin.setting.backup*' => [
            'title' => 'tutorial.backup_page',
            'icon'  => 'ti-database',
            'desc'  => 'tutorial.backup_page_desc',
            'steps' => ['tutorial.backup_recommendation', 'tutorial.backup_create', 'tutorial.backup_files', 'tutorial.backup_restore', 'tutorial.backup_password_tab', 'tutorial.backup_password_first_time', 'tutorial.backup_password_enable', 'tutorial.backup_password_restore_needed', 'tutorial.backup_password_minimum'],
        ],
        'admin.setting.notifications*' => [
            'title' => 'tutorial.notifications_title',
            'icon'  => 'ti-bell',
            'desc'  => 'tutorial.notifications_desc',
            'steps' => ['tutorial.po_sales_notif', 'tutorial.low_stock_notif', 'tutorial.expiring_notif', 'tutorial.system_notif', 'tutorial.clear_all_notif', 'tutorial.clear_individual'],
        ],
        'admin.setting*' => [
            'title' => 'tutorial.system_settings',
            'icon'  => 'ti-settings',
            'desc'  => 'tutorial.system_settings_desc',
            'steps' => ['tutorial.interface_layout', 'tutorial.theme_config', 'tutorial.notifications', 'tutorial.session_security', 'tutorial.performance', 'tutorial.language_localization', 'tutorial.advanced_options'],
        ],
        'admin.accounting.ledger*' => [
            'title' => 'tutorial.general_ledger',
            'icon'  => 'ti-book',
            'desc'  => 'tutorial.general_ledger_desc',
            'steps' => ['tutorial.account_selection_ledger', 'tutorial.date_range_ledger', 'tutorial.filter_ledger', 'tutorial.opening_balance_ledger', 'tutorial.transactions_ledger', 'tutorial.view_source_ledger', 'tutorial.closing_balance_ledger', 'tutorial.export_ledger'],
        ],
        'admin.accounting.journal' => [
            'title' => 'tutorial.general_journal',
            'icon'  => 'ti-notebook',
            'desc'  => 'tutorial.general_journal_desc',
            'steps' => ['tutorial.date_filter_journal', 'tutorial.filter_journal', 'tutorial.entry_list_journal', 'tutorial.view_source_journal', 'tutorial.manual_entries_journal', 'tutorial.export_journal'],
        ],
        'admin.accounting.journal-entries.create' => [
            'title' => 'tutorial.create_journal_entry',
            'icon'  => 'ti-plus',
            'desc'  => 'tutorial.create_journal_entry_desc',
            'steps' => ['tutorial.date_je', 'tutorial.description_je', 'tutorial.notes_je', 'tutorial.add_transaction', 'tutorial.account_selection', 'tutorial.debit_credit', 'tutorial.balance_check', 'tutorial.save_je'],
        ],
        'admin.accounting.journal-entries.edit' => [
            'title' => 'tutorial.edit_journal_entry',
            'icon'  => 'ti-edit',
            'desc'  => 'tutorial.edit_journal_entry_desc',
            'steps' => ['tutorial.date_edit_je', 'tutorial.description_edit_je', 'tutorial.notes_edit_je', 'tutorial.edit_transactions', 'tutorial.add_remove_rows', 'tutorial.balance_check_edit', 'tutorial.save_edit_je'],
        ],
        'admin.accounting.journal-entries.show' => [
            'title' => 'tutorial.journal_entry_details',
            'icon'  => 'ti-eye',
            'desc'  => 'tutorial.journal_entry_details_desc',
            'steps' => ['tutorial.entry_overview', 'tutorial.transaction_lines', 'tutorial.balance_mje', 'tutorial.audit_log', 'tutorial.related_entries', 'tutorial.actions_mje', 'tutorial.post_entry', 'tutorial.reverse_entry', 'tutorial.void_entry'],
        ],
        'admin.accounting.journal-entries*' => [
            'title' => 'tutorial.manual_journal_entries',
            'icon'  => 'ti-notebook',
            'desc'  => 'tutorial.manual_journal_entries_desc',
            'steps' => ['tutorial.entry_list_mje', 'tutorial.status_mje', 'tutorial.reversed_entries', 'tutorial.create_entry_mje', 'tutorial.view_mje', 'tutorial.edit_mje', 'tutorial.delete_mje', 'tutorial.reverse_mje'],
        ],
        'admin.accounting.chart*' => [
            'title' => 'tutorial.chart_of_accounts',
            'icon'  => 'ti-list-details',
            'desc'  => 'tutorial.chart_of_accounts_desc',
            'steps' => ['tutorial.account_list_coa', 'tutorial.account_code', 'tutorial.account_name_coa', 'tutorial.account_type_coa', 'tutorial.description_coa', 'tutorial.system_accounts', 'tutorial.account_setup_coa'],
        ],
        'admin.accounting.accounts.create' => [
            'title' => 'tutorial.add_account_title',
            'icon'  => 'ti-plus',
            'desc'  => 'tutorial.add_account_desc',
            'steps' => ['tutorial.name_acc', 'tutorial.code_acc', 'tutorial.type_acc', 'tutorial.parent_account', 'tutorial.description_acc', 'tutorial.active_acc', 'tutorial.save_acc'],
        ],
        'admin.accounting.accounts.edit' => [
            'title' => 'tutorial.edit_account',
            'icon'  => 'ti-edit',
            'desc'  => 'tutorial.edit_account_desc',
            'steps' => ['tutorial.name_edit_acc', 'tutorial.code_edit_acc', 'tutorial.type_edit_acc', 'tutorial.parent_edit_acc', 'tutorial.description_edit_acc', 'tutorial.active_edit_acc', 'tutorial.save_edit_acc'],
        ],
        'admin.accounting.accounts*' => [
            'title' => 'tutorial.accounts_index',
            'icon'  => 'ti-building-bank',
            'desc'  => 'tutorial.accounts_index_desc',
            'steps' => ['tutorial.account_list_tree', 'tutorial.search_coa', 'tutorial.account_details', 'tutorial.active_status', 'tutorial.actions_coa', 'tutorial.add_account', 'tutorial.export_coa'],
        ],
        'admin.accounting.trial_balance*' => [
            'title' => 'tutorial.trial_balance',
            'icon'  => 'ti-scale',
            'desc'  => 'tutorial.trial_balance_desc',
            'steps' => ['tutorial.date_filter_tb', 'tutorial.filter_tb', 'tutorial.account_list_tb', 'tutorial.balance_display_tb', 'tutorial.debit_credit_tb', 'tutorial.totals_tb', 'tutorial.export_tb'],
        ],
        'admin.accounting.opening-balance*' => [
            'title' => 'tutorial.opening_balances',
            'icon'  => 'ti-scale',
            'desc'  => 'tutorial.opening_balances_desc',
            'steps' => ['tutorial.balance_date', 'tutorial.notes_ob', 'tutorial.account_list_ob', 'tutorial.set_balances', 'tutorial.balance_field', 'tutorial.balance_check_ob', 'tutorial.reset_all', 'tutorial.save_ob', 'tutorial.delete_ob', 'tutorial.summary_ob'],
        ],
        'admin.reports.income-statement*' => [
            'title' => 'tutorial.income_statement',
            'icon'  => 'ti-file-analytics',
            'desc'  => 'tutorial.income_statement_desc',
            'steps' => ['tutorial.date_range_is', 'tutorial.generate_report_is', 'tutorial.revenue_section', 'tutorial.expenses_section', 'tutorial.net_income', 'tutorial.export_is'],
        ],
        'admin.reports.balance-sheet*' => [
            'title' => 'tutorial.balance_sheet',
            'icon'  => 'ti-file-text',
            'desc'  => 'tutorial.balance_sheet_desc',
            'steps' => ['tutorial.as_of_date_bs', 'tutorial.generate_report_bs', 'tutorial.assets_section', 'tutorial.liabilities_section', 'tutorial.equity_section', 'tutorial.accounting_equation', 'tutorial.export_bs'],
        ],
        'admin.reports.aged-receivables*' => [
            'title' => 'tutorial.ar_aging',
            'icon'  => 'ti-receipt-2',
            'desc'  => 'tutorial.ar_aging_desc',
            'steps' => ['tutorial.aging_period_ar', 'tutorial.customer_list_ar', 'tutorial.days_overdue_ar', 'tutorial.totals_ar', 'tutorial.total_outstanding_ar', 'tutorial.collection_priority', 'tutorial.export_ar'],
        ],
        'admin.reports.aged-payables*' => [
            'title' => 'tutorial.ap_aging',
            'icon'  => 'ti-receipt-tax',
            'desc'  => 'tutorial.ap_aging_desc',
            'steps' => ['tutorial.aging_period_ap', 'tutorial.supplier_list_ap', 'tutorial.days_overdue_ap', 'tutorial.totals_ap', 'tutorial.total_outstanding_ap', 'tutorial.payment_priority', 'tutorial.export_ap'],
        ],
        'admin.reports.recent-transactions*' => [
            'title' => 'tutorial.transaction_list',
            'icon'  => 'ti-history',
            'desc'  => 'tutorial.transaction_list_desc',
            'steps' => ['tutorial.date_range_tl', 'tutorial.transaction_type_tl', 'tutorial.payment_status_tl', 'tutorial.apply_filters', 'tutorial.summary_cards', 'tutorial.transactions_table', 'tutorial.bulk_actions_tl', 'tutorial.export_tl'],
        ],
        'admin.audit-log*' => [
            'title' => 'tutorial.activity_log',
            'icon'  => 'ti-shield-lock',
            'desc'  => 'tutorial.activity_log_desc',
            'steps' => ['tutorial.activity_overview', 'tutorial.search_filter_al', 'tutorial.activity_timeline', 'tutorial.filter_action_type', 'tutorial.filter_by_user', 'tutorial.security_compliance'],
        ],
        'admin.users*' => [
            'title' => 'tutorial.user_management',
            'icon'  => 'ti-users',
            'desc'  => 'tutorial.user_management_desc',
            'steps' => ['tutorial.user_statistics', 'tutorial.create_new_user', 'tutorial.user_table', 'tutorial.edit_user', 'tutorial.delete_user'],
        ],
        'admin.notifications' => [
            'title' => 'tutorial.notifications_title',
            'icon'  => 'ti-bell',
            'desc'  => 'tutorial.notifications_desc',
            'steps' => ['tutorial.po_sales_notif', 'tutorial.low_stock_notif', 'tutorial.expiring_notif', 'tutorial.system_notif', 'tutorial.clear_all_notif', 'tutorial.clear_individual'],
        ],
        'admin.setting.notifications*' => [
            'title' => 'tutorial.notification_settings_title',
            'icon'  => 'ti-bell-cog',
            'desc'  => 'tutorial.notification_settings_desc',
            'steps' => ['tutorial.notif_setting_sound', 'tutorial.notif_setting_browser_push', 'tutorial.notif_setting_success_toggle', 'tutorial.notif_setting_duration'],
        ],
        // Default catch-all
        '*' => [
            'title' => 'tutorial.help_tutorial',
            'icon'  => 'ti-help',
            'desc'  => 'tutorial.need_help_navigating',
            'steps' => ['tutorial.general_navigation', 'tutorial.general_settings', 'tutorial.general_creating', 'tutorial.search_filter_al', 'tutorial.actions_coa'],
        ],
    ]
];
