<div class="card">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label required">{{ __('messages.date') }}</label>
                <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="col-md-8">
                <label class="form-label required">{{ __('messages.description') }}</label>
                <input type="text" name="description" class="form-control" placeholder="{{ __('messages.journal_entry_description_placeholder') }}" required>
            </div>
            <div class="col-12">
                <label class="form-label">{{ __('messages.notes') }}</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="{{ __('messages.optional_notes') }}"></textarea>
            </div>
        </div>
    </div>
</div>
