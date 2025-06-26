<div class="mb-2 text-end">
    Filter by:
    <form method="GET" action="{{ route('admin.po') }}" class="d-inline-block">
        <select name="month" class="form-select form-select-sm d-inline-block w-auto">
            <option value="">Select Month</option>
            @foreach (range(1, 12) as $m)
                <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                    {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                </option>
            @endforeach
        </select>
        <select name="year" class="form-select form-select-sm d-inline-block w-auto">
            <option value="">Select Year</option>
            @foreach (range(date('Y') - 5, date('Y')) as $y)
                <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>
                    {{ $y }}
                </option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
    </form>
</div>
