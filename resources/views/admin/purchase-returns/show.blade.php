@extends('admin.layouts.base')

@section('title', __('messages.purchase_return_details'))

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        {{ __('messages.purchase_return_details') }}
                    </h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        <a href="{{ route('admin.purchase-returns.edit', $purchaseReturn) }}"
                            class="btn btn-primary d-none d-sm-inline-block">
                            <i class="ti ti-edit me-2"></i>
                            {{ __('messages.edit_purchase_return') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('messages.return_hash') }}{{ $purchaseReturn->id }}</h3>
                </div>
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">{{ __('messages.original_purchase') }}</div>
                            <div class="datagrid-content">
                                <a
                                    href="{{ route('admin.po.view', $purchaseReturn->purchase) }}">{{ $purchaseReturn->purchase->invoice }}</a>
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">{{ __('messages.returned_by') }}</div>
                            <div class="datagrid-content">{{ $purchaseReturn->user->name }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">{{ __('messages.return_date') }}</div>
                            <div class="datagrid-content">{{ $purchaseReturn->return_date->format('d M Y') }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">{{ __('messages.status') }}</div>
                            <div class="datagrid-content">{{ $purchaseReturn->status }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">{{ __('messages.total_return_amount') }}</div>
                            <div class="datagrid-content">
                                {{ App\Helpers\CurrencyHelper::format($purchaseReturn->total_amount) }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">{{ __('messages.reason') }}</div>
                            <div class="datagrid-content">{{ $purchaseReturn->reason }}</div>
                        </div>
                    </div>

                    <h3 class="card-title mt-4">{{ __('messages.returned_items') }}</h3>
                    <div class="table-responsive">
                        <table class="table card-table table-vcenter text-nowrap">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.product') }}</th>
                                    <th>{{ __('messages.quantity') }}</th>
                                    <th>{{ __('messages.price') }}</th>
                                    <th>{{ __('messages.total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($purchaseReturn->items as $item)
                                    <tr>
                                        <td>{{ $item->product->name }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ App\Helpers\CurrencyHelper::format($item->price) }}</td>
                                        <td>{{ App\Helpers\CurrencyHelper::format($item->total) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
