<div class="dropdown">
    <a href="#" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
        <i class="ti ti-download me-2"></i> Export
    </a>
    <div class="dropdown-menu">
        @foreach ([['icon' => 'ti-file-type-pdf', 'text' => 'Export as PDF'], ['icon' => 'ti-file-type-csv', 'text' => 'Export as CSV'], ['icon' => 'ti-printer', 'text' => 'Print Report']] as $item)
            <a class="dropdown-item" href="#"><i class="ti {{ $item['icon'] }} me-2"></i> {{ $item['text'] }}</a>
        @endforeach
    </div>
</div>
