<script src="{{ asset('tabler/dist/js/tabler.min.js?1692870487') }}" defer></script>
<script src="{{ asset('tabler/dist/js/demo.min.js?1692870487') }}" defer></script>
<script src="{{ asset('tabler/dist/js/demo-theme.min.js?1692870487') }}"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/list.js/2.3.1/list.min.js"></script>
{{-- SCRIPT FOR ADMIN POS  --}}
@if (request()->is('admin/pos'))
    <script src="{{ asset('js/admin/pos.js') }}"></script>
@endif
{{-- SCRIPT FOR ADMIN LOGIN  --}}
@if (request()->is('admin/login', 'admin/register', 'forgot-password'))
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('tabler/dist/js/demo-theme.min.js') }}"></script>
    <script src="{{ asset('js/admin/auth.js') }}"></script>
@endif
{{-- SCRIPT FOR SETTING PROFILE --}}
@if (request()->is('admin/setting/profile'))
    <script src="{{ asset('js/admin/profile.js') }}"></script>
@endif
{{-- SCRIPT FOR ADMIN SALES CREATE & EDIT --}}
@if (request()->is('admin/sales/create', 'admin/sales/edit/*'))
    <script src="{{ asset('js/admin/sales-order.js') }}"></script>
@endif
{{-- SCRIPT FOR ADMIN PO CREATE & EDIT --}}
@if (request()->is('admin/po/create', 'admin/po/edit/*'))
    <script src="{{ asset('js/admin/purchase-order.js') }}"></script>
@endif
{{-- SCRIPT FOR ADMIN DASHBOARD --}}
@if (request()->is('admin/dashboard'))
    <script src="{{ asset('tabler/dist/libs/apexcharts/dist/apexcharts.min.js') }}" defer></script>
    <script>
        window.onload = function() {
            var chartElement = document.querySelector("#chart-container");

            if (!chartElement) {
                console.error("Chart container not found! Check if #chart-container exists in the DOM.");
                return;
            }

            var invoicesData = @json($chartData);
            var earningsData = @json($chartDataEarning);

            function renderChart(type) {
                var options;
                if (type === "invoices") {
                    options = {
                        series: [{
                                name: "Invoices Count",
                                type: "bar",
                                data: invoicesData.map(item => item.invoice_count)
                            },
                            {
                                name: "Total Amount",
                                type: "line",
                                data: invoicesData.map(item => item.total_amount_raw)
                            }
                        ],
                        xaxis: {
                            categories: invoicesData.map(item => item.date)
                        }
                    };
                }

                if (type === "earnings") {
                    options = {
                        series: [{
                            name: "Daily Earnings",
                            type: "line",
                            data: earningsData.map(item => item.total_amount_raw)
                        }],
                        xaxis: {
                            categories: earningsData.map(item => item.date)
                        }
                    };
                }

                options = {
                    ...options,
                    chart: {
                        type: "line",
                        height: 400
                    },
                    stroke: {
                        width: [2, 4]
                    },
                    plotOptions: {
                        bar: {
                            horizontal: false
                        }
                    },
                    colors: ["#206bc4", "#f59f00"],
                    tooltip: {
                        y: {
                            formatter: function(val) {
                                return "{{ \App\Helpers\CurrencyHelper::format(0) }}".replace("0", val);
                            }
                        }
                    }
                };

                if (window.chartInstance) {
                    window.chartInstance.destroy();
                }

                window.chartInstance = new ApexCharts(chartElement, options);
                window.chartInstance.render();
            }

            // Initial Load
            renderChart("invoices");

            // Tab Click Events
            document.querySelector("#invoices-tab").addEventListener("click", function() {
                renderChart("invoices");
            });

            document.querySelector("#earnings-tab").addEventListener("click", function() {
                renderChart("earnings");
            });
        };
    </script>
@endif
{{-- SCRIPT FOR WAREHOUSE --}}
@if (request()->is('admin/warehouse'))
    <script src="{{ asset('js/admin/warehouse.js') }}"></script>
@endif
{{-- SCRIPT FOR UNIT --}}
@if (request()->is('admin/setting/unit'))
    <script src="{{ asset('js/admin/unit.js') }}"></script>
@endif
{{-- SCRIPT FOR CATEGORY --}}
@if (request()->is('admin/setting/category'))
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
        'admin/setting/unit',
        'admin/setting/category'))
    <script src="{{ asset('js/admin/sorting.js') }}"></script>
@endif
{{-- SCRIPT FOR CURRENCY SETTING --}}
@if (request()->is('admin/setting/currency'))
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
