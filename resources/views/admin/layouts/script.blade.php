{{-- Tabler JS. Every library below is served from public/ rather than a
     CDN: nothing here waits on an external host before the page can run,
     and nothing can change version out from under us. Chart.js and
     Flatpickr are no longer globals at all, they are bundled by Vite into
     the page that actually uses them. --}}
<script src="{{ asset('tabler/dist/js/tabler.min.js?1692870487') }}"></script>
<script src="{{ asset('tabler/dist/js/demo.min.js?1692870487') }}"></script>
<script src="{{ asset('tabler/dist/libs/list.js/dist/list.min.js?1692870487') }}"></script>

{{-- Global Notification Helper --}}
@vite(['resources/js/admin/utils/sound.js', 'resources/js/admin/helpers/notification.js'])

{{-- Global Keyboard Shortcuts - MUST load FIRST before other scripts use it --}}
@vite('resources/js/admin/layouts/global-keyboard-shortcuts.js')

{{--
    NOTE: All page-specific scripts are loaded unconditionally so that
    Turbo Drive navigation (which reuses the same JS bundle from the initial
    page load) can re-initialize the correct JS on every page visit.
    Each script guards itself via window.location.pathname checks.
--}}

{{-- SCRIPT FOR ADMIN POS --}}
@vite('resources/js/admin/pos.js')

{{-- SCRIPT FOR RECENT TRANSACTIONS --}}
@vite('resources/js/admin/recentts.js')

{{-- SCRIPT FOR USER MANAGEMENT --}}
@vite('resources/js/admin/user.js')

{{-- SCRIPT FOR PROFILE --}}
@vite('resources/js/admin/profile.js')

{{-- SCRIPT FOR ADMIN SALES CREATE & EDIT --}}
@vite('resources/js/admin/sales-order.js')

{{-- SCRIPT FOR ADMIN SALES RETURN --}}
@vite('resources/js/admin/sales-return.js')

{{-- SCRIPT FOR SENDING THE INVOICE EMAIL (sales view) --}}
@vite('resources/js/admin/sales/invoice-email.js')

{{-- SCRIPT FOR ADMIN PO --}}
@vite('resources/js/admin/purchase-order.js')
@vite('resources/js/admin/paid-invoice-modal.js')

{{-- SCRIPT FOR ADMIN PURCHASE RETURN --}}
@vite('resources/js/admin/purchase-return.js')

{{-- SCRIPT FOR PRODUCT --}}
@vite('resources/js/admin/product.js')

{{-- SCRIPT FOR WAREHOUSE --}}
@vite('resources/js/admin/warehouse.js')

{{-- SCRIPT FOR UNIT --}}
@vite('resources/js/admin/unit.js')

{{-- SCRIPT FOR CATEGORY --}}
@vite('resources/js/admin/category.js')

{{-- SCRIPT FOR SUPPLIER --}}
@vite('resources/js/admin/supplier.js')

{{-- SCRIPT FOR CUSTOMER --}}
@vite('resources/js/admin/customer.js')
@vite('resources/js/admin/customer-credit.js')

{{-- SCRIPT FOR STOCK OPNAME --}}
@vite('resources/js/admin/stock-opname.js')

{{-- SCRIPT FOR SORTING TABLE --}}
@vite('resources/js/admin/sorting.js')

{{-- SCRIPT FOR CURRENCY settings --}}
@vite('resources/js/admin/currency.js')

{{-- GLOBAL FALLBACK FUNCTIONS FOR JOURNAL ENTRIES - Load before journal-entry.js --}}
{{-- SCRIPT FOR ACCOUNTING settings --}}
@vite('resources/js/admin/accounting.js')

{{-- SCRIPT FOR FINANCIAL HEALTH trend charts (Accounting > Financial Health) --}}
@vite('resources/js/admin/financial-health.js')

{{-- SCRIPT FOR JOURNAL ENTRIES --}}
@vite('resources/js/admin/journal-entry.js')

{{-- SCRIPT FOR OPENING BALANCE --}}
@vite('resources/js/admin/opening-balance.js')

{{-- SCRIPT FOR ADJUSTMENT LOG --}}
@vite('resources/js/admin/adjustment-log.js')

{{-- SCRIPT FOR AUDIT LOG --}}
@vite('resources/js/admin/audit-log.js')

{{-- SCRIPT FOR STOCK TRANSFER --}}
@vite('resources/js/admin/reports/stock-transfer.js')

{{-- SCRIPT FOR TAX settings --}}
@vite('resources/js/admin/tax.js')

{{-- SCRIPT FOR QUOTATIONS CREATE & EDIT --}}
@vite('resources/js/admin/quotation.js')

{{-- SCRIPT FOR SALES PIPELINE (SortableJS only loaded when needed) --}}
@vite('resources/js/admin/sales-pipeline.js')

{{-- SCRIPT FOR ADMIN LOGIN & PASSWORD RESET --}}
@vite('resources/js/admin/auth.js')
@vite('resources/js/admin/reset-password.js')

{{-- SCRIPT FOR SETTINGS --}}
@vite('resources/js/admin/layouts/settings.js')
@vite('resources/js/admin/layouts/advanced-settings.js')
@vite('resources/js/admin/backup.js')
@vite('resources/js/admin/hr.js')
@vite('resources/js/admin/layouts/selectable-table.js')

{{-- SCRIPT FOR NAVBAR --}}
@vite('resources/js/admin/layouts/navbar.js')

{{-- REFACTORED INLINE SCRIPTS --}}
@vite('resources/js/admin/layouts/legacy-inline-scripts.js')

{{-- SCRIPT FOR NOTIFICATIONS PAGE --}}
@vite('resources/js/admin/notifications.js')

{{-- BOOTSTRAP RE-INITIALIZATION FOR TURBO --}}
@vite('resources/js/admin/bootstrap-init.js')

{{-- SCRIPT FOR TUTORIAL MODAL --}}
@vite('resources/js/admin/layouts/tutorial-modal.js')

{{-- SCRIPT FOR ADMIN DASHBOARD (ApexCharts lazy-loaded) --}}
{{-- DELETE MODAL --}}
