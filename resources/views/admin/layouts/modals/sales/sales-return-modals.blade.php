<!-- Bulk Delete Sales Returns Modal -->
<div class="modal modal-blur fade" id="bulkDeleteSalesReturnModal" tabindex="-1" aria-labelledby="bulkDeleteSalesReturnModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="bulkDeleteSalesReturnModalLabel">
                    <i class="ti ti-trash me-2"></i>
                    {{ __('messages.sr_modal_bulk_delete_title') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="alert alert-warning d-flex align-items-center w-100 mb-0">
                        <i class="ti ti-alert-circle me-2 fs-4"></i>
                        <div>
                            <strong>{{ __('messages.warning') }}</strong>
                            {{ __('messages.sr_modal_bulk_delete_warning_message') }}
                        </div>
                    </div>
                </div>

                <p class="mb-3">
                    {{ __('messages.sr_modal_bulk_delete_message_part1') }}
                    <strong id="bulkDeleteCount">0</strong>
                    {{ __('messages.sr_modal_bulk_delete_message_part2') }}
                </p>

                <div class="bg-light p-3 rounded">
                    <h6 class="mb-2"><i class="ti ti-info-circle me-1"></i>
                        {{ __('messages.sr_modal_bulk_delete_what_deleted_title') }}</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li><i class="ti ti-check text-danger me-1"></i>
                            {{ __('messages.sr_modal_bulk_delete_item1') }}</li>
                        <li><i class="ti ti-check text-danger me-1"></i>
                            {{ __('messages.sr_modal_bulk_delete_item2') }}</li>
                    </ul>
                </div>

                <div class="mt-3">
                    <div class="alert alert-info d-flex align-items-start">
                        <i class="ti ti-info-circle me-2 fs-4 mt-1"></i>
                        <div>
                            <strong>{{ __('messages.sr_modal_bulk_delete_stock_impact_title') }}</strong>
                            <ul class="mb-0 mt-1 small">
                                <li><strong>{{ __('messages.sr_modal_bulk_delete_stock_impact') }}</strong>
                                    {{ __('messages.sr_modal_bulk_delete_stock_impact_desc') }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>
                    {{ __('messages.cancel') }}
                </button>
                <button type="button" class="btn btn-danger" id="confirmBulkDeleteBtn">
                    <i class="ti ti-trash me-1"></i>
                    {{ __('messages.sr_modal_bulk_delete_button') }}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Mark Completed Sales Returns Modal -->
<div class="modal modal-blur fade" id="bulkMarkCompletedSalesReturnModal" tabindex="-1" aria-labelledby="bulkMarkCompletedSalesReturnModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="bulkMarkCompletedSalesReturnModalLabel">
                    <i class="ti ti-check me-2"></i>
                    {{ __('messages.sr_modal_bulk_mark_completed_title') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">
                    {{ __('messages.sr_modal_bulk_mark_completed_message_part1') }}
                    <strong id="bulkCompletedCount">0</strong>
                    {{ __('messages.sr_modal_bulk_mark_completed_message_part2') }}
                </p>
                <div class="bg-light p-3 rounded">
                    <h6 class="mb-2"><i class="ti ti-info-circle me-1"></i>
                        {{ __('messages.sr_modal_bulk_mark_completed_what_happens_title') }}</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li><i class="ti ti-check text-success me-1"></i>
                            {{ __('messages.sr_modal_bulk_mark_completed_item1') }}</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>
                    {{ __('messages.cancel') }}
                </button>
                <button type="button" class="btn btn-success" id="confirmBulkCompletedBtn">
                    <i class="ti ti-check me-1"></i>
                    {{ __('messages.mark_as_completed') }}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Mark Canceled Sales Returns Modal -->
<div class="modal modal-blur fade" id="bulkMarkCanceledSalesReturnModal" tabindex="-1" aria-labelledby="bulkMarkCanceledSalesReturnModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="bulkMarkCanceledSalesReturnModalLabel">
                    <i class="ti ti-x me-2"></i>
                    {{ __('messages.sr_modal_bulk_mark_canceled_title') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">
                    {{ __('messages.sr_modal_bulk_mark_canceled_message_part1') }}
                    <strong id="bulkCanceledCount">0</strong>
                    {{ __('messages.sr_modal_bulk_mark_canceled_message_part2') }}
                </p>
                <div class="bg-light p-3 rounded">
                    <h6 class="mb-2"><i class="ti ti-info-circle me-1"></i>
                        {{ __('messages.sr_modal_bulk_mark_canceled_what_happens_title') }}</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li><i class="ti ti-check text-warning me-1"></i>
                            {{ __('messages.sr_modal_bulk_mark_canceled_item1') }}</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>
                    {{ __('messages.cancel') }}
                </button>
                <button type="button" class="btn btn-warning" id="confirmBulkCanceledBtn">
                    <i class="ti ti-x me-1"></i>
                    {{ __('messages.mark_as_canceled') }}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Single Delete Sales Return Modal -->
<div class="modal modal-blur fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('messages.sr_modal_delete_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <i class="ti ti-alert-triangle text-danger" style="font-size: 3rem;"></i>
                <h3>{{ __('messages.are_you_sure') }}</h3>
                <div class="text-muted">{{ __('messages.sr_modal_delete_warning') }}
                </div>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col">
                            <button type="button" class="btn w-100" data-bs-dismiss="modal">
                                {{ __('messages.cancel') }}
                            </button>
                        </div>
                        <div class="col">
                            <form id="deleteForm" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger w-100">{{ __('messages.delete') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sales Return Status Warning Modal -->
<div class="modal fade" id="srStatusWarningModal" tabindex="-1" aria-labelledby="srStatusWarningModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <button type="button" class="btn-close m-2" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body text-center py-4">
                <i class="ti ti-alert-triangle icon text-warning icon-lg mb-4"></i>
                <h3 class="mb-3">{{ __('messages.warning') }}</h3>
                <div class="text-secondary">
                    <div class="text-warning text-center" id="srStatusWarningMessage">
                        {{-- Message will be set by JavaScript --}}
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-primary w-100" data-bs-dismiss="modal">
                    {{ __('messages.close') }}
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Sales Return Detail Modal -->
<div class="modal modal-blur fade" id="salesReturnDetailModal" tabindex="-1" aria-labelledby="salesReturnDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div id="salesReturnDetailModalContent">
                <!-- Content will be loaded dynamically here -->
                <div class="modal-header">
                    <h5 class="modal-title" id="salesReturnDetailModalLabel">{{ __('messages.loading') }}...</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-center align-items-center" style="min-height: 100px;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">{{ __('messages.loading') }}...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="text-muted me-auto">
                    <small><i class="ti ti-info-circle me-1"></i>
                        {{ __('messages.sr_modal_details_info_message') }}</small>
                </div>
                <a href="#" class="btn btn-info" id="srModalFullView">
                    <i class="ti ti-zoom-scan me-1"></i> {{ __('messages.sr_full_view_button') }}
                </a>
                <button type="button" class="btn btn-secondary" id="srModalPrint">
                    <i class="ti ti-printer me-1"></i> {{ __('messages.sr_modal_details_print_button') }}
                </button>
                <a href="#" class="btn btn-primary" id="srModalEdit">
                    <i class="ti ti-edit me-1"></i> {{ __('messages.edit') }}
                </a>
            </div>
        </div>
    </div>
</div>
