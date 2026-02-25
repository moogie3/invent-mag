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
                            <span class="text-muted">{{ __('messages.purchase_returns_selected') }}</span>
                        </div>
                        <div class="selection-subtext">{{ __('messages.choose_action_apply_selected') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12">
                <div class="d-flex flex-wrap justify-content-lg-end justify-content-center gap-2 mt-lg-0 mt-2">
                    <button onclick="bulkMarkCompletedPurchaseReturns()" class="btn btn-success action-btn d-flex align-items-center">
                        <i class="ti ti-checks me-2"></i> {{ __('messages.mark_as_completed') }}
                    </button>
                    <button onclick="bulkMarkCanceledPurchaseReturns()" class="btn btn-warning action-btn d-flex align-items-center">
                        <i class="ti ti-x me-2"></i> {{ __('messages.mark_as_canceled') }}
                    </button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ti ti-download me-2"></i> {{ __('messages.export') }}
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="bulkExportPurchaseReturns('csv')">Export as CSV</a></li>
                            <li><a class="dropdown-item" href="#" onclick="bulkExportPurchaseReturns('pdf')">Export as PDF</a></li>
                        </ul>
                    </div>
                    <button onclick="bulkDeletePurchaseReturns()" class="btn btn-danger action-btn d-flex align-items-center">
                        <i class="ti ti-trash me-2"></i> {{ __('messages.delete') }}
                    </button>
                    <button onclick="clearPurchaseReturnSelection()"
                        class="btn btn-outline-secondary action-btn d-flex align-items-center">
                        <i class="ti ti-x me-2"></i> {{ __('messages.clear_selection') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
