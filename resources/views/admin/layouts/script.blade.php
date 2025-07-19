{{-- Load jQuery FIRST --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"
    integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

{{-- Load Tabler JS (remove defer since jQuery is already loaded) --}}
<script src="{{ asset('tabler/dist/js/tabler.min.js?1692870487') }}"></script>
<script src="{{ asset('tabler/dist/js/demo.min.js?1692870487') }}"></script>
<script src="{{ asset('tabler/dist/js/demo-theme.min.js?1692870487') }}"></script>

{{-- Load other external libraries --}}
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/list.js/2.3.1/list.min.js"></script>

{{-- Global Toast Helper --}}
@vite('resources/js/admin/helpers/toast.js')

{{-- SCRIPT FOR ADMIN POS  --}}
@if (request()->is('admin/pos'))
    @vite('resources/js/admin/pos.js')
@endif

{{-- SCRIPT FOR RECENT TRANSACTIONS  --}}
@if (request()->is('admin/transactions'))
    @vite('resources/js/admin/recentts.js')
@endif

{{-- SCRIPT FOR ADMIN LOGIN --}}
@if (request()->is('admin/login', 'admin/register', 'forgot-password'))
    @vite('resources/js/admin/auth.js')
@endif

{{-- SCRIPT FOR USER MANAGEMENT --}}
@if (request()->is('admin/users'))
    @vite('resources/js/admin/user.js')
@endif

{{-- SCRIPT FOR  PROFILE --}}
@if (request()->is('admin/settings/profile'))
    @vite('resources/js/admin/profile.js')
@endif

{{-- SCRIPT FOR ADMIN SALES CREATE & EDIT --}}
@if (request()->is('admin/sales', 'admin/sales/create', 'admin/sales/edit/*'))
    @vite('resources/js/admin/sales-order.js')
@endif

{{-- SCRIPT FOR ADMIN PO CREATE & EDIT --}}
@if (request()->is('admin/po', 'admin/po/create', 'admin/po/edit/*'))
    @vite('resources/js/admin/purchase-order.js')
@endif

{{-- SCRIPT FOR PRODUCT --}}
@if (request()->is('admin/product', 'admin/product/edit/*', 'admin/product/create'))
    @vite('resources/js/admin/product.js')
@endif

{{-- SCRIPT FOR ADMIN DASHBOARD --}}
@if (request()->is('admin/dashboard'))
    <script src="{{ asset('tabler/dist/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
@endif

{{-- SCRIPT FOR WAREHOUSE --}}
@if (request()->is('admin/warehouse'))
    @vite('resources/js/admin/warehouse.js')
@endif

{{-- SCRIPT FOR UNIT --}}
@if (request()->is('admin/settings/unit*'))
    @vite('resources/js/admin/unit.js')
@endif

{{-- SCRIPT FOR CATEGORY --}}
@if (request()->is('admin/settings/categories*'))
    @vite('resources/js/admin/category.js')
@endif

{{-- SCRIPT FOR SUPPLIER --}}
@if (request()->is('admin/supplier'))
    @vite('resources/js/admin/supplier.js')
@endif

{{-- SCRIPT FOR CUSTOMER --}}
@if (request()->is('admin/customer'))
    @vite('resources/js/admin/customer.js')
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
    @vite('resources/js/admin/sorting.js')
@endif

{{-- SCRIPT FOR CURRENCY settings --}}
@if (request()->is('admin/settings/currency'))
    @vite('resources/js/admin/currency.js')
@endif

{{-- SCRIPT FOR SALES PIPELINE --}}
@if (request()->is('admin/sales-pipeline'))
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
    @vite('resources/js/admin/sales-pipeline.js')
@endif

{{--  MODAL --}}
@if ($errors->any() || session('success'))
    @include('admin.layouts.modals')
    @vite('resources/js/admin/layouts/modal.js')
@endif

{{-- DELETE MODAL --}}
<script>
    function setDeleteFormAction(action) {
        document.getElementById('deleteForm').setAttribute('action', action);
    }
</script>

@stack('scripts')
