<div class="card">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label required" for="date">{{ __('messages.date') }}</label>
                <input type="date" name="date" id="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date', date('Y-m-d')) }}" required>
                @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-8">
                <label class="form-label required" for="description">{{ __('messages.description') }}</label>
                <input type="text" name="description" id="description" class="form-control @error('description') is-invalid @enderror" placeholder="{{ __('messages.journal_entry_description_placeholder') }}" value="{{ old('description') }}" required>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label" for="notes">{{ __('messages.notes') }}</label>
                <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" placeholder="{{ __('messages.optional_notes') }}">{{ old('notes') }}</textarea>
                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>
