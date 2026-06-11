<div class="page-body">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.quotations.update', $quotation->id) }}" id="quotationForm">
                            @csrf
                            @method('PUT')
                            @include('admin.quotations.partials.form.quotation-info')
                            @include('admin.quotations.partials.form.quotation-items')
                            @include('admin.quotations.partials.form.quotation-summary')
                            @include('admin.quotations.partials.form.form-actions')
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
