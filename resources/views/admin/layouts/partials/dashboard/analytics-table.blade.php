<div class="table-responsive">
    <table class="table table-vcenter table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>{{ ucfirst($type) }}</th>
                @if ($type === 'supplier')
                    <th>{{ __('messages.table_location') }}</th>
                @endif
                <th class="text-end">{{ __('messages.total_' . ($type === 'customer' ? 'sales' : 'purchases')) }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($analytics['top' . ucfirst($type) . 's'] as $item)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $item->name }}</div>
                    </td>
                    @if ($type === 'supplier')
                        <td>{{ $item->location }}</td>
                    @endif
                    <td class="text-end fw-medium">
                        {{ \App\Helpers\CurrencyHelper::format($item->{'total_' . ($type === 'customer' ? 'sales' : 'purchases')}) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $type === 'supplier' ? 3 : 2 }}" class="text-center py-3 text-muted">
                        {{ __('messages.no_data_available_for_type', ['type' => $type]) }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
