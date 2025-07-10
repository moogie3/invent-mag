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
                            <span class="text-muted">products selected</span>
                        </div>
                        <div class="selection-subtext">Choose an action to apply to selected items</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12">
                <div class="d-flex flex-wrap justify-content-lg-end justify-content-center gap-2 mt-lg-0 mt-2">
                    <button onclick="bulkUpdateStock()" class="btn btn-info action-btn d-flex align-items-center">
                        <i class="ti ti-packages me-2"></i> Update Stock
                    </button>
                    <button onclick="bulkExportProducts()"
                        class="btn btn-secondary action-btn d-flex align-items-center">
                        <i class="ti ti-download me-2"></i> Export
                    </button>
                    <button onclick="bulkDeleteProducts()" class="btn btn-danger action-btn d-flex align-items-center">
                        <i class="ti ti-trash me-2"></i> Delete
                    </button>
                    <button onclick="clearProductSelection()"
                        class="btn btn-outline-secondary action-btn d-flex align-items-center">
                        <i class="ti ti-x me-2"></i> Clear Selection
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
