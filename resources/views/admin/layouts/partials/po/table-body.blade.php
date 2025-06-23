<tbody id="invoiceTableBody" class="table-tbody">
    @foreach ($pos as $index => $po)
        @include('admin.layouts.partials.po.table-row', ['po' => $po, 'index' => $index])
    @endforeach
</tbody>
