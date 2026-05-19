<div id="bulkActionsBar" class="bulk-actions-bar border-bottom" style="display: none; position: sticky; top: 0; z-index: 1030; background: var(--tblr-bg-surface);">
    <div class="px-4 py-3">
        <div class="row align-items-center">
            <div class="col-lg-6 col-md-12">
                <div class="d-flex align-items-center">
                    <div
                        class="selection-indicator rounded-circle d-flex align-items-center justify-content-center me-3"
                        style="background: var(--tblr-primary); width: 32px; height: 32px;">
                        <i class="ti ti-checklist" style="font-size: 16px; color: white;"></i>
                    </div>
                    <div>
                        <div class="selection-text">
                            <span id="selectedCount" class="fw-bold" style="color: var(--tblr-primary);">0</span>
                            <span class="text-muted">{{ __('messages.products_selected') }}</span>
                        </div>
                        <div class="selection-subtext">{{ __('messages.choose_action_apply_selected') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12">
                <div class="d-flex flex-wrap justify-content-lg-end justify-content-center gap-2 mt-lg-0 mt-2">
                    <button data-onclick="bulkUpdateStock()" class="btn btn-info action-btn d-flex align-items-center">
                        <i class="ti ti-packages me-2"></i> {{ __('messages.update_stock') }}
                    </button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="window" aria-expanded="false">
                            <i class="ti ti-download me-2"></i> {{ __('messages.export') }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" data-onclick="bulkExportProducts('pdf')">Export as PDF</a></li>
                            <li><a class="dropdown-item" href="#" data-onclick="bulkExportProducts('csv')">Export as CSV</a></li>
                        </ul>
                    </div>
                    <button data-onclick="bulkDeleteProducts()" class="btn btn-danger action-btn d-flex align-items-center">
                        <i class="ti ti-trash me-2"></i> {{ __('messages.delete') }}
                    </button>
                    <button data-onclick="clearProductSelection()"
                        class="btn btn-outline-secondary action-btn d-flex align-items-center">
                        <i class="ti ti-x me-2"></i> {{ __('messages.clear_selection') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
