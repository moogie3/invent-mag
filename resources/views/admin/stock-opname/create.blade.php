@extends('admin.layouts.base')

@section('title', __('messages.plan_stock_opname'))

@section('content')
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="{{ ($containerClass ?? 'container-xl') === 'container-xl' ? 'container-fluid' : $containerClass }}" 
                 style="{{ ($containerClass ?? 'container-xl') === 'container-xl' ? 'max-width: 90%;' : '' }}">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            <a href="{{ route('admin.stock-opname.index') }}" class="text-muted text-decoration-none">
                                <i class="ti ti-arrow-left"></i> {{ __('messages.back') }}
                            </a>
                        </div>
                        <h2 class="page-title d-flex align-items-center">
                            <i class="ti ti-plus me-2"></i> {{ __('messages.plan_stock_opname') }}
                            <a href="#" class="ms-3 text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                                <i class="ti ti-help-circle fs-3"></i>
                            </a>
                        </h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="page-body">
            <div class="{{ ($containerClass ?? 'container-xl') === 'container-xl' ? 'container-fluid' : $containerClass }}" 
                 style="{{ ($containerClass ?? 'container-xl') === 'container-xl' ? 'max-width: 90%;' : '' }}">
                <div class="row row-cards">
                    <div class="col-md-8 mx-auto">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('messages.stock_opname_details') }}</h3>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('admin.stock-opname.store') }}" method="POST" novalidate data-validate-form>
                                    @csrf
                                    
                                    <div class="mb-3">
                                        <label class="form-label required">{{ __('messages.target_warehouse') }}</label>
                                        <select class="form-select @error('warehouse_id') is-invalid @enderror" name="warehouse_id" required>
                                            <option value="">{{ __('messages.select_warehouse') }}</option>
                                            @foreach($warehouses as $warehouse)
                                                <option value="{{ $warehouse->id }}" {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                                    {{ $warehouse->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('warehouse_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-hint mt-2 text-primary">
                                            <i class="ti ti-info-circle me-1"></i> {{ __('messages.stock_opname_snapshot_hint') }}
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label required">{{ __('messages.scheduled_date') }}</label>
                                        <input type="date" class="form-control @error('scheduled_date') is-invalid @enderror" name="scheduled_date" value="{{ old('scheduled_date', date('Y-m-d')) }}" required>
                                        @error('scheduled_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">{{ __('messages.notes_instructions') ?? __('messages.notes') }}</label>
                                        <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="4">{{ old('notes') }}</textarea>
                                        @error('notes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-footer text-end mt-4">
                                        <a href="{{ route('admin.stock-opname.index') }}" class="btn btn-secondary">{{ __('messages.cancel') }}</a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ti ti-device-floppy me-2"></i> {{ __('messages.save_generate_count_sheet') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection