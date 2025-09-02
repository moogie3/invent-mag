@extends('admin.layouts.base')

@section('title', 'Tax Settings')

@section('content')
    <div class="page-wrapper">
        <div class="page-wrapper">
            <div class="page-body">
                <div class="container-xl">
                    <div class="card">
                        <div class="card-body">
                            <h2><i class="ti ti-receipt-tax me-2"></i>TAX SETTINGS</h2>
                        </div>
                        <hr class="my-0">
                        <div class="row g-0">
                            <div class="col-12 col-md-3 border-end">
                                @include('admin.layouts.menu')
                            </div>
                            <div class="col-12 col-md-9 d-flex flex-column">
                                <div class="card-body">
                                    <form id="taxSettingsForm" action="{{ route('admin.setting.tax.update') }}"
                                        method="POST">
                                        @csrf

                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <label>Tax Name</label>
                                                <input type="text" name="name" class="form-control"
                                                    value="{{ optional($tax)->name ?? '' }}" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="mb-2">Activate Tax</label>
                                                <div class="form-check form-switch">
                                                    <!-- Hidden input ensures '0' is sent if unchecked -->
                                                    <input type="hidden" name="is_active" value="0">

                                                    <input class="form-check-input" type="checkbox" name="is_active"
                                                        id="taxSwitch" value="1"
                                                        {{ optional($tax)->is_active ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="taxSwitch">Enable Tax</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <label>Tax Rate (%)</label>
                                                <input type="number" name="rate" class="form-control"
                                                    value="{{ optional($tax)->rate ?? '' }}" step="0.01" required>
                                            </div>
                                        </div>
                                </div>
                                </form>
                            </div>
                            <div class="card-footer bg-transparent mt-auto">
                                <div class="btn-list justify-content-end">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                        data-bs-target="#confirmModal">Save Settings</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.layouts.modals.taxmodals')
@endsection
