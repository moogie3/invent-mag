<div>
    <h4 class="fw-semibold mb-3 text-secondary d-flex align-items-center">
        <i class="ti ti-settings me-2 text-muted"></i> Additional Details
    </h4>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Product Image</label>
            <input type="file" class="form-control @error('image') is-invalid @enderror" name="image">
            <small class="form-text text-muted">Recommended size: 400x400px (Max: 2MB)</small>
        </div>
        <div class="col-md-2"></div>
        <div class="col-md-6">
            <label class="form-label">Expiration Settings</label>
            <div class="row g-2 align-items-center">
                <div class="col-auto d-flex align-items-center">
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" id="has_expiry" name="has_expiry"
                            value="1">
                        <label class="form-check-label ms-2" for="has_expiry">Has expiry</label>
                    </div>
                </div>
                <div class="col">
                    <div id="expiry_date_container" style="display: none;">
                        <input type="date" class="form-control" name="expiry_date" id="expiry_date"
                            placeholder="Select expiry date">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
