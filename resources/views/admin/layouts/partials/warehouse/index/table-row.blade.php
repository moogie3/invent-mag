<tr>
    <td class="sort-no no-print">{{ $wos->firstItem() + $index }}</td>
    <td class="sort-name">{{ $wo->name }}</td>
    <td class="sort-address">{{ $wo->address }}</td>
    <td class="sort-description">{{ $wo->description }}</td>
    <td class="sort-is-main">
        @if ($wo->is_main)
            <span class="badge bg-green-lt">Main</span>
        @else
            <span class="badge bg-secondary-lt">Sub</span>
        @endif
    </td>
    <td class="no-print" style="text-align:center">
        @include('admin.layouts.partials.warehouse.index.table-actions', ['wo' => $wo])
    </td>
</tr>
