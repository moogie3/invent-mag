<div class="mb-5 border-bottom pb-4">
    <h4 class="card-title mb-4">
        <i class="ti ti-shopping-cart me-2 text-primary"></i> {{ __('messages.po_order_information_title') }}
    </h4>
    <div class="row g-4">
        <div class="col-md-6">
            @include('admin.layouts.partials.po.view.supplier-info')
        </div>
        <div class="col-md-6">
            @include('admin.layouts.partials.po.view.order-info')
        </div>
    </div>
</div>
