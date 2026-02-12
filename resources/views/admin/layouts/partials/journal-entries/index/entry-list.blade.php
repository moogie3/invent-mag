@if($entries->count() == 0)
    <div class="empty">
        <div class="empty-img">
            <i class="ti ti-notebook-off" style="font-size: 5rem; color: #ccc;"></i>
        </div>
        <p class="empty-title">{{ __('messages.no_journal_entries_found') }}</p>
        <p class="empty-subtitle text-muted">{{ __('messages.no_journal_entries_message') }}</p>
        <div class="empty-action">
            <a href="{{ route('admin.accounting.journal-entries.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-2"></i>{{ __('messages.create_first_entry') }}
            </a>
        </div>
    </div>
@else
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead style="font-size: large">
                <tr>
                    <th class="fs-4 py-3">{{ __('messages.date') }}</th>
                    <th class="fs-4 py-3">{{ __('messages.description') }}</th>
                    <th class="fs-4 py-3 text-end">{{ __('messages.debit') }}</th>
                    <th class="fs-4 py-3 text-end">{{ __('messages.credit') }}</th>
                    <th class="fs-4 py-3">{{ __('messages.status') }}</th>
                    <th class="fs-4 py-3 w-1">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entries as $entry)
                    <tr>
                        <td>{{ $entry->date->format('d/m/Y') }}</td>
                        <td>
                            <a href="{{ route('admin.accounting.journal-entries.show', $entry) }}" class="text-decoration-none">
                                {{ Str::limit($entry->description, 50) }}
                            </a>
                            @if($entry->reversingEntry || $entry->reversedEntry)
                                <span class="badge bg-warning text-dark ms-1" title="{{ __('messages.reversed') }}">
                                    <i class="ti ti-arrows-left-right"></i>
                                </span>
                            @endif
                        </td>
                        <td class="text-end">{{ $entry->total_debit ? \App\Helpers\CurrencyHelper::format($entry->total_debit) : 'Rp 0,00' }}</td>
                        <td class="text-end">{{ $entry->total_credit ? \App\Helpers\CurrencyHelper::format($entry->total_credit) : 'Rp 0,00' }}</td>
                        <td>
                            @if($entry->status == 'draft')
                                <span class="badge bg-warning text-dark">{{ __('messages.draft') }}</span>
                            @elseif($entry->status == 'posted')
                                <span class="badge bg-success">{{ __('messages.posted') }}</span>
                            @else
                                <span class="badge bg-danger">{{ __('messages.voided') }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-list flex-nowrap justify-content-end">
                                <a href="{{ route('admin.accounting.journal-entries.show', $entry) }}" 
                                    class="btn btn-icon btn-ghost-secondary"
                                    title="{{ __('messages.view') }}">
                                    <i class="ti ti-eye"></i>
                                </a>
                                @if($entry->status == 'draft')
                                    <a href="{{ route('admin.accounting.journal-entries.edit', $entry) }}" 
                                        class="btn btn-icon btn-ghost-primary"
                                        title="{{ __('messages.edit') }}">
                                        <i class="ti ti-edit"></i>
                                    </a>
                                @endif
                                @if($entry->status == 'posted')
                                    <button type="button" 
                                        class="btn btn-icon btn-ghost-warning"
                                        title="{{ __('messages.reverse') }}"
                                        onclick="confirmAction('{{ route('admin.accounting.journal-entries.reverse', $entry) }}', '{{ __('messages.reverse_entry') }}', '{{ __('messages.reverse_confirmation') }}')">
                                        <i class="ti ti-arrow-back-up"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-3">
        {{ $entries->appends(request()->query())->links() }}
    </div>
@endif
