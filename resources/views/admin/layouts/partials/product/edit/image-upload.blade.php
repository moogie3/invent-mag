<div class="row g-3 align-items-end">
    <div class="col-md-6">
        <label class="form-label">Current Image</label>
        <div class="mb-2">
            <img src="{{ asset($products->image) }}" alt="Product Image" class="rounded border shadow-sm" width="200">
        </div>
        <input type="file" name="image" class="form-control">
        <small class="text-muted">Upload a new image to replace the current one.</small>
    </div>
</div>
