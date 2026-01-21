<button onclick="bulkMarkAsPaidSales()" class="btn btn-success action-btn d-flex align-items-center">
    <i class="ti ti-check me-2"></i> {{ __('messages.mark_as_paid') }}
</button>
<div class="btn-group">
    <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="ti ti-download me-2"></i> {{ __('messages.export') }}
    </button>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="#" onclick="bulkExportSales('pdf')">Export as PDF</a></li>
        <li><a class="dropdown-item" href="#" onclick="bulkExportSales('csv')">Export as CSV</a></li>
    </ul>
</div>
<button onclick="bulkDeleteSales()" class="btn btn-danger action-btn d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#bulkDeleteModal">
    <i class="ti ti-trash me-2"></i> {{ __('messages.delete') }}
</button>
<button onclick="clearSalesSelection()" class="btn btn-outline-secondary action-btn d-flex align-items-center">
    <i class="ti ti-x me-2"></i> {{ __('messages.clear_selection') }}
</button>
