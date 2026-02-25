<div class="page-body">
    <div class="container-xl">
        <div class="row row-deck row-cards">
            <div class="col-md-12">
                <div class="card card-primary">
                    @include('admin.layouts.partials.por.index.store-info')
                    @include('admin.layouts.partials.por.index.bulk-actions')
                    <div id="invoiceTableContainer" class="position-relative">
                        <div class="table-responsive">
                            <table class="table card-table table-vcenter">
                                <thead style="font-size: large">
                                    <tr>
                                        <th class="sticky-top" style="z-index: 1020;">
                                            <input type="checkbox" id="selectAll" class="form-check-input">
                                        </th>
                                        <th class="sticky-top" style="z-index: 1020;">
                                            <button class="table-sort fs-4 py-3" data-sort="sort-no">
                                                {{ __('messages.no') }}
                                            </button>
                                        </th>
                                        <th class="sticky-top" style="z-index: 1020;">
                                            <button class="table-sort fs-4 py-3">
                                                {{ __('messages.table_invoice') }}
                                            </button>
                                        </th>
                                        <th class="sticky-top" style="z-index: 1020;">
                                            <button class="table-sort fs-4 py-3">
                                                {{ __('messages.return_date') }}
                                            </button>
                                        </th>
                                        <th class="sticky-top" style="z-index: 1020;">
                                            <button class="table-sort fs-4 py-3">
                                                {{ __('messages.table_total') }}
                                            </button>
                                        </th>
                                        <th class="sticky-top" style="z-index: 1020;">
                                            <button class="table-sort fs-4 py-3">
                                                {{ __('messages.status') }}
                                            </button>
                                        </th>
                                        <th class="sticky-top fs-4 py-3 no-print"
                                            style="width: 100px; text-align: center; z-index: 1020;">
                                            {{ __('messages.table_action') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($returns as $return)
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="selected_invoices[]" value="{{ $return->id }}"
                                                    class="form-check-input row-checkbox">
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ $loop->iteration }}</span>
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.por.show', $return) }}">
                                                    {{ $return->purchase->invoice }}
                                                </a>
                                            </td>
                                            <td>{{ $return->return_date->format('d-m-Y') }}</td>
                                            <td>{{ App\Helpers\CurrencyHelper::format($return->total_amount) }}</td>
                                            <td>
                                                <span
                                                    class="badge bg-{{ $return->status == 'Completed' ? 'success' : 'warning' }}-lt">
                                                    {{ $return->status }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-list flex-nowrap justify-content-end">
                                                    <button type="button" class="btn btn-icon btn-ghost-secondary view-purchase-return-btn"
                                                        data-bs-toggle="modal" data-bs-target="#purchaseReturnDetailModal"
                                                        data-pr-id="{{ $return->id }}"
                                                        title="{{ __('messages.view') }}">
                                                        <i class="ti ti-eye"></i>
                                                    </button>
                                                    <a href="{{ route('admin.por.edit', $return) }}"
                                                        class="btn btn-icon btn-ghost-primary"
                                                        title="{{ __('messages.edit') }}">
                                                        <i class="ti ti-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-icon btn-ghost-danger"
                                                        data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                        title="{{ __('messages.delete') }}"
                                                        onclick="setDeleteFormAction('{{ route('admin.por.destroy', $return->id) }}')">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">
                                                {{ __('messages.no_purchase_returns_found') }}
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
    </div>
</div>
