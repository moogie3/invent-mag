<div class="page-body">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <div class="row row-cards">
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-3">
                    @include('admin.layouts.partials.sales-returns.index.store-info')
                    @include('admin.layouts.partials.sales-returns.index.bulk-actions')
                    <div id="invoiceTableContainer" class="position-relative card-body border-top py-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead style="font-size: large">
                                    <tr>
                                        <th class="sticky-top bg-white fs-4 py-3" style="z-index: 1020; width: 40px;">
                                            <input type="checkbox" id="selectAll" class="form-check-input">
                                        </th>
                                        <th class="sticky-top bg-white fs-4 py-3" style="z-index: 1020; width: 60px;">
                                            {{ __('messages.table_no') }}
                                        </th>
                                        <th class="sticky-top bg-white fs-4 py-3" style="z-index: 1020;">
                                            {{ __('messages.table_invoice') }}
                                        </th>
                                        <th class="sticky-top bg-white fs-4 py-3" style="z-index: 1020;">
                                            {{ __('messages.return_date') }}
                                        </th>
                                        <th class="sticky-top bg-white fs-4 py-3" style="z-index: 1020;">
                                            {{ __('messages.table_total') }}
                                        </th>
                                        <th class="sticky-top bg-white fs-4 py-3 text-center" style="z-index: 1020;">
                                            {{ __('messages.status') }}
                                        </th>
                                        <th class="sticky-top bg-white fs-4 py-3 no-print" style="z-index: 1020; width: 120px;">
                                            {{ __('messages.table_action') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="invoiceTableBody">
                                    @forelse ($returns as $return)
                                        <tr>
                                            <td class="text-center py-3">
                                                <input type="checkbox" name="selected_invoices[]" value="{{ $return->id }}"
                                                    class="form-check-input row-checkbox">
                                            </td>
                                            <td class="text-center py-3">
                                                <span class="text-muted">{{ $loop->iteration }}</span>
                                            </td>
                                            <td class="py-3">
                                                <a href="{{ route('admin.sales-returns.show', $return) }}" class="fw-bold text-primary">
                                                    {{ $return->sale->invoice }}
                                                </a>
                                            </td>
                                            <td class="py-3">{{ $return->return_date->format('d F Y') }}</td>
                                            <td class="fw-bold py-3">{{ App\Helpers\CurrencyHelper::format($return->total_amount) }}</td>
                                            <td class="text-center py-3">
                                                @php
                                                    $statusClass = match($return->status) {
                                                        'Completed' => 'bg-success-lt text-success',
                                                        'Pending' => 'bg-warning-lt text-warning',
                                                        'Canceled' => 'bg-danger-lt text-danger',
                                                        default => 'bg-secondary-lt text-secondary'
                                                    };
                                                @endphp
                                                <span class="badge {{ $statusClass }}">
                                                    {{ $return->status }}
                                                </span>
                                            </td>
                                            <td class="text-center py-3">
                                                <div class="btn-list flex-nowrap justify-content-center">
                                                    <button type="button" class="btn btn-icon btn-ghost-secondary view-sales-return-btn"
                                                        data-bs-toggle="modal" data-bs-target="#salesReturnDetailModal"
                                                        data-sr-id="{{ $return->id }}"
                                                        title="{{ __('messages.view') }}">
                                                        <i class="ti ti-eye"></i>
                                                    </button>
                                                    <a href="{{ route('admin.sales-returns.edit', $return) }}"
                                                        class="btn btn-icon btn-ghost-primary"
                                                        title="{{ __('messages.edit') }}">
                                                        <i class="ti ti-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-icon btn-ghost-danger"
                                                        data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                        title="{{ __('messages.delete') }}"
                                                        onclick="setDeleteFormAction('{{ route('admin.sales-returns.destroy', $return->id) }}')">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-5">
                                                <div class="empty-state">
                                                    <i class="ti ti-receipt-refund fs-1 text-muted mb-3 d-block"></i>
                                                    <h4 class="text-muted">{{ __('messages.no_sales_returns_found') }}</h4>
                                                    <p class="text-muted small">{{ __('messages.create_your_first_sales_return') }}</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer d-flex align-items-center border-top py-3">
                            <p class="m-0 text-secondary">
                                {{ __('messages.pagination_showing_entries', ['first' => $returns->firstItem(), 'last' => $returns->lastItem(), 'total' => $returns->total()]) }}
                            </p>
                            <div class="ms-auto">
                                {{ $returns->appends(request()->query())->links('vendor.pagination.tabler') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
