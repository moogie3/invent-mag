@php
    $routeName = Route::currentRouteName();
    $contextTitle = __('tutorial.help_tutorial');
    $contextDesc = __('tutorial.need_help_navigating');
    $contextIcon = 'ti-help';
    $contextSteps = [];

    // Contextual logic
    if ($routeName === 'admin.dashboard') {
        $contextTitle = __('tutorial.dashboard');
        $contextIcon = 'ti-dashboard';
        $contextDesc = __('tutorial.dashboard_desc');
        $contextSteps = [
            __('tutorial.key_metrics'),
            __('tutorial.sales_purchase_chart'),
            __('tutorial.sales_forecast'),
            __('tutorial.accounting_summary'),
            __('tutorial.recent_transactions'),
            __('tutorial.quick_actions'),
            __('tutorial.system_alerts'),
        ];
    } elseif ($routeName === 'admin.product.create') {
        $contextTitle = __('tutorial.create_product');
        $contextIcon = 'ti-plus';
        $contextDesc = __('tutorial.product_desc');
        $contextSteps = [
            __('tutorial.basic_info_create'),
            __('tutorial.category_supplier_create'),
            __('tutorial.unit_measure_create'),
            __('tutorial.pricing_create'),
            __('tutorial.stock_info_create'),
            __('tutorial.has_expiry_create'),
            __('tutorial.additional_details_create')
        ];
    } elseif ($routeName === 'admin.product.edit') {
        $contextTitle = __('tutorial.edit_product');
        $contextIcon = 'ti-edit';
        $contextDesc = __('tutorial.edit_product_desc');
        $contextSteps = [
            __('tutorial.basic_info_edit'),
            __('tutorial.category_supplier_edit'),
            __('tutorial.unit_edit'),
            __('tutorial.pricing_edit'),
            __('tutorial.stock_warehouse_edit'),
            __('tutorial.stock_transfer_edit')
        ];
    } elseif (Str::startsWith($routeName, 'admin.product')) {
        $contextTitle = __('tutorial.products_master');
        $contextIcon = 'ti-packages';
        $contextDesc = __('tutorial.products_master_desc');
        $contextSteps = [
            __('tutorial.create_product_btn'),
            __('tutorial.warehouse_filter'),
            __('tutorial.stock_status'),
            __('tutorial.expiry_status'),
            __('tutorial.bulk_actions'),
            __('tutorial.table_actions'),
            __('tutorial.multi_warehouse_flow')
        ];
    } elseif ($routeName === 'admin.reports.stock-transfer.page') {
        $contextTitle = __('tutorial.stock_transfer');
        $contextIcon = 'ti-forklift';
        $contextDesc = __('tutorial.stock_transfer_desc');
        $contextSteps = [
            __('tutorial.select_source'),
            __('tutorial.select_destination'),
            __('tutorial.select_product_transfer'),
            __('tutorial.enter_quantity'),
            __('tutorial.add_reason'),
            __('tutorial.submit_transfer'),
            __('tutorial.view_history')
        ];
    } elseif ($routeName === 'admin.reports.adjustment-log') {
        $contextTitle = __('tutorial.adjustment_log');
        $contextIcon = 'ti-file-text';
        $contextDesc = __('tutorial.adjustment_log_desc');
        $contextSteps = [
            __('tutorial.view_all_adjustments'),
            __('tutorial.filter_by_type'),
            __('tutorial.filter_by_product'),
            __('tutorial.filter_by_user'),
            __('tutorial.filter_by_date'),
            __('tutorial.export'),
            __('tutorial.quick_actions_adj')
        ];
    } elseif (Str::startsWith($routeName, 'admin.warehouse')) {
        $contextTitle = __('tutorial.warehouse_management');
        $contextIcon = 'ti-building-warehouse';
        $contextDesc = __('tutorial.warehouse_management_desc');
        $contextSteps = [
            __('tutorial.create_warehouse'),
            __('tutorial.main_warehouse'),
            __('tutorial.warehouse_list'),
            __('tutorial.view_details_warehouse'),
            __('tutorial.export_warehouse'),
            __('tutorial.table_actions_warehouse'),
            __('tutorial.stock_tracking')
        ];
    } elseif (Str::startsWith($routeName, 'admin.customer')) {
        $contextTitle = __('tutorial.customer_management');
        $contextIcon = 'ti-users';
        $contextDesc = __('tutorial.customer_management_desc');
        $contextSteps = [
            __('tutorial.create_customer'),
            __('tutorial.customer_crm'),
            __('tutorial.customer_list'),
            __('tutorial.export'),
            __('tutorial.table_actions_customer')
        ];
    } elseif (Str::startsWith($routeName, 'admin.supplier')) {
        $contextTitle = __('tutorial.supplier_management');
        $contextIcon = 'ti-truck';
        $contextDesc = __('tutorial.supplier_management_desc');
        $contextSteps = [
            __('tutorial.create_supplier'),
            __('tutorial.supplier_srm'),
            __('tutorial.supplier_list'),
            __('tutorial.export'),
            __('tutorial.table_actions_supplier')
        ];
    } elseif ($routeName === 'admin.sales.create') {
        $contextTitle = __('tutorial.create_sales_order');
        $contextIcon = 'ti-plus';
        $contextDesc = __('tutorial.create_sales_order_desc');
        $contextSteps = [
            __('tutorial.sales_info'),
            __('tutorial.customer_credit'),
            __('tutorial.product_selection'),
            __('tutorial.auto_pricing'),
            __('tutorial.quantity_discount'),
            __('tutorial.invoice_discount'),
            __('tutorial.order_summary'),
            __('tutorial.credit_applied'),
            __('tutorial.submit_sales')
        ];
    } elseif ($routeName === 'admin.sales.edit') {
        $contextTitle = __('tutorial.edit_sales_order');
        $contextIcon = 'ti-edit';
        $contextDesc = __('tutorial.edit_sales_order_desc');
        $contextSteps = [
            __('tutorial.sales_info_edit'),
            __('tutorial.customer_info_edit'),
            __('tutorial.customer_credit'),
            __('tutorial.fully_paid_warning'),
            __('tutorial.edit_items'),
            __('tutorial.invoice_discount'),
            __('tutorial.record_payment'),
            __('tutorial.payment_info'),
            __('tutorial.track_returns_sales'),
            __('tutorial.save_sales')
        ];
    } elseif ($routeName === 'admin.po.edit') {
        $contextTitle = __('tutorial.edit_purchase_order');
        $contextIcon = 'ti-edit';
        $contextDesc = __('tutorial.edit_purchase_order_desc');
        $contextSteps = [
            __('tutorial.purchase_info_edit'),
            __('tutorial.supplier_info'),
            __('tutorial.fully_paid_warning_po'),
            __('tutorial.edit_items_po'),
            __('tutorial.invoice_discount_po_edit'),
            __('tutorial.record_payment_po'),
            __('tutorial.payment_info_po'),
            __('tutorial.track_returns_po'),
            __('tutorial.save_po')
        ];
    } elseif ($routeName === 'admin.sales.view') {
        $contextTitle = __('tutorial.view_sales_order');
        $contextIcon = 'ti-eye';
        $contextDesc = __('tutorial.view_sales_order_desc');
        $contextSteps = [
            __('tutorial.order_information'),
            __('tutorial.customer_credit_view'),
            __('tutorial.items_table'),
            __('tutorial.returned_items'),
            __('tutorial.amount_display'),
            __('tutorial.returned_adjusted'),
            __('tutorial.create_return_link'),
            __('tutorial.actions_sales')
        ];
    } elseif ($routeName === 'admin.po.view') {
        $contextTitle = __('tutorial.view_purchase_order');
        $contextIcon = 'ti-eye';
        $contextDesc = __('tutorial.view_purchase_order_desc');
        $contextSteps = [
            __('tutorial.order_info_po_view'),
            __('tutorial.supplier_info_view'),
            __('tutorial.items_table_po_view'),
            __('tutorial.returned_items_po_view'),
            __('tutorial.amount_display_po_view'),
            __('tutorial.returned_adjusted_po'),
            __('tutorial.create_return_po'),
            __('tutorial.actions_po_view')
        ];
    } elseif ($routeName === 'admin.sales-returns.index') {
        $contextTitle = __('tutorial.sales_returns_list');
        $contextIcon = 'ti-receipt-refund';
        $contextDesc = __('tutorial.sales_returns_list_desc');
        $contextSteps = [
            __('tutorial.view_returns'),
            __('tutorial.create_return'),
            __('tutorial.return_status'),
            __('tutorial.credit_note'),
            __('tutorial.completed_returns'),
            __('tutorial.customer_credit_ret'),
            __('tutorial.view_credit'),
            __('tutorial.bulk_actions_returns'),
            __('tutorial.export_returns')
        ];
    } elseif ($routeName === 'admin.sales-returns.create') {
        $contextTitle = __('tutorial.create_sales_return');
        $contextIcon = 'ti-plus';
        $contextDesc = __('tutorial.create_sales_return_desc');
        $contextSteps = [
            __('tutorial.select_invoice'),
            __('tutorial.return_date'),
            __('tutorial.reason_ret'),
            __('tutorial.select_items_ret'),
            __('tutorial.return_quantity'),
            __('tutorial.credit_note_condition'),
            __('tutorial.status_ret'),
            __('tutorial.save_return')
        ];
    } elseif ($routeName === 'admin.sales-returns.edit') {
        $contextTitle = __('tutorial.edit_sales_return');
        $contextIcon = 'ti-edit';
        $contextDesc = __('tutorial.edit_sales_return_desc');
        $contextSteps = [
            __('tutorial.return_info'),
            __('tutorial.return_date_edit'),
            __('tutorial.reason_edit'),
            __('tutorial.edit_items_ret'),
            __('tutorial.return_quantity_edit'),
            __('tutorial.status_edit'),
            __('tutorial.credit_note_condition'),
            __('tutorial.save_return_edit')
        ];
    } elseif ($routeName === 'admin.sales-returns.show') {
        $contextTitle = __('tutorial.sales_return_details');
        $contextIcon = 'ti-eye';
        $contextDesc = __('tutorial.sales_return_details_desc');
        $contextSteps = [
            __('tutorial.return_details'),
            __('tutorial.items_ret'),
            __('tutorial.status_ret_view'),
            __('tutorial.credit_info'),
            __('tutorial.print_ret')
        ];
    } elseif (Str::startsWith($routeName, 'admin.sales') && !Str::startsWith($routeName, 'admin.sales_pipeline')) {
        $contextTitle = __('tutorial.sales_orders');
        $contextIcon = 'ti-shopping-cart';
        $contextDesc = __('tutorial.sales_orders_desc');
        $contextSteps = [
            __('tutorial.create_sales_order_btn'),
            __('tutorial.view_details_sales'),
            __('tutorial.payment_status'),
            __('tutorial.returned_indicator'),
            __('tutorial.sales_returns_btn'),
            __('tutorial.accounting_sales'),
            __('tutorial.bulk_actions_sales'),
            __('tutorial.export_sales')
        ];
    } elseif (Str::startsWith($routeName, 'admin.sales_pipeline')) {
        $contextTitle = __('tutorial.sales_pipeline');
        $contextIcon = 'ti-chart-line';
        $contextDesc = __('tutorial.sales_pipeline_desc');
        $contextSteps = [
            __('tutorial.kanban_intro'),
            __('tutorial.step1_pipeline'),
            __('tutorial.step2_pipeline'),
            __('tutorial.step3_pipeline'),
            __('tutorial.kanban_board'),
            __('tutorial.edit_pipeline_info'),
            __('tutorial.edit_delete_pipeline'),
            __('tutorial.convert_sales')
        ];
    } elseif (Str::startsWith($routeName, 'admin.sales')) {
        $contextTitle = __('tutorial.sales_operations');
        $contextIcon = 'ti-shopping-cart';
        $contextDesc = __('tutorial.sales_operations_desc');
        $contextSteps = [
            __('tutorial.sales_orders_op'),
            __('tutorial.customer_credit_op'),
            __('tutorial.sales_pipeline_op'),
            __('tutorial.payments_op'),
            __('tutorial.sales_returns_op'),
            __('tutorial.customer_credits_op'),
            __('tutorial.invoices_op')
        ];
} elseif (Str::startsWith($routeName, 'admin.pos')) {
        $contextTitle = __('tutorial.pos');
        $contextIcon = 'ti-cash';
        $contextDesc = __('tutorial.pos_desc');
        $contextSteps = [
            __('tutorial.product_catalog'),
            __('tutorial.quick_create_product'),
            __('tutorial.shopping_cart'),
            __('tutorial.customer_selection'),
            __('tutorial.sale_summary'),
            __('tutorial.payment_methods'),
            __('tutorial.complete_sale')
        ];
    } elseif ($routeName === 'admin.po.create') {
        $contextTitle = __('tutorial.create_purchase_order');
        $contextIcon = 'ti-plus';
        $contextDesc = __('tutorial.create_purchase_order_desc');
        $contextSteps = [
            __('tutorial.purchase_info'),
            __('tutorial.product_selection_po'),
            __('tutorial.supplier_price_po'),
            __('tutorial.autofill_info'),
            __('tutorial.quantity_po'),
            __('tutorial.invoice_discount_po'),
            __('tutorial.order_summary_po'),
            __('tutorial.submit_po')
        ];
    } elseif (Str::startsWith($routeName, 'admin.po')) {
        $contextTitle = __('tutorial.purchase_orders');
        $contextIcon = 'ti-truck-delivery';
        $contextDesc = __('tutorial.purchase_orders_desc');
        $contextSteps = [
            __('tutorial.create_purchase_order'),
            __('tutorial.view_details_po'),
            __('tutorial.payment_status_po'),
            __('tutorial.returned_indicator_po'),
            __('tutorial.purchase_returns_btn'),
            __('tutorial.accounting_po'),
            __('tutorial.bulk_actions_po'),
            __('tutorial.supplier_association'),
            __('tutorial.goods_receipt'),
            __('tutorial.export_po')
        ];
    } elseif (Str::startsWith($routeName, 'admin.setting.index')) {
        $contextTitle = __('tutorial.system_settings');
        $contextIcon = 'ti-settings';
        $contextDesc = __('tutorial.system_settings_desc');
        $contextSteps = [
            __('tutorial.interface_layout'),
            __('tutorial.theme_config'),
            __('tutorial.notifications'),
            __('tutorial.session_security'),
            __('tutorial.performance'),
            __('tutorial.language_localization'),
            __('tutorial.advanced_options')
        ];
    } elseif (Str::startsWith($routeName, 'admin.setting.unit')) {
        $contextTitle = __('tutorial.unit_management');
        $contextIcon = 'ti-ruler-2';
        $contextDesc = __('tutorial.unit_management_desc');
        $contextSteps = [
            __('tutorial.create_units'),
            __('tutorial.unit_symbols'),
            __('tutorial.base_units')
        ];
    } elseif (Str::startsWith($routeName, 'admin.setting.category')) {
        $contextTitle = __('tutorial.product_categories');
        $contextIcon = 'ti-tags';
        $contextDesc = __('tutorial.product_categories_desc');
        $contextSteps = [
            __('tutorial.create_categories'),
            __('tutorial.filtering_cat'),
            __('tutorial.reporting_cat')
        ];
    } elseif (Str::startsWith($routeName, 'admin.setting.tax')) {
        $contextTitle = __('tutorial.tax_configuration');
        $contextIcon = 'ti-receipt-tax';
        $contextDesc = __('tutorial.tax_configuration_desc');
        $contextSteps = [
            __('tutorial.define_tax_rates'),
            __('tutorial.sales_application')
        ];
    } elseif (Str::startsWith($routeName, 'admin.setting.currency')) {
        $contextTitle = __('tutorial.currency_settings');
        $contextIcon = 'ti-coin';
        $contextDesc = __('tutorial.currency_settings_desc');
        $contextSteps = [
            __('tutorial.primary_currency'),
            __('tutorial.formatting_currency'),
            __('tutorial.thousands_separator')
        ];
    } elseif (Str::startsWith($routeName, 'admin.setting.profile')) {
        $contextTitle = __('tutorial.my_profile');
        $contextIcon = 'ti-user-circle';
        $contextDesc = __('tutorial.my_profile_desc');
        $contextSteps = [
            __('tutorial.account_info'),
            __('tutorial.password_management'),
            __('tutorial.api_tokens')
        ];
    } elseif (Str::startsWith($routeName, 'admin.setting.plan')) {
        $contextTitle = __('tutorial.subscription_plan');
        $contextIcon = 'ti-crown';
        $contextDesc = __('tutorial.subscription_plan_desc');
        $contextSteps = [
            __('tutorial.current_plan'),
            __('tutorial.usage_statistics'),
            __('tutorial.plan_features')
        ];
    } elseif (Str::startsWith($routeName, 'admin.setting.accounting')) {
        $contextTitle = __('tutorial.accounting_setup');
        $contextIcon = 'ti-calculator';
        $contextDesc = __('tutorial.accounting_setup_desc');
        $contextSteps = [
            __('tutorial.default_accounts'),
            __('tutorial.cash_receivables'),
            __('tutorial.reset_option')
        ];
    } elseif ($routeName === 'admin.accounting.ledger') {
        $contextTitle = __('tutorial.general_ledger');
        $contextIcon = 'ti-book';
        $contextDesc = __('tutorial.general_ledger_desc');
        $contextSteps = [
            __('tutorial.account_selection_ledger'),
            __('tutorial.date_range_ledger'),
            __('tutorial.filter_ledger'),
            __('tutorial.opening_balance_ledger'),
            __('tutorial.transactions_ledger'),
            __('tutorial.view_source_ledger'),
            __('tutorial.closing_balance_ledger'),
            __('tutorial.export_ledger')
        ];
    } elseif ($routeName === 'admin.accounting.journal') {
        $contextTitle = __('tutorial.general_journal');
        $contextIcon = 'ti-notebook';
        $contextDesc = __('tutorial.general_journal_desc');
        $contextSteps = [
            __('tutorial.date_filter_journal'),
            __('tutorial.filter_journal'),
            __('tutorial.entry_list_journal'),
            __('tutorial.view_source_journal'),
            __('tutorial.manual_entries_journal'),
            __('tutorial.export_journal')
        ];
    } elseif ($routeName === 'admin.accounting.journal-entries.index') {
        $contextTitle = __('tutorial.manual_journal_entries');
        $contextIcon = 'ti-notebook';
        $contextDesc = __('tutorial.manual_journal_entries_desc');
        $contextSteps = [
            __('tutorial.entry_list_mje'),
            __('tutorial.status_mje'),
            __('tutorial.reversed_entries'),
            __('tutorial.create_entry_mje'),
            __('tutorial.view_mje'),
            __('tutorial.edit_mje'),
            __('tutorial.delete_mje'),
            __('tutorial.reverse_mje')
        ];
    } elseif ($routeName === 'admin.accounting.journal-entries.create') {
        $contextTitle = __('tutorial.create_journal_entry');
        $contextIcon = 'ti-plus';
        $contextDesc = __('tutorial.create_journal_entry_desc');
        $contextSteps = [
            __('tutorial.date_je'),
            __('tutorial.description_je'),
            __('tutorial.notes_je'),
            __('tutorial.add_transaction'),
            __('tutorial.account_selection'),
            __('tutorial.debit_credit'),
            __('tutorial.balance_check'),
            __('tutorial.save_je')
        ];
    } elseif ($routeName === 'admin.accounting.journal-entries.edit') {
        $contextTitle = __('tutorial.edit_journal_entry');
        $contextIcon = 'ti-edit';
        $contextDesc = __('tutorial.edit_journal_entry_desc');
        $contextSteps = [
            __('tutorial.date_edit_je'),
            __('tutorial.description_edit_je'),
            __('tutorial.notes_edit_je'),
            __('tutorial.edit_transactions'),
            __('tutorial.add_remove_rows'),
            __('tutorial.balance_check_edit'),
            __('tutorial.save_edit_je')
        ];
    } elseif ($routeName === 'admin.accounting.journal-entries.show') {
        $contextTitle = __('tutorial.journal_entry_details');
        $contextIcon = 'ti-eye';
        $contextDesc = __('tutorial.journal_entry_details_desc');
        $contextSteps = [
            __('tutorial.entry_overview'),
            __('tutorial.transaction_lines'),
            __('tutorial.balance_mje'),
            __('tutorial.audit_log'),
            __('tutorial.related_entries'),
            __('tutorial.actions_mje'),
            __('tutorial.post_entry'),
            __('tutorial.reverse_entry'),
            __('tutorial.void_entry')
        ];
    } elseif ($routeName === 'admin.accounting.chart') {
        $contextTitle = __('tutorial.chart_of_accounts');
        $contextIcon = 'ti-list-details';
        $contextDesc = __('tutorial.chart_of_accounts_desc');
        $contextSteps = [
            __('tutorial.account_list_coa'),
            __('tutorial.account_code'),
            __('tutorial.account_name_coa'),
            __('tutorial.account_type_coa'),
            __('tutorial.description_coa'),
            __('tutorial.system_accounts'),
            __('tutorial.account_setup_coa')
        ];
    } elseif ($routeName === 'admin.accounting.accounts.index') {
        $contextTitle = __('tutorial.accounts_index');
        $contextIcon = 'ti-building-bank';
        $contextDesc = __('tutorial.accounts_index_desc');
        $contextSteps = [
            __('tutorial.account_list_tree'),
            __('tutorial.search_coa'),
            __('tutorial.account_details'),
            __('tutorial.active_status'),
            __('tutorial.actions_coa'),
            __('tutorial.add_account'),
            __('tutorial.export_coa')
        ];
    } elseif ($routeName === 'admin.accounting.accounts.create') {
        $contextTitle = __('tutorial.add_account');
        $contextIcon = 'ti-plus';
        $contextDesc = __('tutorial.add_account_desc');
        $contextSteps = [
            __('tutorial.name_acc'),
            __('tutorial.code_acc'),
            __('tutorial.type_acc'),
            __('tutorial.parent_account'),
            __('tutorial.description_acc'),
            __('tutorial.active_acc'),
            __('tutorial.save_acc')
        ];
    } elseif ($routeName === 'admin.accounting.accounts.edit') {
        $contextTitle = __('tutorial.edit_account');
        $contextIcon = 'ti-edit';
        $contextDesc = __('tutorial.edit_account_desc');
        $contextSteps = [
            __('tutorial.name_edit_acc'),
            __('tutorial.code_edit_acc'),
            __('tutorial.type_edit_acc'),
            __('tutorial.parent_edit_acc'),
            __('tutorial.description_edit_acc'),
            __('tutorial.active_edit_acc'),
            __('tutorial.save_edit_acc')
        ];
    } elseif ($routeName === 'admin.accounting.trial_balance') {
        $contextTitle = __('tutorial.trial_balance');
        $contextIcon = 'ti-scale';
        $contextDesc = __('tutorial.trial_balance_desc');
        $contextSteps = [
            __('tutorial.date_filter_tb'),
            __('tutorial.filter_tb'),
            __('tutorial.account_list_tb'),
            __('tutorial.balance_display_tb'),
            __('tutorial.debit_credit_tb'),
            __('tutorial.totals_tb'),
            __('tutorial.export_tb')
        ];
    } elseif ($routeName === 'admin.accounting.opening-balance.index') {
        $contextTitle = __('tutorial.opening_balances');
        $contextIcon = 'ti-scale';
        $contextDesc = __('tutorial.opening_balances_desc');
        $contextSteps = [
            __('tutorial.balance_date'),
            __('tutorial.notes_ob'),
            __('tutorial.account_list_ob'),
            __('tutorial.set_balances'),
            __('tutorial.balance_field'),
            __('tutorial.balance_check_ob'),
            __('tutorial.reset_all'),
            __('tutorial.save_ob'),
            __('tutorial.delete_ob'),
            __('tutorial.summary_ob')
        ];
    } elseif ($routeName === 'admin.reports.income-statement') {
        $contextTitle = __('tutorial.income_statement');
        $contextIcon = 'ti-file-analytics';
        $contextDesc = __('tutorial.income_statement_desc');
        $contextSteps = [
            __('tutorial.date_range_is'),
            __('tutorial.generate_report_is'),
            __('tutorial.revenue_section'),
            __('tutorial.expenses_section'),
            __('tutorial.net_income'),
            __('tutorial.export_is')
        ];
    } elseif ($routeName === 'admin.reports.balance-sheet') {
        $contextTitle = __('tutorial.balance_sheet');
        $contextIcon = 'ti-file-text';
        $contextDesc = __('tutorial.balance_sheet_desc');
        $contextSteps = [
            __('tutorial.as_of_date_bs'),
            __('tutorial.generate_report_bs'),
            __('tutorial.assets_section'),
            __('tutorial.liabilities_section'),
            __('tutorial.equity_section'),
            __('tutorial.accounting_equation'),
            __('tutorial.export_bs')
        ];
    } elseif ($routeName === 'admin.reports.aged-receivables') {
        $contextTitle = __('tutorial.ar_aging');
        $contextIcon = 'ti-receipt-2';
        $contextDesc = __('tutorial.ar_aging_desc');
        $contextSteps = [
            __('tutorial.aging_period_ar'),
            __('tutorial.customer_list_ar'),
            __('tutorial.days_overdue_ar'),
            __('tutorial.totals_ar'),
            __('tutorial.total_outstanding_ar'),
            __('tutorial.collection_priority'),
            __('tutorial.export_ar')
        ];
    } elseif ($routeName === 'admin.reports.aged-payables') {
        $contextTitle = __('tutorial.ap_aging');
        $contextIcon = 'ti-receipt-tax';
        $contextDesc = __('tutorial.ap_aging_desc');
        $contextSteps = [
            __('tutorial.aging_period_ap'),
            __('tutorial.supplier_list_ap'),
            __('tutorial.days_overdue_ap'),
            __('tutorial.totals_ap'),
            __('tutorial.total_outstanding_ap'),
            __('tutorial.payment_priority'),
            __('tutorial.export_ap')
        ];
    } elseif ($routeName === 'admin.reports.recent-transactions') {
        $contextTitle = __('tutorial.transaction_list');
        $contextIcon = 'ti-history';
        $contextDesc = __('tutorial.transaction_list_desc');
        $contextSteps = [
            __('tutorial.date_range_tl'),
            __('tutorial.transaction_type_tl'),
            __('tutorial.payment_status_tl'),
            __('tutorial.apply_filters'),
            __('tutorial.summary_cards'),
            __('tutorial.transactions_table'),
            __('tutorial.bulk_actions_tl'),
            __('tutorial.export_tl')
        ];
    } elseif (Str::startsWith($routeName, 'admin.audit-log')) {
        $contextTitle = __('tutorial.activity_log');
        $contextIcon = 'ti-shield-lock';
        $contextDesc = __('tutorial.activity_log_desc');
        $contextSteps = [
            __('tutorial.activity_overview'),
            __('tutorial.search_filter_al'),
            __('tutorial.activity_timeline'),
            __('tutorial.filter_action_type'),
            __('tutorial.filter_by_user'),
            __('tutorial.security_compliance')
        ];
    } elseif (Str::startsWith($routeName, 'admin.users')) {
        $contextTitle = __('tutorial.user_management');
        $contextIcon = 'ti-users';
        $contextDesc = __('tutorial.user_management_desc');
        $contextSteps = [
            __('tutorial.user_statistics'),
            __('tutorial.create_new_user'),
            __('tutorial.user_table'),
            __('tutorial.edit_user'),
            __('tutorial.delete_user')
        ];
    } elseif ($routeName === 'admin.notifications' || $routeName === 'admin.setting.notifications') {
        $contextTitle = __('tutorial.notifications');
        $contextIcon = 'ti-bell';
        $contextDesc = __('tutorial.notifications_desc');
        $contextSteps = [
            __('tutorial.po_sales_notif'),
            __('tutorial.low_stock_notif'),
            __('tutorial.expiring_notif'),
            __('tutorial.system_notif'),
            __('tutorial.clear_all_notif'),
            __('tutorial.clear_individual')
        ];
    } else {
        // Generic Help
        $contextSteps = [
            __('tutorial.general_navigation'),
            __('tutorial.general_settings'),
            __('tutorial.general_creating'),
            __('tutorial.search_filter_al'),
            __('tutorial.actions_coa')
        ];
    }
