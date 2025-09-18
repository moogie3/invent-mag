<div class="mb-2 pb-2">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">{{ __('supplier') }}</label>
            <select class="form-select" name="supplier_id">
                <option value="">{{ __('Select Supplier') }}</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">
                        {{ $supplier->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">{{ __('category') }}</label>
            <select class="form-select" name="category_id">
                <option value="">{{ __('Select Category') }}</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>
