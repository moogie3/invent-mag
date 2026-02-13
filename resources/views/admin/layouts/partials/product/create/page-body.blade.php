<div class="page-body">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row row-cards">
            <div class="col-12">
                <div class="card shadow-sm border">
                    <div class="card-header bg-light">
                        <h3 class="card-title d-flex align-items-center">
                            <i class="ti ti-package me-2"></i> {{ __('messages.product_information') }}
                        </h3>
                    </div>
                    <div class="card-body">
                        @include('admin.layouts.partials.product.create.form')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
