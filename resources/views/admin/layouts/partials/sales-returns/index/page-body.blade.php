<div class="page-body">
    <div class="container-xl">
        <div class="row row-deck row-cards">
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">{{ __('messages.total_returns') }}</div>
                        </div>
                        <div class="h1 mb-3">{{ $total_returns }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">{{ __('messages.total_amount') }}</div>
                        </div>
                        <div class="h1 mb-3">{{ App\Helpers\CurrencyHelper::format($total_amount) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">{{ __('messages.model_sales_return') }}</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table card-table table-vcenter text-nowrap datatable">
                        <thead>
                            <tr>
                                <th class="w-1">#</th>
                                <th>{{ __('messages.table_invoice') }}</th>
                                <th>{{ __('messages.return_date') }}</th>
                                <th>{{ __('messages.table_total') }}</th>
                                <th>{{ __('messages.status') }}</th>
                                <th>{{ __('messages.table_action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($returns as $return)
                                <tr>
                                    <td><span class="text-muted">{{ $loop->iteration }}</span></td>
                                    <td>
                                        <a href="{{ route('admin.sales-returns.show', $return) }}">
                                            {{ $return->sale->invoice }}
                                        </a>
                                    </td>
                                    <td>{{ $return->return_date->format('d-m-Y') }}</td>
                                    <td>{{ App\Helpers\CurrencyHelper::format($return->total_amount) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $return->status == 'Completed' ? 'success' : 'warning' }}-lt">
                                            {{ $return->status }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-list flex-nowrap">
                                            <a href="{{ route('admin.sales-returns.show', $return) }}" class="btn">
                                                {{ __('messages.view') }}
                                            </a>
                                            <a href="{{ route('admin.sales-returns.edit', $return) }}" class="btn">
                                                {{ __('messages.edit') }}
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">
                                        {{ __('messages.no_sales_returns_found') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex align-items-center">
                    {{ $returns->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
