<tbody id="invoiceTableBody" class="table-tbody">
    @foreach ($sales as $index => $sale)
        @include('admin.layouts.partials.sales.index.table-row', ['sale' => $sale, 'index' => $index])
    @endforeach
</tbody>
