<div class="page-header no-print">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Overview</div>
                <h2 class="page-title no-print">View PO Invoice</h2>
            </div>
            <div class="col text-end">
                <button type="button" class="btn btn-secondary me-2" onclick="javascript:window.print();">
                    <i class="ti ti-printer me-1"></i> Print Invoice
                </button>
                <a href="{{ route('admin.po.edit', $pos->id) }}" class="btn btn-primary">
                    <i class="ti ti-edit me-1"></i> Edit Invoice
                </a>
            </div>
        </div>
    </div>
</div>
