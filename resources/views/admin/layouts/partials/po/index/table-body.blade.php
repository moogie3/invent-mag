<tbody id="invoiceTableBody" class="table-tbody">
    @forelse ($pos as $index => $po)
        @include('admin.layouts.partials.po.index.table-row', ['po' => $po, 'index' => $index])
    @empty
        <tr>
            <td colspan="12">
                <div class="empty">
                    <div class="empty-img">
                        <i class="ti ti-mood-sad" style="font-size: 5rem; color: #ccc;"></i>
                    </div>
                    <p class="empty-title">{{ __('messages.no_purchase_orders_found') }}</p>
                    <p class="empty-subtitle text-muted">
                        {{ __('messages.it_looks_like_you_havent_added_any_purchase_orders_yet') }}
                    </p>
                </div>
            </td>
        </tr>
    @endforelse
</tbody>
