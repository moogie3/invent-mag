<div class="page-body mt-4">
    <div class="{{ $containerClass ?? "container-xl" }}">
        <form id="journalEntryForm" action="{{ route('admin.accounting.journal-entries.update', $entry) }}" method="POST" data-row-counter="{{ $entry->transactions->count() }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="transactions" id="transactionsInput">
            
            @include('admin.layouts.partials.journal-entries.edit.basic-info')
            @include('admin.layouts.partials.journal-entries.edit.transactions-card')
        </form>
    </div>
</div>
