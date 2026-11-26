@extends('mobile.layouts.app')

@section('title', __('messages.products'))
@section('page', 'products')

@section('content')
    <div class="pos-search">
        <div class="scan-row">
            <input class="input" type="search" id="product-search"
                placeholder="{{ __('messages.mobile_product_search_placeholder') }}" autocomplete="off"
                enterkeyhint="search" />
            <button type="button" class="btn btn-outline" id="product-scan-button"
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
        <input type="text" id="product-barcode-input" class="input"
            placeholder="{{ __('messages.mobile_barcode_placeholder') }}" autocomplete="off"
            style="margin-top: 8px;" />
    </div>

    <div id="product-loading" class="spinner" role="status" hidden></div>
    <div id="product-results"></div>
    <p class="empty" id="product-empty">{{ __('messages.mobile_search_hint') }}</p>

    {{-- Product detail sheet --}}
    <div class="sheet-overlay" id="detail-overlay"></div>
    <section class="sheet" id="detail-sheet" role="dialog" aria-modal="true"
        aria-labelledby="detail-sheet-title">
        <div class="sheet-grip"></div>
        <div class="sheet-head">
            <span class="sheet-title" id="detail-sheet-title">{{ __('messages.details') }}</span>
            <button type="button" class="btn-icon" data-close-sheet="detail"
                aria-label="{{ __('messages.close') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="sheet-body" id="detail-body"></div>
    </section>
@endsection
