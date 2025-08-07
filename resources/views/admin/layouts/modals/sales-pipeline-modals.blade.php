<div class="modal modal-blur fade" id="convertOpportunityModal" tabindex="-1" aria-labelledby="convertOpportunityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="convertOpportunityModalLabel">Convert to Sales Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="convertOpportunityForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Are you sure you want to convert this opportunity to a Sales Order?</p>
                    <input type="hidden" id="convertOpportunityId" name="opportunity_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal modal-blur fade" id="confirmationModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmationModalTitle">Confirmation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <i class="ti ti-alert-triangle text-danger" style="font-size: 3rem;"></i>
                <h3 id="confirmationModalBodyTitle">Are you sure?</h3>
                <div class="text-muted" id="confirmationModalBody">Do you really want to remove this item? This
                    action cannot be undone.</div>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col">
                            <button type="button" class="btn w-100" data-bs-dismiss="modal">
                                Cancel
                            </button>
                        </div>
                        <div class="col">
                            <button type="button" class="btn btn-danger w-100" id="confirmationModalConfirm">
                                Confirm
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>