<button onclick="bulkMarkAsPaidPO()" class="btn btn-success action-btn d-flex align-items-center">
    <i class="ti ti-check me-2"></i> {{ __('messages.mark_as_paid') }}
</button>
<button onclick="bulkExportPO()" class="btn btn-secondary action-btn d-flex align-items-center">
    <i class="ti ti-download me-2"></i> {{ __('messages.export') }}
</button>
<button onclick="bulkDeletePO()" class="btn btn-danger action-btn d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#bulkDeleteModal">
    <i class="ti ti-trash me-2"></i> {{ __('messages.delete') }}
</button>
<button onclick="clearPOSelection()" class="btn btn-outline-secondary action-btn d-flex align-items-center">
    <i class="ti ti-x me-2"></i> {{ __('messages.clear_selection') }}
</button>
