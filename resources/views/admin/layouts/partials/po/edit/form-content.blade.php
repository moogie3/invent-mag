<form id="edit-po-form" enctype="multipart/form-data" method="POST" action="{{ route('admin.po.update', $pos->id) }}">
    @csrf
    @method('PUT')
    <div class="card-body p-4">
        @php
            // Calculate summary info once and make it available to all partials
            $summary = \App\Helpers\PurchaseHelper::calculateInvoiceSummary(
                $pos->items,
                $pos->discount_total,
                $pos->discount_total_type,
            );
        @endphp

        @include('admin.layouts.partials.po.edit.po-info-section')
        @include('admin.layouts.partials.po.edit.items-table')
        @include('admin.layouts.partials.po.edit.summary-section')
        <input type="hidden" name="products" id="products-json">
    </div>
</form>