@endphp

<div class="modal modal-blur fade" id="tutorial-modal" tabindex="-1" role="dialog" aria-hidden="true" data-completed="{{ auth()->check() && auth()->user()->tutorial_completed ? 'true' : 'false' }}" data-route="{{ Route::currentRouteName() }}">
    <div class="modal-dialog modal-dialog-centered modal-lg tutorial-modal-dialog" role="document">

{{-- Sub-topic route map: generated by Blade so URLs are always correct.
     Exposed as window.tutorialSubTopics for tutorial-modal.js --}}
<script>
window.tutorialSubTopics = {
    configuration: {
        label: 'Configuration',
        subtitle: 'System setup and preferences',
        color: 'primary',
        icon: 'ti-adjustments',
        items: [
            { icon: 'ti-settings', label: 'System Settings', url: '{{ route('admin.setting.index') }}' },
            { icon: 'ti-ruler-2', label: 'Units of Measure', url: '{{ route('admin.setting.unit') }}' },
            { icon: 'ti-tag', label: 'Categories', url: '{{ route('admin.setting.category') }}' },
            { icon: 'ti-receipt-tax', label: 'Taxes', url: '{{ route('admin.setting.tax') }}' },
            { icon: 'ti-currency-dollar', label: 'Currencies', url: '{{ route('admin.setting.currency.edit') }}' },
            { icon: 'ti-building', label: 'Company Profile', url: '{{ route('admin.setting.profile.edit') }}' },
            { icon: 'ti-calculator', label: 'Accounting Setup', url: '{{ route('admin.setting.accounting') }}' },
        ]
    },
    masterdata: {
        label: 'Master Data',
        subtitle: 'Core business entities',
        color: 'success',
        icon: 'ti-packages',
        items: [
            { icon: 'ti-packages', label: 'Products', url: '{{ route('admin.product') }}' },
            { icon: 'ti-building-warehouse', label: 'Warehouses', url: '{{ route('admin.warehouse') }}' },
            { icon: 'ti-users', label: 'Customers', url: '{{ route('admin.customer') }}' },
            { icon: 'ti-truck', label: 'Suppliers', url: '{{ route('admin.supplier') }}' },
        ]
    },
    operations: {
        label: 'Operations',
        subtitle: 'Daily business transactions',
        color: 'warning',
        icon: 'ti-shopping-cart',
        items: [
            { icon: 'ti-report-money', label: 'Sales Orders', url: '{{ route('admin.sales') }}' },
            { icon: 'ti-truck-delivery', label: 'Purchase Orders', url: '{{ route('admin.po') }}' },
            { icon: 'ti-cash', label: 'Point of Sale (POS)', url: '{{ route('admin.pos') }}' },
            { icon: 'ti-receipt-refund', label: 'Sales Returns', url: '{{ route('admin.sales-returns.index') }}' },
            { icon: 'ti-arrow-back-up', label: 'Purchase Returns', url: '{{ route('admin.por.index') }}' },
            { icon: 'ti-chart-line', label: 'Sales Pipeline', url: '{{ route('admin.sales_pipeline.index') }}' },
        ]
    },
    accounting: {
        label: 'Accounting',
        subtitle: 'Financial records and journals',
        color: 'info',
        icon: 'ti-calculator',
        items: [
            { icon: 'ti-building-bank', label: 'Chart of Accounts', url: '{{ route('admin.accounting.accounts.index') }}' },
            { icon: 'ti-book', label: 'General Ledger', url: '{{ route('admin.accounting.ledger') }}' },
            { icon: 'ti-notebook', label: 'General Journal', url: '{{ route('admin.accounting.journal') }}' },
            { icon: 'ti-pencil', label: 'Manual Journal Entries', url: '{{ route('admin.accounting.journal-entries.index') }}' },
            { icon: 'ti-scale', label: 'Trial Balance', url: '{{ route('admin.accounting.trial_balance') }}' },
            { icon: 'ti-scale', label: 'Opening Balance', url: '{{ route('admin.accounting.opening-balance.index') }}' },
        ]
    },
    reports: {
        label: 'Reports',
        subtitle: 'Financial and inventory reports',
        color: 'indigo',
        icon: 'ti-report-analytics',
        items: [
            { icon: 'ti-file-analytics', label: 'Income Statement', url: '{{ route('admin.reports.income-statement') }}' },
            { icon: 'ti-file-text', label: 'Balance Sheet', url: '{{ route('admin.reports.balance-sheet') }}' },
            { icon: 'ti-receipt-2', label: 'Aged Receivables', url: '{{ route('admin.reports.aged-receivables') }}' },
            { icon: 'ti-receipt-tax', label: 'Aged Payables', url: '{{ route('admin.reports.aged-payables') }}' },
            { icon: 'ti-history', label: 'Recent Transactions', url: '{{ route('admin.reports.recent-transactions') }}' },
            { icon: 'ti-forklift', label: 'Stock Transfer', url: '{{ route('admin.reports.stock-transfer.page') }}' },
            { icon: 'ti-file-text', label: 'Adjustment Log', url: '{{ route('admin.reports.adjustment-log') }}' },
        ]
    },
    administration: {
        label: 'Administration',
        subtitle: 'System monitoring and access control',
        color: 'purple',
        icon: 'ti-shield-lock',
        items: [
            { icon: 'ti-shield-lock', label: 'Audit Log', url: '{{ route('admin.audit-log.index') }}' },
            { icon: 'ti-users', label: 'User Management', url: '{{ route('admin.users.index') }}' },
        ]
    },
};
</script>
        <div class="modal-content shadow-lg border-0" style="border-radius: 20px; overflow: hidden;">

            <!-- Header with Gradient -->
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #1e40af, #3b82f6); border-bottom: none; padding: 20px 24px;">
                <h5 class="modal-title fw-bold fs-3" id="tutorial-modal-title">
                    <i class="ti ti-rocket me-2"></i> {{ __('Welcome to Invent-MAG') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="closeTutorialExplicitly()"></button>
            </div>

            <!-- STEP 1: Workflow Overview (For First Time Login) -->
            <div class="modal-body py-5 px-4 px-md-5 tutorial-step" id="tutorial-step-1">
                <div class="text-center mb-5">
                    <h2 class="fw-bold text-dark mb-3">{{ __('Your Complete Inventory & Accounting Solution') }}</h2>
                    <p class="text-secondary fs-4">
                        {{ __('To unlock the full potential of the app, follow this recommended setup flow:') }}
                    </p>
                </div>

                <div class="row">
                    <div class="col-md-10 offset-md-1">

                        <div class="tutorial-list-item">
                            <div class="tutorial-icon-box bg-primary-lt text-primary me-4 shadow-sm">
                                <i class="ti ti-settings"></i>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold fs-4">1. Configuration</h3>
                                <p class="text-secondary mb-0">Set up your base parameters: <strong>Units, Categories, Currencies, and Taxes</strong>.</p>
                            </div>
                        </div>

                        <div class="tutorial-list-item">
                            <div class="tutorial-icon-box bg-success-lt text-success me-4 shadow-sm">
                                <i class="ti ti-database"></i>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold fs-4">2. Master Data</h3>
                                <p class="text-secondary mb-0">Input your core entities: <strong>Warehouses, Suppliers, Customers, and Products</strong>.</p>
                            </div>
                        </div>

                        <div class="tutorial-list-item">
                            <div class="tutorial-icon-box bg-warning-lt text-warning me-4 shadow-sm">
                                <i class="ti ti-shopping-cart"></i>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold fs-4">3. Operations</h3>
                                <p class="text-secondary mb-0">Manage daily tasks: <strong>Purchase Orders, Sales, Returns, POS, and Pipeline</strong>.</p>
                            </div>
                        </div>

                        <div class="tutorial-list-item">
                            <div class="tutorial-icon-box bg-info-lt text-info me-4 shadow-sm">
                                <i class="ti ti-chart-bar"></i>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold fs-4">4. Accounting & Reports</h3>
                                <p class="text-secondary mb-0">Track your finances: <strong>Journals, Chart of Accounts, Income Statements</strong>.</p>
                            </div>
                        </div>

                        <div class="tutorial-list-item">
                            <div class="tutorial-icon-box bg-purple-lt text-purple me-4 shadow-sm">
                                <i class="ti ti-shield-lock"></i>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold fs-4">5. Administration & Security</h3>
                                <p class="text-secondary mb-0">Configure system preferences on the <strong>Settings page</strong>, and monitor system changes via the <strong>Audit & Activity Logs</strong>.</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- STEP 2: Topics Grid -->
            <div class="modal-body py-5 px-4 px-md-5 d-none tutorial-step" id="tutorial-step-topics">
                <div class="text-center mb-5">
                    <h2 class="fw-bold text-dark mb-2">{{ __('What would you like to explore?') }}</h2>
                    <p class="text-secondary">{{ __('Select a category to see available pages.') }}</p>
                </div>

                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card topic-card h-100 shadow-sm bg-primary-lt cursor-pointer" onclick="showSubTopics('configuration')">
                            <div class="card-body text-center p-4">
                                <div class="icon-wrapper bg-primary text-white mx-auto shadow">
                                    <i class="ti ti-adjustments"></i>
                                </div>
                                <h4 class="fw-bold text-dark">Configuration</h4>
                                <p class="text-secondary small mb-0">Units, Categories, Taxes, Currencies, Company Profile</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card topic-card h-100 shadow-sm bg-success-lt cursor-pointer" onclick="showSubTopics('masterdata')">
                            <div class="card-body text-center p-4">
                                <div class="icon-wrapper bg-success text-white mx-auto shadow">
                                    <i class="ti ti-packages"></i>
                                </div>
                                <h4 class="fw-bold text-dark">Master Data</h4>
                                <p class="text-secondary small mb-0">Products, Warehouses, Suppliers, Customers</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card topic-card h-100 shadow-sm bg-warning-lt cursor-pointer" onclick="showSubTopics('operations')">
                            <div class="card-body text-center p-4">
                                <div class="icon-wrapper bg-warning text-white mx-auto shadow">
                                    <i class="ti ti-shopping-cart"></i>
                                </div>
                                <h4 class="fw-bold text-dark">Operations</h4>
                                <p class="text-secondary small mb-0">Sales, Purchases, POS, Returns, Pipeline</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card topic-card h-100 shadow-sm bg-info-lt cursor-pointer" onclick="showSubTopics('accounting')">
                            <div class="card-body text-center p-4">
                                <div class="icon-wrapper bg-info text-white mx-auto shadow">
                                    <i class="ti ti-calculator"></i>
                                </div>
                                <h4 class="fw-bold text-dark">Accounting</h4>
                                <p class="text-secondary small mb-0">Chart of Accounts, Journals, Trial Balance, Ledgers</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card topic-card h-100 shadow-sm bg-indigo-lt cursor-pointer" onclick="showSubTopics('reports')">
                            <div class="card-body text-center p-4">
                                <div class="icon-wrapper bg-indigo text-white mx-auto shadow">
                                    <i class="ti ti-report-analytics"></i>
                                </div>
                                <h4 class="fw-bold text-dark">Reports</h4>
                                <p class="text-secondary small mb-0">Income Statement, Balance Sheet, Stock Reports</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card topic-card h-100 shadow-sm bg-purple-lt cursor-pointer" onclick="showSubTopics('administration')">
                            <div class="card-body text-center p-4">
                                <div class="icon-wrapper bg-purple text-white mx-auto shadow">
                                    <i class="ti ti-shield-lock"></i>
                                </div>
                                <h4 class="fw-bold text-dark">Administration</h4>
                                <p class="text-secondary small mb-0">Audit Logs, User Management, Settings</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 2b: Sub-Topics List (drills into a topic category) -->
            <div class="modal-body py-4 px-4 px-md-5 d-none tutorial-step" id="tutorial-step-subtopics">
                <div class="d-flex align-items-center mb-4">
                    <button type="button" class="btn btn-ghost-secondary btn-sm me-3 px-2" onclick="backToTopics()">
                        <i class="ti ti-arrow-left me-1"></i>Back
                    </button>
                    <div>
                        <h3 class="fw-bold text-dark mb-0" id="subtopics-title">Topics</h3>
                        <p class="text-secondary mb-0 small" id="subtopics-subtitle">Select a page to navigate to.</p>
                    </div>
                </div>
                <div id="subtopics-list" class="list-group list-group-flush">
                    {{-- Populated dynamically by JS --}}
                </div>
            </div>

            <!-- STEP 3: Contextual Help (Page Specific) -->
            <div class="modal-body py-5 px-4 px-md-5 d-none tutorial-step" id="tutorial-step-context">
                <div class="text-center mb-5">
                    <div class="icon-wrapper bg-blue-lt text-blue mx-auto shadow-sm mb-3" style="width: 64px; height: 64px; border-radius: 16px; font-size: 32px; display: flex; align-items: center; justify-content: center;">
                        <i class="ti {{ $contextIcon }}"></i>
                    </div>
                    <h2 class="fw-bold text-dark mb-2">{{ $contextTitle }}</h2>
                    <p class="text-secondary fs-4">{{ $contextDesc }}</p>
                </div>

                <div class="row">
                    <div class="col-md-10 offset-md-1">
                        <div class="card shadow-sm border-0 bg-blue-lt">
                            <div class="card-body p-4">
                                <h4 class="fw-bold mb-3"><i class="ti ti-bulb text-warning me-2"></i>{{ __('Quick Guide:') }}</h4>
                                <ul class="text-secondary fs-4 context-list ps-3 mb-0">
                                    @foreach($contextSteps as $step)
                                        <li>{!! $step !!}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer" style="border-top: 1px solid rgba(0,0,0,0.05); padding: 16px 24px;">
                <button type="button" class="btn btn-ghost-secondary me-auto" onclick="closeTutorialExplicitly()">
                    <i class="ti ti-x me-2"></i>{{ __('Skip / Close') }}
                </button>
                <button type="button" class="btn btn-outline-primary shadow-sm px-4 me-2 d-none" id="btn-back-topics" onclick="showTopics()">
                    <i class="ti ti-grid-dots me-2"></i> {{ __('View All Topics') }}
                </button>
                <button type="button" class="btn btn-primary btn-pill shadow-sm px-4" id="btn-next-tutorial" onclick="showTopics()">
                    {{ __('Next: View Topics') }} <i class="ti ti-arrow-right ms-2"></i>
                </button>
            </div>
        </div>
    </div>
</div>
