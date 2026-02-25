<div id="invoiceTableContainer">
    <div class="table-responsive">
        <table class="table card-table table-vcenter">
            <thead style="font-size: large">
                <tr>
                    <th class="no-print">
                        <button class="table-sort fs-4 py-3 no-print" data-sort="sort-no">{{ __('messages.table_no') }}</button>
                    </th>
                    <th>
                        <button class="table-sort fs-4 py-3" data-sort="sort-name">{{ __('messages.table_name') }}</button>
                    </th>
                    <th>
                        <button class="table-sort fs-4 py-3" data-sort="sort-address">{{ __('messages.table_address') }}</button>
                    </th>
                    <th>
                        <button class="table-sort fs-4 py-3" data-sort="sort-description">{{ __('messages.table_description') }}</button>
                    </th>
                    <th>
                        <button class="table-sort fs-4 py-3" data-sort="sort-is-main">{{ __('messages.table_main') }}</button>
                    </th>
                    <th style="width:180px;text-align:center" class="fs-4 py-3 no-print">{{ __('messages.table_action') }}</th>
                </tr>
            </thead>
            <tbody id="invoiceTableBody" class="table-tbody">
                @forelse ($wos as $index => $wo)
                    @include('admin.layouts.partials.warehouse.index.table-row', [
                        'wo' => $wo,
                        'index' => $index,
                    ])
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty">
                                <div class="empty-img">
                                    <i class="ti ti-mood-sad" style="font-size: 5rem; color: #ccc;"></i>
                                </div>
                                <p class="empty-title">{{ __('messages.no_warehouses_found') }}</p>
                                <p class="empty-subtitle text-muted">
                                    {{ __('messages.it_looks_like_you_havent_added_any_warehouses_yet') }}
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
