<form id="edit-sales-form" enctype="multipart/form-data" method="POST"
    action="{{ route('admin.sales.update', $sales->id) }}">
    @csrf
    @method('PUT')

    <div class="card-body p-4">
        @php
            // Use the helper to calculate summary info
            $summary = \App\Helpers\SalesHelper::calculateInvoiceSummary(
                $sales->salesItems,
                $sales->order_discount ?? 0,
                $sales->order_discount_type ?? 'percentage',
                $sales->tax_rate ?? 0,
            );
        @endphp

        @include('admin.layouts.partials.sales.edit.sales-info-section')
        @include('admin.layouts.partials.sales.edit.items-table')
        @include('admin.layouts.partials.sales.edit.summary-section')
    </div>
</form>
