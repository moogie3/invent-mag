<div class="mb-2 text-end">
    {{ __('messages.filter_by') }}
    <form method="GET" action="{{ route('admin.sales') }}" class="d-inline-block">
            {{-- Carry every active filter across the submit; without this
                 applying a date range silently clears the search term. --}}
            @foreach (request()->except(['month', 'year', 'page']) as $key => $value)
                <input type="hidden" name="{{ $key }}"
                    value="{{ is_array($value) ? json_encode($value) : $value }}">
            @endforeach
        <select name="month" class="form-select form-select-sm d-inline-block w-auto">
            <option value="">{{ __('messages.select_month') }}</option>
            @foreach (range(1, 12) as $m)
                <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                    {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                </option>
            @endforeach
        </select>
        <select name="year" class="form-select form-select-sm d-inline-block w-auto">
            <option value="">{{ __('messages.select_year') }}</option>
            @foreach (range(date('Y') - 5, date('Y')) as $y)
                <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>
                    {{ $y }}
                </option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-sm btn-primary">{{ __('messages.filter') }}</button>
    </form>
</div>
