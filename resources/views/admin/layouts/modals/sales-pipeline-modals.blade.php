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