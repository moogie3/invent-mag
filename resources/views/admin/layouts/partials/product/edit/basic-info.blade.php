<div class="row g-3">
    <div class="col-md-2">
        <label class="form-label">{{ __('Code') }}</label>
        <input type="text" class="form-control" value="{{ $products->code }}" disabled>
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __('Supplier') }}</label>
        <select name="supplier_id" class="form-select">
            <option value="{{ $products->supplier_id }}">{{ $products->supplier->name }}</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('Product Name') }}</label>
        <input type="text" name="name" class="form-control" value="{{ $products->name }}">
    </div>
</div>
