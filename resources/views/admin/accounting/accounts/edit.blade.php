@extends('admin.layouts.base')

@section('title', __('messages.edit_account'))

@section('content')
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="page-header d-print-none mt-4">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        {{ __('messages.accounting') }}
                    </div>
                    <h2 class="page-title">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <i class="ti ti-edit"></i>
                        </span>
                        {{ __('messages.edit_account') }}
                    </h2>
                </div>
            </div>
        </div>

        <div class="page-body mt-4">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.accounting.accounts.update', $account) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.name') }}</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $account->name) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.code') }}</label>
                            <input type="text" name="code" class="form-control" value="{{ old('code', $account->code) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.type') }}</label>
                            <select name="type" class="form-select" required>
                                <option value="asset" @if($account->type == 'asset') selected @endif>{{ __('messages.asset') }}</option>
                                <option value="liability" @if($account->type == 'liability') selected @endif>{{ __('messages.liability') }}</option>
                                <option value="equity" @if($account->type == 'equity') selected @endif>{{ __('messages.equity') }}</option>
                                <option value="revenue" @if($account->type == 'revenue') selected @endif>{{ __('messages.revenue') }}</option>
                                <option value="expense" @if($account->type == 'expense') selected @endif>{{ __('messages.expense') }}</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.parent_account') }}</label>
                            <select name="parent_id" class="form-select">
                                <option value="">{{ __('messages.none') }}</option>
                                @foreach ($accounts as $parentAccount)
                                    <option value="{{ $parentAccount->id }}" @if($account->parent_id == $parentAccount->id) selected @endif>{{ __($parentAccount->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.description') }}</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $account->description) }}</textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" @if($account->is_active) checked @endif>
                            <label class="form-check-label">
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
