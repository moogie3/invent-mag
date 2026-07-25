@extends('admin.layouts.base')
@section('title', __('messages.efaktur_detail_title'))

@section('content')
<div class="{{ $containerClass ?? 'container-xl' }}">
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">
                    <a href="{{ route('admin.efaktur.index') }}" class="text-muted text-decoration-none">
                        <i class="ti ti-arrow-left"></i> {{ __('messages.back') }}
                    </a>
                </div>
                <h2 class="page-title fw-bold d-flex align-items-center">
                    <i class="ti ti-file-invoice me-2"></i>{{ __('messages.efaktur_detail_title') }}
                    <a href="#" class="ms-3 text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                        <i class="ti ti-help-circle fs-3"></i>
                    </a>
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a href="{{ route('admin.efaktur.print', $fakturPajak) }}" target="_blank"
                   class="btn btn-outline-secondary me-2">
                    <i class="ti ti-printer me-1"></i>{{ __('messages.efaktur_print_pdf') }}
                </a>
                @if($fakturPajak->isSubmittable())
                    <form method="POST" action="{{ route('admin.efaktur.cancel', $fakturPajak) }}"
                          class="d-inline"
                          onsubmit="return confirm('{{ __('messages.efaktur_confirm_cancel') }}')">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="ti ti-x me-1"></i>{{ __('messages.cancel') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="page-body mt-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible mb-3">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible mb-3">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row g-4">
            {{-- Left: Faktur details --}}
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h3 class="card-title mb-0">{{ __('messages.efaktur_faktur_info') }}</h3>
                        <span class="badge bg-{{ $fakturPajak->statusColor() }} fs-6">
                            {{ $fakturPajak->statusLabel() }}
                        </span>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-5">NSFP</dt>
                            <dd class="col-7"><code class="fs-6">{{ $fakturPajak->nsfp }}</code></dd>

                            <dt class="col-5">{{ __('messages.efaktur_faktur_date') }}</dt>
                            <dd class="col-7">{{ $fakturPajak->tanggal_faktur->format('d F Y') }}</dd>

                            <dt class="col-5">{{ __('messages.efaktur_tax_period_label') }}</dt>
                            <dd class="col-7">
                                {{ \Carbon\Carbon::create()->month($fakturPajak->masa_pajak)->format('F') }}
                                {{ $fakturPajak->tahun_pajak }}
                            </dd>

                            <dt class="col-5">{{ __('messages.efaktur_reference_invoice') }}</dt>
                            <dd class="col-7">
                                <a href="{{ route('admin.sales.show', $fakturPajak->sale?->id) }}">
                                    {{ $fakturPajak->sale?->invoice ?? '-' }}
                                </a>
                            </dd>

                            <dt class="col-5">{{ __('messages.efaktur_ppn_rate') }}</dt>
                            <dd class="col-7">{{ $fakturPajak->ppn_rate }}%</dd>
                        </dl>
                    </div>
                </div>

                {{-- Penjual --}}
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header">
                        <h3 class="card-title mb-0"><i class="ti ti-building me-2 text-muted"></i>{{ __('messages.efaktur_seller') }}</h3>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-4">{{ __('messages.efaktur_npwp') }}</dt>
                            <dd class="col-8"><code>{{ $fakturPajak->npwp_penjual ?: '-' }}</code></dd>
                            <dt class="col-4">{{ __('messages.efaktur_name') }}</dt>
                            <dd class="col-8">{{ $fakturPajak->nama_penjual }}</dd>
                            <dt class="col-4">{{ __('messages.efaktur_address') }}</dt>
                            <dd class="col-8">{{ $fakturPajak->alamat_penjual ?: '-' }}</dd>
                        </dl>
                    </div>
                </div>

                {{-- Pembeli --}}
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header">
                        <h3 class="card-title mb-0"><i class="ti ti-user me-2 text-muted"></i>{{ __('messages.efaktur_buyer_label') }}</h3>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-4">{{ __('messages.efaktur_npwp') }}</dt>
                            <dd class="col-8">
                                <code>{{ $fakturPajak->npwp_pembeli ?: '00.000.000.0-000.000' }}</code>
                                @if(!$fakturPajak->npwp_pembeli || $fakturPajak->npwp_pembeli === '00.000.000.0-000.000')
                                    <span class="badge bg-secondary ms-1">{{ __('messages.efaktur_non_pkp') }}</span>
                                @endif
                            </dd>
                            <dt class="col-4">{{ __('messages.efaktur_name') }}</dt>
                            <dd class="col-8">{{ $fakturPajak->nama_pembeli }}</dd>
                            <dt class="col-4">{{ __('messages.efaktur_address') }}</dt>
                            <dd class="col-8">{{ $fakturPajak->alamat_pembeli ?: '-' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            {{-- Right: Tax summary + items --}}
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header">
                        <h3 class="card-title mb-0">{{ __('messages.efaktur_tax_summary') }}</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted">{{ __('messages.efaktur_dpp') }}</td>
                                    <td class="text-end fw-semibold">
                                        Rp {{ number_format($fakturPajak->dpp, 0, ',', '.') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">{{ __('messages.efaktur_ppn_label') }} ({{ $fakturPajak->ppn_rate }}%)</td>
                                    <td class="text-end fw-semibold text-danger">
                                        Rp {{ number_format($fakturPajak->ppn, 0, ',', '.') }}
                                    </td>
                                </tr>
                                @if($fakturPajak->ppnbm > 0)
                                <tr>
                                    <td class="text-muted">{{ __('messages.efaktur_ppnbm') }}</td>
                                    <td class="text-end fw-semibold">
                                        Rp {{ number_format($fakturPajak->ppnbm, 0, ',', '.') }}
                                    </td>
                                </tr>
                                @endif
                                <tr class="table-active">
                                    <td class="fw-bold">{{ __('messages.efaktur_total_billing') }}</td>
                                    <td class="text-end fw-bold fs-5">
                                        Rp {{ number_format($fakturPajak->totalTagihan(), 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- DJP tracking if submitted --}}
                @if($fakturPajak->djp_submitted_at)
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header">
                        <h3 class="card-title mb-0"><i class="ti ti-cloud-upload me-2 text-muted"></i>{{ __('messages.efaktur_djp_status') }}</h3>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-6">{{ __('messages.efaktur_submitted') }}</dt>
                            <dd class="col-6">{{ $fakturPajak->djp_submitted_at->format('d/m/Y H:i') }}</dd>
                            @if($fakturPajak->djp_reference_number)
                                <dt class="col-6">{{ __('messages.efaktur_djp_reference') }}</dt>
                                <dd class="col-6"><code>{{ $fakturPajak->djp_reference_number }}</code></dd>
                            @endif
                        </dl>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Line Items --}}
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="ti ti-list-details me-2 text-muted"></i>{{ __('messages.efaktur_items_title') }}</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-vcenter mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>{{ __('messages.efaktur_item_name') }}</th>
                            <th class="text-center">{{ __('messages.efaktur_item_code') }}</th>
                            <th class="text-end">{{ __('messages.efaktur_unit_price') }}</th>
                            <th class="text-end">{{ __('messages.efaktur_quantity') }}</th>
                            <th class="text-end">{{ __('messages.efaktur_discount') }}</th>
                            <th class="text-end">DPP</th>
                            <th class="text-end">PPN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fakturPajak->items as $i => $item)
                        <tr>
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td>{{ $item->nama_barang }}</td>
                            <td class="text-center"><code class="small">{{ $item->kode_objek_pajak }}</code></td>
                            <td class="text-end">{{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                            <td class="text-end">{{ $item->jumlah_barang }}</td>
                            <td class="text-end">{{ number_format($item->diskon, 0, ',', '.') }}</td>
                            <td class="text-end fw-semibold">{{ number_format($item->dpp, 0, ',', '.') }}</td>
                            <td class="text-end fw-semibold text-danger">{{ number_format($item->ppn, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-active fw-bold">
                        <tr>
                            <td colspan="6" class="text-end">{{ __('messages.total') }}</td>
                            <td class="text-end">{{ number_format($fakturPajak->dpp, 0, ',', '.') }}</td>
                            <td class="text-end text-danger">{{ number_format($fakturPajak->ppn, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
