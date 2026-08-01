@extends('admin.layouts.base')
@section('title', __('messages.efaktur_settings_title'))

@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="{{ $containerClass ?? 'container-xl' }}">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        <a href="{{ route('admin.efaktur.index') }}" class="text-muted text-decoration-none">
                            <i class="ti ti-arrow-left"></i> {{ __('messages.back') }}
                        </a>
                    </div>
                    <h2 class="page-title fw-bold d-flex align-items-center">
                        <i class="ti ti-settings me-2"></i>{{ __('messages.efaktur_settings_title') }}
                        <a href="#" class="ms-3 text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                            <i class="ti ti-help-circle fs-3"></i>
                        </a>
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="{{ $containerClass ?? 'container-xl' }}">
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
            {{-- PKP / NPWP Settings --}}
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            <i class="ti ti-building-community me-2 text-muted"></i>
                            {{ __('messages.efaktur_pkp_data') }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.efaktur.settings.save') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="taxJurisdictionSelect">{{ __('messages.tax_jurisdiction') }}</label>
                                <select name="tax_jurisdiction" class="form-select @error('tax_jurisdiction') is-invalid @enderror" id="taxJurisdictionSelect">
                                    <option value="">{{ __('messages.tax_jurisdiction_none') }}</option>
                                    <option value="ID" {{ old('tax_jurisdiction', $tenant->tax_jurisdiction ?? '') === 'ID' ? 'selected' : '' }}>
                                        {{ __('messages.tax_jurisdiction_id') }}
                                    </option>
                                </select>
                                @error('tax_jurisdiction')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">{!! __('messages.tax_jurisdiction_hint') !!}</div>
                            </div>

                            <div id="indonesianTaxFields" style="{{ ($tenant->tax_jurisdiction ?? '') === 'ID' ? '' : 'display:none' }}">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold" for="npwp">{{ __('messages.efaktur_company_npwp') }}</label>
                                    <input type="text" name="npwp" id="npwp" class="form-control font-monospace @error('npwp') is-invalid @enderror"
                                        value="{{ old('npwp', $tenant->npwp) }}"
                                        placeholder="XX.XXX.XXX.X-XXX.XXX"
                                        maxlength="20">
                                    @error('npwp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <div class="form-text">{!! __('messages.efaktur_npwp_format_hint') !!}</div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="pkp_status"
                                            id="pkp_status" value="1"
                                            {{ old('pkp_status', $tenant->pkp_status) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="pkp_status">
                                            {{ __('messages.efaktur_pkp_registered') }}
                                        </label>
                                    </div>
                                    <div class="form-text">{{ __('messages.efaktur_pkp_registered_hint') }}</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold" for="pkp_date">{{ __('messages.efaktur_pkp_date') }}</label>
                                    <input type="date" name="pkp_date" id="pkp_date" class="form-control @error('pkp_date') is-invalid @enderror"
                                        value="{{ old('pkp_date', $tenant->pkp_date?->format('Y-m-d')) }}">
                                    @error('pkp_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold" for="ppn_rate">{{ __('messages.efaktur_ppn_rate_label') }}</label>
                                    <input type="number" name="ppn_rate" id="ppn_rate" class="form-control @error('ppn_rate') is-invalid @enderror"
                                        value="{{ old('ppn_rate', $tenant->ppn_rate ?? 11.00) }}"
                                        step="0.01" min="0" max="100">
                                    @error('ppn_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <div class="form-text">{{ __('messages.efaktur_ppn_rate_hint') }}</div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-1"></i>{{ __('messages.efaktur_save_settings') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- NSFP Import + Status (Indonesian tax jurisdiction only) --}}
            <div class="col-lg-6" id="nsfpSection" style="{{ ($tenant->tax_jurisdiction ?? '') === 'ID' ? '' : 'display:none' }}">
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            <i class="ti ti-numbers me-2 text-muted"></i>
                            {{ __('messages.efaktur_nsfp_pool') }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <div class="text-center p-3 bg-success-lt rounded-2">
                                    <div class="fs-1 fw-bold text-success">{{ $nsfpCount }}</div>
                                    <div class="text-muted small">{{ __('messages.efaktur_available') }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-3 bg-secondary-lt rounded-2">
                                    <div class="fs-1 fw-bold text-secondary">{{ $nsfpUsed }}</div>
                                    <div class="text-muted small">{{ __('messages.efaktur_used') }}</div>
                                </div>
                            </div>
                        </div>

                        @if($nsfpCount < 10)
                            <div class="alert alert-warning py-2 mb-3">
                                <i class="ti ti-alert-triangle me-1"></i>
                                {{ __('messages.efaktur_nsfp_low_stock') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('admin.efaktur.nsfp.import') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="nsfp_list">{{ __('messages.efaktur_import_nsfp') }}</label>
                                <textarea name="nsfp_list" id="nsfp_list" class="form-control font-monospace @error('nsfp_list') is-invalid @enderror"
                                    rows="8"
                                    placeholder="{{ __('messages.efaktur_nsfp_placeholder') }}&#10;010.000-26.00000001&#10;010.000-26.00000002&#10;010.000-26.00000003"></textarea>
                                @error('nsfp_list')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">
                                    {!! __('messages.efaktur_nsfp_format_hint') !!}
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="ti ti-upload me-1"></i>{{ __('messages.efaktur_import_nsfp') }}
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Phase 2 notice --}}
                <div class="card border-0 shadow-sm rounded-3 border-start border-warning border-3">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-start gap-3">
                            <i class="ti ti-clock fs-3 text-warning"></i>
                            <div>
                                <div class="fw-semibold">{{ __('messages.efaktur_klikpajak_coming_soon') }}</div>
                                <div class="text-muted small">
                                    {{ __('messages.efaktur_klikpajak_desc') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
</div>
@endsection

<script>
document.getElementById('taxJurisdictionSelect')?.addEventListener('change', function() {
    const isIndonesian = this.value === 'ID';
    document.getElementById('indonesianTaxFields').style.display = isIndonesian ? '' : 'none';
    document.getElementById('nsfpSection').style.display = isIndonesian ? '' : 'none';
});
</script>
