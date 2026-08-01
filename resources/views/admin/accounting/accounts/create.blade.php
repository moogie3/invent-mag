@extends('admin.layouts.base')

@section('title', __('messages.add_account'))

@section('content')
    <div class="{{ $containerClass ?? "container-xl" }}">
<div class="page-header d-print-none mt-4">
    <div class="row g-2 align-items-center">
        <div class="col">
            <div class="page-pretitle">
                {{ __('messages.accounting') }}
            </div>
            <h2 class="page-title d-flex align-items-center">
                <i class="ti ti-plus me-2"></i>{{ __('messages.add_account') }}
                <a href="#" class="ms-3 text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                    <i class="ti ti-help-circle fs-3"></i>
                </a>
            </h2>
        </div>
    </div>
</div>

        <div class="page-body mt-4">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.accounting.accounts.store') }}" method="POST" novalidate data-validate-form>
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="name">{{ __('messages.name') }}</label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="code">{{ __('messages.code') }}</label>
                            <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" required>
                            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="type">{{ __('messages.type') }}</label>
                            <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                                <option value="asset" {{ old('type') == 'asset' ? 'selected' : '' }}>{{ __('messages.asset') }}</option>
                                <option value="liability" {{ old('type') == 'liability' ? 'selected' : '' }}>{{ __('messages.liability') }}</option>
                                <option value="equity" {{ old('type') == 'equity' ? 'selected' : '' }}>{{ __('messages.equity') }}</option>
                                <option value="revenue" {{ old('type') == 'revenue' ? 'selected' : '' }}>{{ __('messages.revenue') }}</option>
                                <option value="expense" {{ old('type') == 'expense' ? 'selected' : '' }}>{{ __('messages.expense') }}</option>
                            </select>
                            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="parent_id">{{ __('messages.parent_account') }}</label>
                            <select name="parent_id" id="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                                <option value="">{{ __('messages.none') }}</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}" {{ old('parent_id') == $account->id ? 'selected' : '' }}>{{ __($account->name) }}</option>
                                @endforeach
                            </select>
                            @error('parent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="description">{{ __('messages.description') }}</label>
                            <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                {{ __('messages.active') }}
                            </label>
                        </div>
                        <div class="d-flex">
                            <a href="{{ route('admin.accounting.accounts.index') }}" class="btn btn-secondary me-auto">{{ __('messages.cancel') }}</a>
                            <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
