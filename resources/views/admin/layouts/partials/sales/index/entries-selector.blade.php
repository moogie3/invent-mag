<div class="mb-2 text-end">
    <form method="GET" action="{{ request()->url() }}" data-turbo="false" class="d-inline">
        @foreach(request()->except('entries', 'page') as $key => $value)
            @if(is_array($value))
                @foreach($value as $v)
                    <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                @endforeach
            @else
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
        {{ __('messages.show') }}
        <div class="mx-1 mt-2 d-inline-block">
            <select name="entries" id="entriesSelect" onchange="this.form.submit()">
                <option value="10" {{ $entries == 10 ? 'selected' : '' }}>10</option>
                <option value="25" {{ $entries == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ $entries == 50 ? 'selected' : '' }}>50</option>
            </select> {{ __('messages.entries') }}
        </div>
    </form>
</div>
