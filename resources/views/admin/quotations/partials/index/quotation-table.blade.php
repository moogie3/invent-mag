<div id="invoiceTableContainer" class="position-relative">
<div class="table-responsive">
    <table class="table card-table table-vcenter">
        <thead class="text-center">
            <tr>
                <th class="w-1 sticky-top" style="z-index: 1020;"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                <th class="sortable">
                    <button class="table-sort" data-sort="sort-invoice">{{ __('messages.quotation_number') }}</button>
                </th>
                <th class="sortable">
                    <button class="table-sort" data-sort="sort-customer">{{ __('messages.customer') }}</button>
                </th>
                <th class="sortable">
                    <button class="table-sort" data-sort="sort-quotationdate">{{ __('messages.quotation_date') }}</button>
                </th>
                <th class="sortable">
                    <button class="table-sort" data-sort="sort-validuntil">{{ __('messages.valid_until') }}</button>
                </th>
                <th class="sortable">
                    <button class="table-sort" data-sort="sort-amount">{{ __('messages.quotation_total') }}</button>
                </th>
                <th class="sortable">
                    <button class="table-sort" data-sort="sort-tax">{{ __('messages.tax') }}</button>
                </th>
                @php
                    $currentTenant = app('currentTenant');
                    $showPpnCol = $currentTenant instanceof \App\Models\Tenant && $currentTenant->isIndonesian() && $currentTenant->hasFeature('ppn');
                @endphp
                @if($showPpnCol)
                <th class="sortable">
                    <button class="table-sort" data-sort="sort-pph">{{ __('messages.pph_status') }}</button>
                </th>
                @endif
                <th class="sortable">
                    <button class="table-sort" data-sort="sort-status">{{ __('messages.quotation_status') }}</button>
                </th>
                <th style="width:180px;text-align:center" class="no-print">{{ __('messages.actions') }}</th>
            </tr>
        </thead>
        <tbody class="text-center table-tbody">
            @forelse($quotations as $q)
            <tr>
                <td><input type="checkbox" class="form-check-input row-checkbox" value="{{ $q->id }}"></td>
                <td class="sort-invoice">
                    <a href="{{ route('admin.quotations.show', $q->id) }}" class="fw-semibold text-reset">
                        {{ $q->quotation_number }}
                    </a>
                </td>
                <td class="sort-customer">{{ $q->customer?->name ?? 'N/A' }}</td>
                <td class="sort-quotationdate" data-date="{{ $q->quotation_date?->format('Y-m-d') ?? '' }}">{{ $q->quotation_date?->format('d M Y') }}</td>
                <td class="sort-validuntil" data-date="{{ $q->valid_until?->format('Y-m-d') ?? '' }}">{{ $q->valid_until?->format('d M Y') ?? '-' }}</td>
                <td class="sort-amount" data-amount="{{ $q->total }}">{{ \App\Helpers\CurrencyHelper::format($q->total) }}</td>
                <td class="sort-tax">
                    @if(($q->tax_rate ?? 0) > 0)
                        <span class="text-muted">{{ $q->tax_rate }}% ({{ ucfirst($q->tax_type ?? 'exclusive') }})</span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                @if($showPpnCol)
                <td class="sort-pph">
                    @if(($q->pph_enabled ?? false) && ($q->pph_amount ?? 0) > 0)
                        <span class="badge bg-orange-lt">{{ __('messages.withholding_tax') }} {{ $q->pph_rate }}%</span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                @endif
                <td class="sort-status">
                    @php
                        $statusClasses = [
                            'draft' => 'bg-secondary-lt',
                            'sent' => 'bg-primary-lt',
                            'accepted' => 'bg-success-lt',
                            'rejected' => 'bg-danger-lt',
                            'expired' => 'bg-warning-lt',
                            'converted' => 'bg-info-lt',
                        ];
                        $class = $statusClasses[$q->status] ?? 'bg-secondary-lt';
                    @endphp
                    <span class="badge rounded-pill {{ $class }}">{{ __("messages.quotation_status_{$q->status}") }}</span>
                </td>
                <td class="no-print">
                    <div class="dropdown">
                        <button class="btn dropdown-toggle align-text-top" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                            {{ __('messages.actions') }}
                        </button>
                        <div class="dropdown-menu">

                                <a class="dropdown-item" href="{{ route('admin.quotations.show', $q->id) }}">
                                    <i class="ti ti-eye me-2"></i> {{ __('messages.view') }}
                                </a>

                            @can('edit-quotations')

                                <a class="dropdown-item" href="{{ route('admin.quotations.edit', $q->id) }}">
                                    <i class="ti ti-edit me-2"></i> {{ __('messages.edit') }}
                                </a>

                            @endcan

                                <a class="dropdown-item" href="{{ route('admin.quotations.print', $q->id) }}" target="_blank">
                                    <i class="ti ti-printer me-2"></i> {{ __('messages.print_quotation') }}
                                </a>

                            @can('create-sales')
                            @if($q->status === 'accepted' && !$q->converted_to_sales_id)

                                <form action="{{ route('admin.quotations.convert-to-sales', $q->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-success">
                                        <i class="ti ti-report-money me-2"></i> {{ __('messages.convert_to_sales_order') }}
                                    </button>
                                </form>

                            @endif
                            @endcan
                            @can('delete-quotations')

                                <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteModal" data-onclick="setDeleteFormAction('{{ route('admin.quotations.destroy', $q->id) }}')">
                                    <i class="ti ti-trash me-2"></i> {{ __('messages.delete') }}
                                </a>

                            @endcan
                        </div>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="py-4 text-muted">{{ __('messages.no_data') }}</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
</div>
