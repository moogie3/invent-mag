<div class="page-body mt-4">
    <div class="container-xl">
        <form id="journalEntryForm" action="{{ route('admin.accounting.journal-entries.store') }}" method="POST" data-row-counter="{{ $accounts->count() + 2 }}">
            @csrf
            <input type="hidden" name="transactions" id="transactionsInput">
            
            @include('admin.layouts.partials.journal-entries.create.basic-info')
            @include('admin.layouts.partials.journal-entries.create.transactions-card')
        </form>
    </div>
</div>
