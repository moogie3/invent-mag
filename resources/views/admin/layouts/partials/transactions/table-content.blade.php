<div class="table-responsive">
    <table class="table table-vcenter table-hover mb-0">
        <thead>
            <tr>
                <th class="w-1">
                    <input class="form-check-input" type="checkbox" id="selectAll">
                </th>
                <th>
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'type', 'direction' => request('sort') == 'type' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                        class="table-sort {{ request('sort') == 'type' ? 'table-sort-' . request('direction', 'asc') : '' }}">
                        Type
                    </a>
                </th>
                <th>
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'invoice', 'direction' => request('sort') == 'invoice' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                        class="table-sort {{ request('sort') == 'invoice' ? 'table-sort-' . request('direction', 'asc') : '' }}">
                        Invoice
                    </a>
                </th>
                <th>
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'customer_supplier', 'direction' => request('sort') == 'customer_supplier' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                        class="table-sort {{ request('sort') == 'customer_supplier' ? 'table-sort-' . request('direction', 'asc') : '' }}">
                        Customer/Supplier
                    </a>
                </th>
                <th>
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'date', 'direction' => request('sort') == 'date' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                        class="table-sort {{ request('sort') == 'date' ? 'table-sort-' . request('direction', 'asc') : '' }}">
                        Date
                    </a>
                </th>
                <th class="text-end">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'amount', 'direction' => request('sort') == 'amount' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                        class="table-sort {{ request('sort') == 'amount' ? 'table-sort-' . request('direction', 'asc') : '' }}">
                        Amount
                    </a>
                </th>
                <th class="text-center">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'direction' => request('sort') == 'status' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                        class="table-sort {{ request('sort') == 'status' ? 'table-sort-' . request('direction', 'asc') : '' }}">
                        Status
                    </a>
                </th>
                <th class="w-1">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions as $transaction)
                @include('admin.layouts.partials.transactions.table-row', ['transaction' => $transaction])
            @empty
                @include('admin.layouts.partials.transactions.empty-state')
            @endforelse
        </tbody>
    </table>
</div>
