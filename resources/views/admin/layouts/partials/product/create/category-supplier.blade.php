<div class="mb-2 pb-2">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="supplier_id">{{ __('messages.supplier') }}</label>
            <select class="form-select @error('supplier_id') is-invalid @enderror" name="supplier_id" id="supplier_id">
                <option value="">{{ __('messages.select_supplier') }}</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                        {{ $supplier->name }}
                    </option>
                @endforeach
            </select>
            @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="category_id">{{ __('messages.category') }}</label>
            <select class="form-select @error('category_id') is-invalid @enderror" name="category_id" id="category_id">
                <option value="">{{ __('messages.select_category') }}</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
