<tr>
    <td colspan="8" class="text-center py-5 text-muted">
        <div class="empty">
            <div class="empty-img">
                <i class="ti ti-receipt-off" style="font-size: 3rem;"></i>
            </div>
            <p class="empty-title">No transactions found</p>
            <p class="empty-subtitle text-muted">
                @if (request()->hasAny(['type', 'status', 'date_range', 'search']))
                    Try adjusting your search criteria or filters.
                @else
                    No transactions have been recorded yet.
                @endif
            </p>
            @if (request()->hasAny(['type', 'status', 'date_range', 'search']))
                <div class="empty-action">
                    <a href="{{ route('admin.transactions') }}" class="btn btn-primary">
                        <i class="ti ti-x me-1"></i>
                        Clear filters
                    </a>
                </div>
            @endif
        </div>
    </td>
</tr>
