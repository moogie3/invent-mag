<div class="row g-3">
    <div class="col-md-6">
        <div class="form-check form-switch mt-3">
            <input class="form-check-input" type="checkbox" id="has_expiry" name="has_expiry" value="1"
                {{ $products->has_expiry ? 'checked' : '' }}>
            <label class="form-check-label" for="has_expiry">Product has expiration date</label>
        </div>
    </div>

    <div class="col-md-6 expiry-date-field" style="{{ $products->has_expiry ? '' : 'display: none;' }}">
        <label class="form-label">Expiry Date</label>
        <input type="date" class="form-control" name="expiry_date" id="expiry_date"
            value="{{ $products->expiry_date ? $products->expiry_date->format('Y-m-d') : '' }}">
    </div>
</div>
