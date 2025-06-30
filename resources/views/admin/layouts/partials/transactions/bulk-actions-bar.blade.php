<div id="bulkActionsBar" class="bulk-actions-bar border-bottom sticky-top" style="display: none;">
    <div class="px-4 py-3">
        <div class="row align-items-center">
            <div class="col-lg-6 col-md-12">
                <div class="d-flex align-items-center">
                    <div
                        class="selection-indicator rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="ti ti-checklist text-white" style="font-size: 16px;"></i>
                    </div>
                    <div>
                        <div class="selection-text">
                            <span id="selectedCount" class="text-primary">0</span>
                            <span class="text-muted">transactions selected</span>
                        </div>
                        <div class="selection-subtext">Choose an action to apply to selected items
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12">
                <div class="d-flex flex-wrap justify-content-lg-end justify-content-center gap-2 mt-lg-0 mt-2">
                    <button onclick="bulkMarkAsPaid()" class="btn btn-success action-btn d-flex align-items-center">
                        <i class="ti ti-check me-2"></i> Mark as Paid
                    </button>
                    <button onclick="bulkExport()" class="btn btn-secondary action-btn d-flex align-items-center">
                        <i class="ti ti-download me-2"></i> Export
                    </button>
                    <button onclick="clearSelection()"
                        class="btn btn-outline-secondary action-btn d-flex align-items-center">
                        <i class="ti ti-x me-2"></i> Clear Selection
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
