@extends('admin.layouts.base')
@section('title', __('messages.efaktur_ppn'))

@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="{{ $containerClass ?? 'container-xl' }}">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">{{ __('messages.overview') }}</div>
                    <h2 class="page-title d-flex align-items-center">
                        <i class="ti ti-file-invoice me-2"></i>{{ __('messages.efaktur_ppn') }}
                        <a href="#" class="ms-3 text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                            <i class="ti ti-help-circle fs-3"></i>
                        </a>
                    </h2>
                    <div class="text-muted mt-1">{{ __('messages.efaktur_subtitle') }}</div>
                </div>
                <div class="col-auto ms-auto d-flex gap-2">
                    <a href="{{ route('admin.efaktur.settings') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-settings me-1"></i>{{ __('messages.efaktur_settings') }}
                    </a>
                    <a href="{{ route('admin.efaktur.export.csv', ['masa' => $masa, 'tahun' => $tahun]) }}"
                       class="btn btn-outline-success">
                        <i class="ti ti-file-download me-1"></i>{{ __('messages.efaktur_export_csv') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="{{ $containerClass ?? 'container-xl' }}">
        {{-- Session notifications --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible mb-4" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible mb-4" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- PKP warning if not configured (Indonesian tenants only) --}}
        @php $tenant = app('currentTenant'); @endphp
        @if($tenant instanceof \App\Models\Tenant && $tenant->isIndonesian() && !$tenant->pkp_status)
            <div class="alert alert-warning mb-4">
                <i class="ti ti-alert-triangle me-2"></i>
                {{ __('messages.efaktur_pkp_warning') }}
                <a href="{{ route('admin.efaktur.settings') }}">{{ __('messages.efaktur_settings') }}</a>.
            </div>
        @endif
        @if($tenant instanceof \App\Models\Tenant && $tenant->isIndonesian() && $nsfpCount === 0)
            <div class="alert alert-warning mb-4">
                <i class="ti ti-alert-triangle me-2"></i>
                {{ __('messages.efaktur_nsfp_empty_warning') }}
                <a href="{{ route('admin.efaktur.settings') }}">{{ __('messages.efaktur_settings') }}</a>.
            </div>
        @endif

        {{-- Period filter --}}
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.efaktur.index') }}" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">{{ __('messages.efaktur_tax_period') }}</label>
                        <select name="masa" class="form-select">
                            @foreach(range(1,12) as $m)
                                <option value="{{ $m }}" @selected($m == $masa)>
                                    {{ \Carbon\Carbon::create()->month($m)->format('F') }} ({{ $m }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">{{ __('messages.efaktur_tax_year') }}</label>
                        <select name="tahun" class="form-select">
                            @foreach(range(now()->year - 2, now()->year + 1) as $y)
                                <option value="{{ $y }}" @selected($y == $tahun)>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-filter me-1"></i>{{ __('messages.filter') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- SPT Masa PPN Summary cards --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-3 text-center py-3 h-100">
                    <div class="text-muted small mb-1">{{ __('messages.efaktur_total_faktur') }}</div>
                    <div class="fs-2 fw-bold align-self-center">{{ $summary['total_faktur'] }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-3 text-center py-3 h-100">
                    <div class="text-muted small mb-1">{{ __('messages.efaktur_total_dpp') }}</div>
                    <div class="fs-5 fw-bold text-primary align-self-center">
                        Rp {{ number_format($summary['total_dpp'], 0, ',', '.') }}
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-3 text-center py-3 h-100">
                    <div class="text-muted small mb-1">{{ __('messages.efaktur_total_ppn') }}</div>
                    <div class="fs-5 fw-bold text-success align-self-center">
                        Rp {{ number_format($summary['total_ppn'], 0, ',', '.') }}
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-3 text-center py-3 h-100">
                    <div class="text-muted small mb-1">{{ __('messages.efaktur_nsfp_available') }}</div>
                    <div class="fs-2 fw-bold align-self-center {{ $nsfpCount < 10 ? 'text-danger' : 'text-success' }}">
                        {{ $nsfpCount }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Faktur Pajak Table --}}
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="ti ti-list me-2 text-muted"></i>
                    {{ __('messages.efaktur_table_title') }} — {{ \Carbon\Carbon::create()->month($masa)->format('F') }} {{ $tahun }}
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-vcenter mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>NSFP</th>
                                <th>{{ __('messages.date') }}</th>
                                <th>{{ __('messages.efaktur_buyer') }}</th>
                                <th>{{ __('messages.efaktur_buyer_npwp') }}</th>
                                <th class="text-end">DPP</th>
                                <th class="text-end">PPN</th>
                                <th class="text-center">{{ __('messages.status') }}</th>
                                <th class="text-center">{{ __('messages.efaktur_action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($fakturs as $faktur)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.efaktur.show', $faktur) }}" class="fw-semibold font-monospace">
                                            {{ $faktur->nsfp }}
                                        </a>
                                    </td>
                                    <td>{{ $faktur->tanggal_faktur->format('d/m/Y') }}</td>
                                    <td>{{ $faktur->nama_pembeli }}</td>
                                    <td><code class="small">{{ $faktur->npwp_pembeli ?? '-' }}</code></td>
                                    <td class="text-end">{{ number_format($faktur->dpp, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($faktur->ppn, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $faktur->statusColor() }}">
                                            {{ $faktur->statusLabel() }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.efaktur.show', $faktur) }}"
                                               class="btn btn-outline-primary" title="{{ __('messages.efaktur_detail') }}">
                                                <i class="ti ti-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.efaktur.print', $faktur) }}"
                                               class="btn btn-outline-secondary" target="_blank" title="{{ __('messages.efaktur_print_pdf') }}">
                                                <i class="ti ti-printer"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="ti ti-file-x fs-2 d-block mb-2"></i>
                                        {{ __('messages.efaktur_empty') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($fakturs->hasPages())
                <div class="card-footer">
                    {{ $fakturs->links() }}
                </div>
            @endif
        </div>
        </div>
    </div>
</div>
@endsection
