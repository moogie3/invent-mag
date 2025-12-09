<div class="page-body">
    <div class="container-xl">
        <div class="card card-lg">
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <p class="h3">{{ __('messages.company_name') }}</p>
                        <address>
                            {{-- Company address from settings will go here --}}
                            Street Address<br>
                            State, City<br>
                            Region, Postal Code<br>
                            ltd@example.com
                        </address>
                    </div>
                    <div class="col-6 text-end">
                        <p class="h3">{{ $purchaseReturn->purchase->supplier->name }}</p>
                        <address>
                            {{ $purchaseReturn->purchase->supplier->address }}<br>
                            {{ $purchaseReturn->purchase->supplier->city }},
                            {{ $purchaseReturn->purchase->supplier->state }}<br>
                            {{ $purchaseReturn->purchase->supplier->zip_code }},
                            {{ $purchaseReturn->purchase->supplier->country }}<br>
                            {{ $purchaseReturn->purchase->supplier->email }}
                        </address>
                    </div>
                    <div class="col-12 my-5">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h1>{{ __('messages.purchase_return') }} #{{ $purchaseReturn->id }}</h1>
                                <p><strong>{{ __('messages.original_purchase_invoice') }}:</strong> <a
                                        href="{{ route('admin.po.view', $purchaseReturn->purchase_id) }}">#{{ $purchaseReturn->purchase->invoice }}</a>
                                </p>
                                <p><strong>{{ __('messages.return_date') }}:</strong>
                                    {{ $purchaseReturn->return_date->format('d M Y') }}</p>
                                <p><strong>{{ __('messages.status') }}:</strong> <span
                                        class="badge bg-{{ strtolower($purchaseReturn->status) }}">{{ $purchaseReturn->status }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($purchaseReturn->reason)
                    <div class="mb-4">
                        <h4>{{ __('messages.reason_for_return') }}:</h4>
                        <p>{{ $purchaseReturn->reason }}</p>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-transparent table-responsive">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 1%"></th>
                                <th>{{ __('messages.product') }}</th>
                                <th class="text-center" style="width: 1%">{{ __('messages.quantity') }}</th>
                                <th class="text-end" style="width: 1%">{{ __('messages.unit_price') }}</th>
                                <th class="text-end" style="width: 1%">{{ __('messages.total') }}</th>
                            </tr>
                        </thead>
                        @foreach ($purchaseReturn->items as $index => $item)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>
                                    <p class="strong mb-1">{{ $item->product->name }}</p>
                                </td>
                                <td class="text-center">
                                    {{ $item->quantity }}
                                </td>
                                <td class="text-end">{{ App\Helpers\CurrencyHelper::format($item->price) }}</td>
                                <td class="text-end">{{ App\Helpers\CurrencyHelper::format($item->total) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="4" class="strong text-end">{{ __('messages.total_return_amount') }}</td>
                            <td class="text-end font-weight-bold">
                                {{ App\Helpers\CurrencyHelper::format($purchaseReturn->total_amount) }}</td>
                        </tr>
                    </table>
                </div>

                <div class="row mt-4">
                    <div class="col-12 text-muted">
                        <p class="text-sm"><strong>{{ __('messages.notes') }}:</strong> {{-- You can add a notes section if needed --}}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
