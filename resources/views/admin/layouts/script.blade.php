{{-- Load jQuery FIRST --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

{{-- Load Tabler JS (remove defer since jQuery is already loaded) --}}
<script src="{{ asset('tabler/dist/js/tabler.min.js?1692870487') }}"></script>
<script src="{{ asset('tabler/dist/js/demo.min.js?1692870487') }}"></script>
<script src="{{ asset('tabler/dist/js/demo-theme.min.js?1692870487') }}"></script>

{{-- Load other external libraries --}}
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/list.js/2.3.1/list.min.js"></script>

{{-- SCRIPT FOR ADMIN POS  --}}
@if (request()->is('admin/pos'))
    <script src="{{ asset('js/admin/pos.js') }}"></script>
@endif

{{-- SCRIPT FOR RECENT TRANSACTIONS  --}}
@if (request()->is('admin/transactions'))
    <script src="{{ asset('js/admin/recentts.js') }}"></script>
@endif

{{-- SCRIPT FOR ADMIN LOGIN --}}
@if (request()->is('admin/login', 'admin/register', 'forgot-password'))
    <script src="{{ asset('js/admin/auth.js') }}"></script>
@endif

{{-- SCRIPT FOR USER MANAGEMENT --}}
@if (request()->is('admin/users'))
    <script src="{{ asset('js/admin/user.js') }}"></script>
@endif

{{-- SCRIPT FOR  PROFILE --}}
@if (request()->is('admin/settings/profile'))
    <script src="{{ asset('js/admin/profile.js') }}"></script>
@endif

{{-- SCRIPT FOR ADMIN SALES CREATE & EDIT --}}
@if (request()->is('admin/sales', 'admin/sales/create', 'admin/sales/edit/*'))
    <script src="{{ asset('js/admin/sales-order.js') }}"></script>
@endif

{{-- SCRIPT FOR ADMIN PO CREATE & EDIT --}}
@if (request()->is('admin/po', 'admin/po/create', 'admin/po/edit/*'))
    <script src="{{ asset('js/admin/purchase-order.js') }}"></script>
@endif

{{-- SCRIPT FOR PRODUCT --}}
@if (request()->is('admin/product', 'admin/product/edit/*', 'admin/product/create'))
    <script src="{{ asset('js/admin/product.js') }}"></script>
@endif

{{-- SCRIPT FOR ADMIN DASHBOARD --}}
@if (request()->is('admin/dashboard'))
    <script src="{{ asset('tabler/dist/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
@endif

{{-- SCRIPT FOR WAREHOUSE --}}
@if (request()->is('admin/warehouse'))
    <script src="{{ asset('js/admin/warehouse.js') }}"></script>
@endif

{{-- SCRIPT FOR UNIT --}}
@if (request()->is('admin/settings/unit'))
    <script src="{{ asset('js/admin/unit.js') }}"></script>
@endif

{{-- SCRIPT FOR CATEGORY --}}
@if (request()->is('admin/settings/category'))
    <script src="{{ asset('js/admin/category.js') }}"></script>
@endif

{{-- SCRIPT FOR SUPPLIER --}}
@if (request()->is('admin/supplier'))
    <script src="{{ asset('js/admin/supplier.js') }}"></script>
@endif

{{-- SCRIPT FOR CUSTOMER --}}
@if (request()->is('admin/customer'))
    <script src="{{ asset('js/admin/customer.js') }}"></script>
@endif

{{-- SCRIPT FOR SORTING TABLE --}}
@if (request()->is(
        'admin/warehouse',
        'admin/po',
        'admin/sales',
        'admin/product',
        'admin/supplier',
        'admin/customer',
        'admin/settings/unit',
        'admin/settings/category'))
    <script src="{{ asset('js/admin/sorting.js') }}"></script>
@endif

{{-- SCRIPT FOR CURRENCY settings --}}
@if (request()->is('admin/settings/currency'))
    <script src="{{ asset('js/admin/currency.js') }}"></script>
@endif

{{--  MODAL --}}
@if ($errors->any() || session('success'))
    @include('admin.layouts.modals')
    <script src="{{ asset('js/admin/layouts/modal.js') }}"></script>
@endif

{{-- DELETE MODAL --}}
<script>
    function setDeleteFormAction(action) {
        document.getElementById('deleteForm').setAttribute('action', action);
    }
</script>
