<div class="row g-3">
    <div class="col-md-2">
        <label class="form-label" for="code">{{ __('messages.code') }}</label>
        <input type="text" name="code" id="code" class="form-control" value="{{ $products->code }}" readonly>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="barcode">{{ __('messages.barcode') }}</label>
        <input type="text" name="barcode" id="barcode" class="form-control @error('barcode') is-invalid @enderror" value="{{ old('barcode', $products->barcode) }}">
        @error('barcode')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label class="form-label" for="supplier_id">{{ __('messages.supplier') }}</label>
        <select name="supplier_id" id="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror">
            <option value="{{ $products->supplier_id }}">{{ $products->supplier->name }}</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" {{ old('supplier_id', $products->supplier_id) == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
            @endforeach
        </select>
        @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label" for="name">{{ __('messages.product_name') }}</label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $products->name) }}">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
