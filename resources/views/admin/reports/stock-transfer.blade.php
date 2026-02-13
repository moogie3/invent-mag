@extends('admin.layouts.base')

@section('title', __('messages.stock_transfer'))

@section('content')
    <div class="page-wrapper">
        <!-- Header -->
        <div class="page-header d-print-none">
            <div class="{{ $containerClass ?? "container-xl" }}">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            {{ __('messages.reports') }}
                        </div>
                        <h2 class="page-title">
                            <i class="ti ti-forklift me-2"></i> {{ __('messages.stock_transfer') }}
                        </h2>
                    </div>
                    <div class="col-auto ms-auto d-print-none">
                        <div class="btn-list">
                            <a href="{{ route('admin.reports.adjustment-log') }}" class="btn btn-outline-secondary">
                                <i class="ti ti-list me-2"></i>
                                {{ __('messages.view_adjustment_log') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Body -->
        <div class="page-body">
            <div class="{{ $containerClass ?? "container-xl" }}">
                <div class="row row-cards">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <form action="{{ route('admin.reports.stock-transfer') }}" method="POST" id="stockTransferForm" data-same-warehouse-error="{{ __('messages.stock_transfer_same_warehouse_error') }}">
                                    @csrf
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">{{ __('messages.from_warehouse') }}</label>
                                            <select class="form-select @error('from_warehouse_id') is-invalid @enderror" name="from_warehouse_id" required>
                                                <option value="">{{ __('messages.select_warehouse') }}</option>
                                                @foreach ($warehouses as $warehouse)
                                                    <option value="{{ $warehouse->id }}" {{ old('from_warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                                        {{ $warehouse->name }}
                                                        @if ($warehouse->is_main)
                                                            ({{ __('messages.table_main') }})
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('from_warehouse_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">{{ __('messages.to_warehouse') }}</label>
                                            <select class="form-select @error('to_warehouse_id') is-invalid @enderror" name="to_warehouse_id" required>
                                                <option value="">{{ __('messages.select_warehouse') }}</option>
                                                @foreach ($warehouses as $warehouse)
                                                    <option value="{{ $warehouse->id }}" {{ old('to_warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                                        {{ $warehouse->name }}
                                                        @if ($warehouse->is_main)
                                                            ({{ __('messages.table_main') }})
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('to_warehouse_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label">{{ __('messages.product') }}</label>
                                            <select class="form-select @error('product_id') is-invalid @enderror" name="product_id" id="productSelect" data-placeholder="{{ __('messages.select_product') }}" required>
                                                <option value="">{{ __('messages.select_product') }}</option>
                                            </select>
                                            @error('product_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">{{ __('messages.available_quantity') }}</label>
                                            <input type="text" class="form-control" id="availableQuantity" readonly disabled>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">{{ __('messages.quantity_to_transfer') }}</label>
                                            <input type="number" class="form-control @error('quantity') is-invalid @enderror" name="quantity" min="1" required>
                                            @error('quantity')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label">{{ __('messages.reason') }}</label>
                                            <textarea class="form-control" name="reason" rows="3" placeholder="{{ __('messages.stock_transfer_reason_placeholder') }}">{{ old('reason') }}</textarea>
                                        </div>
                                    </div>
                                    <div class="card-footer d-flex justify-content-end mt-4">
                                        <a href="{{ route('admin.reports.adjustment-log') }}" class="btn btn-secondary me-2">{{ __('messages.cancel') }}</a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ti ti-transfer me-2"></i>{{ __('messages.transfer') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Stock Transfer - Direct inline script for debugging
        (function() {
            console.log('Stock Transfer inline script loaded');
            
            const fromWarehouse = document.querySelector('select[name="from_warehouse_id"]');
            const toWarehouse = document.querySelector('select[name="to_warehouse_id"]');
            const productSelect = document.getElementById('productSelect');
            const availableQty = document.getElementById('availableQuantity');
            const form = document.getElementById('stockTransferForm');
            
            console.log('Elements found:', {
                from: !!fromWarehouse,
                to: !!toWarehouse,
                product: !!productSelect,
                qty: !!availableQty,
                form: !!form
            });
            
            // Load products function
            function loadProducts(warehouseId) {
                console.log('Loading products for warehouse:', warehouseId);
                if (!warehouseId || !productSelect) return;
                
                fetch('/admin/reports/stock-transfer/products/' + warehouseId)
                    .then(res => res.json())
                    .then(data => {
                        console.log('Products loaded:', data);
                        productSelect.innerHTML = '<option value="">Select Product</option>';
                        
                        if (data && data.length > 0) {
                            data.forEach(p => {
                                const opt = document.createElement('option');
                                opt.value = p.id;
                                opt.textContent = p.name + ' (' + p.code + ') - Stock: ' + p.quantity;
                                opt.dataset.quantity = p.quantity;
                                productSelect.appendChild(opt);
                            });
                        } else {
                            productSelect.innerHTML = '<option value="">No products available</option>';
                        }
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        productSelect.innerHTML = '<option value="">Error loading</option>';
                    });
            }
            
            // Validate warehouses
            function validateSameWarehouse() {
                if (!fromWarehouse || !toWarehouse) return true;
                
                const fromVal = fromWarehouse.value;
                const toVal = toWarehouse.value;
                
                if (fromVal && toVal && fromVal === toVal) {
                    const msg = 'Source and destination warehouses must be different';
                    
                    // Try toast first, then alert
                    if (typeof InventMagApp !== 'undefined' && InventMagApp.showToast) {
                        InventMagApp.showToast('Warning', msg, 'warning');
                    } else {
                        alert(msg);
                    }
                    
                    toWarehouse.value = '';
                    return false;
                }
                return true;
            }
            
            // Event listeners
            if (fromWarehouse) {
                fromWarehouse.addEventListener('change', function() {
                    loadProducts(this.value);
                    validateSameWarehouse();
                });
            }
            
            if (toWarehouse) {
                toWarehouse.addEventListener('change', validateSameWarehouse);
            }
            
            if (productSelect) {
                productSelect.addEventListener('change', function() {
                    const opt = this.options[this.selectedIndex];
                    if (availableQty) {
                        availableQty.value = opt ? (opt.dataset.quantity || '0') : '0';
                    }
                });
            }
            
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (!validateSameWarehouse()) {
                        e.preventDefault();
                    }
                });
            }
            
            console.log('Stock Transfer inline script initialized');
        })();
    </script>
@endsection
