@extends('mobile.layouts.app')

@section('title', __('messages.pos'))
@section('page', 'pos')

@section('content')
    <div class="pos-search">
        <div class="scan-row">
            <input class="input" type="search" id="pos-search"
                placeholder="{{ __('messages.mobile_pos_search_placeholder') }}"
                autocomplete="off" autocorrect="off" spellcheck="false" enterkeyhint="search" />
            <button type="button" class="btn btn-outline" id="pos-scan-button"
                title="{{ __('messages.mobile_scan_barcode') }}">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 7V5a2 2 0 0 1 2-2h2" />
                    <path d="M17 3h2a2 2 0 0 1 2 2v2" />
                    <path d="M21 17v2a2 2 0 0 1-2 2h-2" />
                    <path d="M7 21H5a2 2 0 0 1-2-2v-2" />
                    <path d="M7 8v8" />
                    <path d="M11 8v8" />
                    <path d="M15 8v8" />
                </svg>
            </button>
        </div>
        <input type="text" id="pos-barcode-input" class="input"
            placeholder="{{ __('messages.mobile_barcode_placeholder') }}" autocomplete="off"
            style="margin-top: 8px;" />
    </div>

    <div id="pos-catalog-loading" class="spinner" role="status"></div>

    <div class="catalog" id="pos-catalog"></div>

    <p class="empty" id="pos-catalog-empty" hidden>{{ __('messages.no_products_found') }}</p>

    <button type="button" class="btn btn-outline btn-block" id="pos-load-more" hidden>
        {{ __('messages.load_more') }}
    </button>

    <button type="button" class="cart-bar" id="cart-bar" hidden>
        <span class="cart-count" id="cart-count">0</span>
        <span id="cart-bar-total">{{ __('messages.cart_is_empty') }}</span>
        <span>{{ __('messages.view_cart') }} &rarr;</span>
    </button>

    {{-- Cart sheet --}}
    <div class="sheet-overlay" id="cart-overlay"></div>
    <section class="sheet" id="cart-sheet" role="dialog" aria-modal="true"
        aria-labelledby="cart-sheet-title">
        <div class="sheet-grip"></div>
        <div class="sheet-head">
            <span class="sheet-title" id="cart-sheet-title">{{ __('messages.cart') }}</span>
            <div class="row">
                <button type="button" class="btn btn-sm btn-outline" id="cart-clear">
                    {{ __('messages.clear_cart') }}
                </button>
                <button type="button" class="btn-icon" data-close-sheet="cart"
                    aria-label="{{ __('messages.close') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="sheet-body">
            <p class="empty" id="cart-empty-note">{{ __('messages.cart_is_empty') }}</p>
            <div id="cart-lines"></div>

            <div class="totals" id="cart-totals" hidden>
                <div class="total-row">
                    <span>{{ __('messages.subtotal') }}</span>
                    <span id="cart-subtotal">0</span>
                </div>

                <div class="discount-line">
                    <input class="input" type="number" id="cart-discount" value="0" min="0" step="any"
                        inputmode="decimal" aria-label="{{ __('messages.po_order_discount') }}" />
                    <select class="select" id="cart-discount-type"
                        aria-label="{{ __('messages.discount') }}">
                        <option value="fixed">{{ __('messages.po_fixed') }}</option>
                        <option value="percentage">%</option>
                    </select>
                </div>

                <div class="field" style="margin-top: 10px;">
                    <label for="cart-tax">{{ __('messages.tax') }}</label>
                    <select class="select" id="cart-tax">
                        <option value="">{{ __('messages.none') }} (0%)</option>
                    </select>
                </div>

                <div class="field" id="cart-pph-field" hidden>
                    <label for="cart-pph">{{ __('messages.withholding_tax') }}</label>
                    <select class="select" id="cart-pph">
                        <option value="">{{ __('messages.none') }}</option>
                    </select>
                </div>

                <div class="total-row" id="cart-tax-row" hidden>
                    <span id="cart-tax-label">{{ __('messages.tax') }}</span>
                    <span id="cart-tax-amount">0</span>
                </div>

                <div class="total-row" id="cart-pph-row" hidden>
                    <span>{{ __('messages.withholding_tax') }}</span>
                    <span id="cart-pph-amount">0</span>
                </div>

                <div class="total-row grand">
                    <span>{{ __('messages.po_grand_total') }}</span>
                    <span id="cart-grand-total">0</span>
                </div>
            </div>
        </div>

        <div class="sheet-foot">
            <button type="button" class="btn btn-primary btn-block" id="cart-checkout" disabled>
                {{ __('messages.mobile_checkout') }}
            </button>
        </div>
    </section>

    {{-- Payment sheet --}}
    <div class="sheet-overlay" id="pay-overlay"></div>
    <section class="sheet" id="pay-sheet" role="dialog" aria-modal="true" aria-labelledby="pay-sheet-title">
        <div class="sheet-grip"></div>
        <div class="sheet-head">
            <span class="sheet-title" id="pay-sheet-title">{{ __('messages.pos_order_summary_title') }}</span>
            <button type="button" class="btn-icon" data-close-sheet="pay" aria-label="{{ __('messages.close') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="sheet-body">
            <p class="card-title">{{ __('messages.pos_payment_method') }}</p>
            <div class="method-grid" id="pay-methods">
                <button type="button" class="method active" data-method="Cash">
                    {{ __('messages.pos_payment_method_cash') }}</button>
                <button type="button" class="method" data-method="Card">
                    {{ __('messages.pos_payment_method_card') }}</button>
                <button type="button" class="method" data-method="Transfer">
                    {{ __('messages.pos_payment_method_transfer') }}</button>
                <button type="button" class="method" data-method="eWallet">
                    {{ __('messages.pos_payment_method_ewallet') }}</button>
            </div>

            <div id="pay-cash-block">
                <div class="field">
                    <label for="pay-received">{{ __('messages.pos_amount_received') }}</label>
                    <div class="row">
                        <input class="input" type="number" id="pay-received" min="0" step="any"
                            inputmode="decimal" placeholder="0" />
                        <button type="button" class="btn btn-outline" id="pay-exact">
                            {{ __('messages.pos_exact_amount') }}
                        </button>
                    </div>
                </div>

                <div class="change-box">
                    <span>{{ __('messages.pos_change') }}</span>
                    <span class="amount" id="pay-change">0</span>
                </div>
            </div>

            <div class="totals">
                <div class="total-row grand">
                    <span>{{ __('messages.po_grand_total') }}</span>
                    <span id="pay-grand-total">0</span>
                </div>
            </div>

            <p class="error-text" id="pay-error" hidden></p>
        </div>

        <div class="sheet-foot">
            <button type="button" class="btn btn-primary btn-block" id="pay-submit">
                {{ __('messages.pos_complete_transaction') }}
            </button>
        </div>
    </section>

    {{-- Receipt sheet --}}
    <div class="sheet-overlay" id="receipt-overlay"></div>
    <section class="sheet" id="receipt-sheet" role="dialog" aria-modal="true"
        aria-labelledby="receipt-sheet-title">
        <div class="sheet-grip"></div>
        <div class="sheet-head">
            <span class="sheet-title" id="receipt-sheet-title">{{ __('messages.receipt') }}</span>
            <button type="button" class="btn-icon" data-close-sheet="receipt"
                aria-label="{{ __('messages.close') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="sheet-body" id="receipt-body"></div>
        <div class="sheet-foot">
            <button type="button" class="btn btn-primary btn-block" id="receipt-new-sale">
                {{ __('messages.mobile_new_sale') }}
            </button>
        </div>
    </section>
@endsection
