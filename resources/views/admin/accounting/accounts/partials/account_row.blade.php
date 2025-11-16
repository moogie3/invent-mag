<tr>
    <td>
        <div style="padding-left: {{ $level * 20 }}px;">
            {{ __($account->name) }}
        </div>
    </td>
    <td>{{ $account->code }}</td>
    <td>{{ __(ucfirst($account->type)) }}</td>
    <td>
        @if ($account->is_active)
            <span class="badge bg-success text-white !important">{{ __('messages.yes') }}</span>
        @else
            <span class="badge bg-danger text-white !important">{{ __('messages.no') }}</span>
        @endif
    </td>
    <td>
        <div class="btn-list flex-nowrap">
            <a href="{{ route('admin.accounting.accounts.edit', $account) }}" class="btn">
                {{ __('messages.edit') }}
            </a>
            <form action="{{ route('admin.accounting.accounts.destroy', $account) }}" method="POST" onsubmit="return confirm('{{ __('messages.are_you_sure') }}');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    {{ __('messages.delete') }}
                </button>
            </form>
        </div>
    </td>
</tr>
@if ($account->children->isNotEmpty())
    @foreach ($account->children as $child)
        @include('admin.accounting.accounts.partials.account_row', ['account' => $child, 'level' => $level + 1])
    @endforeach
@endif
