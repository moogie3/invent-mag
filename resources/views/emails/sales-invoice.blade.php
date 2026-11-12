<x-mail::message>
# {{ __('messages.sales_invoice') }} {{ $sale->invoice }}

@php
    // A status word with no translation key must never reach the customer as
    // "messages.something": Lang::has() checks the fallback locale too, so a
    // key that exists in en still resolves for id, and anything genuinely
    // unmapped falls back to the stored status value itself.
    $statusKey = 'messages.'.strtolower($sale->status ?? 'unpaid');
    $statusLabel = \Illuminate\Support\Facades\Lang::has($statusKey) ? __($statusKey) : (string) ($sale->status ?? '-');
@endphp
{{ __('messages.invoice_email_intro', ['invoice' => $sale->invoice]) }}

<x-mail::table>
| {{ __('messages.invoice_no') }} | {{ __('messages.date') }} | {{ __('messages.customer') }} | {{ __('messages.status') }} |
|:--|:--|:--|:--|
| {{ $sale->invoice }} | {{ $sale->created_at?->format('d/m/Y') }} | {{ $sale->customer?->name ?? '-' }} | {{ $statusLabel }} |
</x-mail::table>

<x-mail::table>
| {{ __('messages.product_name') }} | {{ __('messages.quantity') }} | {{ __('messages.price') }} | {{ __('messages.subtotal') }} |
|:--|--:|--:|--:|
@foreach($items as $item)
| {{ $item->product->name ?? 'N/A' }} | {{ $item->quantity }} | {{ \App\Helpers\CurrencyHelper::format($item->customer_price) }} | {{ \App\Helpers\CurrencyHelper::format($item->total) }} |
@endforeach
</x-mail::table>

@php
    // Same figures in the same order as the invoice screen (sales view amount
    // summary): gross total, then the deductions, then what is paid and what is
    // still owed - so the page, this email and the attached PDF cannot disagree.
    // grand_total is read FIRST because balance() lazy-loads salesItems, and
    // grand_total recomputes from items once that relation is loaded. Reading it
    // first keeps it on the stored total the PDF prints. getTotalReturnedAmount()
    // only reads salesReturns when the relation is already loaded.
    $grandTotal = $sale->grand_total;
    $sale->loadMissing('salesReturns');
    $returned = $sale->getTotalReturnedAmount();
    $creditUsed = (float) $sale->customer_credit_used;
    $paid = (float) $sale->total_paid;
    $balance = (float) $sale->balance;
@endphp
<x-mail::table>
| {{ __('messages.table_details') }} | {{ __('messages.amount') }} |
|:--|--:|
| **{{ __('messages.grand_total') }}** | **{{ \App\Helpers\CurrencyHelper::format($grandTotal) }}** |
@if($returned > 0)
| {{ __('messages.returned') }} ({{ __('messages.sales_totals_adjusted') }}) | {{ \App\Helpers\CurrencyHelper::format($returned) }} |
@endif
@if($creditUsed > 0)
| {{ __('messages.credit_used') }} | {{ \App\Helpers\CurrencyHelper::format($creditUsed) }} |
@endif
| {{ __('messages.total_paid') }} | {{ \App\Helpers\CurrencyHelper::format($paid) }} |
| **{{ __('messages.balance') }}** | **{{ \App\Helpers\CurrencyHelper::format($balance) }}** |
</x-mail::table>

@if($balance > 0)
<x-mail::panel>
**{{ $paid > 0 ? __('messages.partial') : __('messages.unpaid') }}**: {{ $paid > 0 ? __('messages.invoice_email_partial_note', ['paid' => \App\Helpers\CurrencyHelper::format($paid), 'amount' => \App\Helpers\CurrencyHelper::format($grandTotal), 'balance' => \App\Helpers\CurrencyHelper::format($balance)]) : __('messages.invoice_email_unpaid_note', ['balance' => \App\Helpers\CurrencyHelper::format($balance)]) }}
</x-mail::panel>
@endif

{{ __('messages.invoice_email_attachment_note') }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
