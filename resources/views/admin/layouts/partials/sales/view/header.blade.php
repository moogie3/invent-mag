<div class="page-header no-print">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">
                    <a href="{{ route('admin.sales') }}" class="text-muted text-decoration-none">
                        <i class="ti ti-arrow-left"></i> {{ __('messages.back') }}
                    </a>
                </div>
                <h2 class="page-title no-print fw-bold d-flex align-items-center">
                    <i class="ti ti-eye me-2"></i>{{ __('messages.view_sales_invoice') }}
                    <a href="#" class="ms-3 text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                        <i class="ti ti-help-circle fs-3"></i>
                    </a>
                </h2>
            </div>
            <div class="col-auto">
                <a href="{{ route('admin.sales.print', $sales->id) }}" target="_blank" class="btn btn-secondary me-2">
                    <i class="ti ti-printer me-1"></i> {{ __('messages.print_invoice') }}
                </a>
                @can('edit-sales')
                    <form method="POST" action="{{ route('admin.sales.send-invoice', $sales->id) }}"
                          class="d-inline me-2" data-invoice-email-send>
                        @csrf
                        <button type="submit" class="btn btn-outline-primary" data-bs-toggle="tooltip"
                            title="{{ $sales->customer?->email ? __('messages.invoice_email_confirm', ['email' => $sales->customer->email]) : __('messages.invoice_email_missing') }}">
                            <i class="ti ti-mail me-1"></i> {{ __('messages.send_invoice_email') }}
                        </button>
                    </form>
                    {{-- data-invoice-email-send is what admin/sales/invoice-email.js binds to:
                         the button disables and shows a spinner for the flight of the POST,
                         so a slow SMTP round trip reads as work in progress instead of a
                         dead control. --}}
                @endcan
                <a href="{{ route('admin.sales.edit', $sales->id) }}" class="btn btn-primary">
                    <i class="ti ti-edit me-1"></i> {{ __('messages.edit_invoice') }}
                </a>
                @php
                    $currentTenant = app('currentTenant');
                    $showFakturPajakButton = $currentTenant instanceof \App\Models\Tenant && $currentTenant->isIndonesian() && $currentTenant->hasFeature('ppn');
                @endphp
                @if($showFakturPajakButton)
                @if(!$sales->fakturPajak)
                    <form method="POST" action="{{ route('admin.efaktur.generate', $sales) }}"
                          class="d-inline ms-2"
                          onsubmit="return confirm('Buat Faktur Pajak untuk invoice {{ $sales->invoice }}?')">
                        @csrf
                        <button type="submit" class="btn btn-outline-warning"
                            @disabled(!$sales->ppn_enabled)
                            title="{{ !$sales->ppn_enabled ? 'Aktifkan PPN pada invoice ini terlebih dahulu' : 'Buat Faktur Pajak' }}">
                            <i class="ti ti-file-invoice me-1"></i>Buat Faktur Pajak
                        </button>
                    </form>
                @else
                    <a href="{{ route('admin.efaktur.show', $sales->fakturPajak) }}"
                       class="btn btn-outline-success ms-2">
                        <i class="ti ti-file-check me-1"></i>Lihat Faktur Pajak
                    </a>
                @endif
                @endif
            </div>
        </div>
        @if($sales->customer_credit_used > 0)
        <div class="row mt-3">
            <div class="col-12">
                <div class="alert alert-info mb-0">
                    <i class="ti ti-credit-card me-2"></i>
                    {{ __('messages.credit_applied') }}: {{ \App\Helpers\CurrencyHelper::format($sales->customer_credit_used) }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
