{{-- ERROR MODAL --}}
@if ($errors->any())
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <button type="button" class="btn-close m-2" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-body text-center py-4">
                    <i class="ti ti-alert-triangle icon text-danger icon-lg mb-4"></i>
                    <h3 class="mb-3">Error!</h3>
                    <div class="text-secondary">
                        <div class="text-danger text-start text-center">
                            @foreach ($errors->all() as $error)
                                {{ $error }}<br>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger w-100" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- SUCCESS MODAL --}}
@if (session('success'))
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <button type="button" class="btn-close m-2" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-body text-center py-4">
                    <i class="ti ti-circle-check icon text-success icon-lg mb-4"></i>
                    <h3 class="mb-3">Success!</h3>
                    <div class="text-secondary">
                        <div class="text-success text-start text-center">
                            {{ session('success') }}
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success w-100" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
@endif
