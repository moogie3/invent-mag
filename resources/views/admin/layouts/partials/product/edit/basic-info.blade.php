<div class="row g-3">
    <div class="col-md-2">
        <label class="form-label">{{ __('messages.code') }}</label>
        <input type="text" name="code" class="form-control" value="{{ $products->code }}" readonly>
    </div>

    <div class="col-md-3">
        <label class="form-label">{{ __('messages.barcode') }}</label>
        <input type="text" name="barcode" class="form-control" value="{{ $products->barcode }}">
    </div>

    <div class="col-md-3">
        <label class="form-label">{{ __('messages.supplier') }}</label>
        <select name="supplier_id" class="form-select">
            <option value="{{ $products->supplier_id }}">{{ $products->supplier->name }}</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __('messages.product_name') }}</label>
        <input type="text" name="name" class="form-control" value="{{ $products->name }}">
    </div>
</div>
