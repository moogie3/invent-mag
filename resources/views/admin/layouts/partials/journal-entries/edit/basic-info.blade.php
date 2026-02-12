<div class="card">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label required">{{ __('messages.date') }}</label>
                <input type="date" name="date" class="form-control" value="{{ $entry->date->toDateString() }}" required>
            </div>
            <div class="col-md-8">
                <label class="form-label required">{{ __('messages.description') }}</label>
                <input type="text" name="description" class="form-control" value="{{ $entry->description }}" required>
            </div>
            <div class="col-12">
                <label class="form-label">{{ __('messages.notes') }}</label>
                <textarea name="notes" class="form-control" rows="2">{{ $entry->notes }}</textarea>
            </div>
        </div>
    </div>
</div>
