<div id="bulkActionsBar" class="bulk-actions-bar border-bottom sticky-top" style="display: none; position: sticky; top: 0; z-index: 1024; background: var(--tblr-bg-surface);">
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
                            <span class="text-muted">{{ __('messages.sales_orders_selected') }}</span>
                        </div>
                        <div class="selection-subtext">{{ __('messages.choose_action_apply_selected') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12">
                <div class="d-flex flex-wrap justify-content-lg-end justify-content-center gap-2 mt-lg-0 mt-2">
                    @include('admin.layouts.partials.sales.index.bulk-action-buttons')
                </div>
            </div>
        </div>
    </div>
</div>
