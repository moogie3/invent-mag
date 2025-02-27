<script src="{{ asset('tabler/dist/js/tabler.min.js?1692870487') }}" defer></script>
<script src="{{ asset('tabler/dist/js/demo.min.js?1692870487') }}" defer></script>
<script src="{{ asset('tabler/dist/js/demo-theme.min.js?1692870487') }}"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/list.js/2.3.1/list.min.js"></script>
@if (
    !request()->is(
        'admin/dashboard',
        'admin/po/create',
        'admin/po/edit',
        'admin/sales/create',
        'admin/sales/edit',
        'admin/product/create',
        'admin/product/edit',
        'admin/supplier/create',
        'admin/supplier/edit',
        'admin/customer/create',
        'admin/customer/edit',
        'admin/unit/create',
        'admin/unit/edit',
        'admin/category/create',
        'admin/category/edit'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize List.js
            const list = new List('invoiceTableContainer', {
                sortClass: 'table-sort',
                listClass: 'table-tbody',
                valueNames: [
                    'sort-no',
                    'sort-invoice',
                    'sort-supplier',
                    'sort-orderdate',
                    'sort-quantity',
                    'sort-name',
                    'sort-description',
                    'sort-phonenumber',
                    'sort-code',
                    'sort-address',
                    'sort-location',
                    'sort-paymentterms',
                    'sort-category',
                    'sort-price',
                    'sort-sellingprice',
                    'sort-unit',
                    {
                        name: 'sort-duedate',
                        attr: 'data-date'
                    },
                    {
                        name: 'sort-amount',
                        attr: 'data-amount'
                    },
                    'sort-amount',
                    'sort-payment',
                    'sort-status',
                ],
            });

            // Enhanced search for formatted and raw amounts
            const searchInput = document.getElementById('searchInput');
            const tableRows = document.querySelectorAll('#invoiceTableBody tr');

            searchInput.addEventListener('keyup', function() {
                const searchTerm = searchInput.value.toLowerCase();

                tableRows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const rawAmount = row.querySelector('.raw-amount')?.textContent.toLowerCase() ||
                        '';

                    // Match either formatted text OR raw amount
                    row.style.display = (text.includes(searchTerm) || rawAmount.includes(
                        searchTerm)) ? '' : 'none';
                });
            });
        });
    </script>
@endif
