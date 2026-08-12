@extends('superadmin.layouts.base')
@section('title', 'Landing Page')
@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle"><i class="ti ti-world"></i> Configuration</div>
                    <h2 class="page-title">
                        Landing Page
                        <a href="#" class="ms-3 text-muted" data-bs-toggle="modal" data-bs-target="#how-it-works-modal" title="How It Works">
                            <i class="ti ti-help-circle fs-3"></i>
                        </a>
                    </h2>
                    <div class="text-muted mt-1">Manage the public-facing website content for invent-mag-fe.</div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card shadow border-0 rounded-3 card-hover h-100 text-center p-3">
                        <div class="card-body">
                            <span class="avatar avatar-md bg-blue-lt rounded-3 mb-2">
                                <i class="ti ti-writing fs-2 text-blue"></i>
                            </span>
                            <h5>Hero Section</h5>
                            <p class="text-muted small mb-0">Edit the main headline, subtitle, and CTA button.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow border-0 rounded-3 card-hover h-100 text-center p-3">
                        <div class="card-body">
                            <span class="avatar avatar-md bg-green-lt rounded-3 mb-2">
                                <i class="ti ti-layout-grid fs-2 text-green"></i>
                            </span>
                            <h5>Features Section</h5>
                            <p class="text-muted small mb-0">Update the features section heading and description.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow border-0 rounded-3 card-hover h-100 text-center p-3">
                        <div class="card-body">
                            <span class="avatar avatar-md bg-purple-lt rounded-3 mb-2">
                                <i class="ti ti-currency-dollar fs-2 text-purple"></i>
                            </span>
                            <h5>Pricing Section</h5>
                            <p class="text-muted small mb-0">Update the pricing section heading and description.</p>
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('superadmin.cms.update') }}">
                @csrf
                <div class="card shadow border-0 rounded-4">
                    <div class="card-header">
                        <h3 class="card-title"><i class="ti ti-home me-2 text-muted"></i>Hero Section</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Hero Title</label>
                            <input type="text" name="hero_title" class="form-control" value="{{ $content['hero_title'] }}" maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hero Subtitle</label>
                            <textarea name="hero_subtitle" class="form-control" rows="2" maxlength="500">{{ $content['hero_subtitle'] }}</textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Primary CTA Text</label>
                                <input type="text" name="hero_cta_text" class="form-control" value="{{ $content['hero_cta_text'] }}" maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Primary CTA URL</label>
                                <input type="text" name="hero_cta_url" class="form-control" value="{{ $content['hero_cta_url'] }}" maxlength="255">
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label">Secondary CTA Text (Demo)</label>
                                <input type="text" name="hero_cta_demo_text" class="form-control" value="{{ $content['hero_cta_demo_text'] }}" maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Secondary CTA URL (Demo)</label>
                                <input type="text" name="hero_cta_demo_url" class="form-control" value="{{ $content['hero_cta_demo_url'] }}" maxlength="255">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow border-0 rounded-4 mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="ti ti-layout-grid me-2 text-muted"></i>Features Section</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Section Title</label>
                            <input type="text" name="features_title" class="form-control" value="{{ $content['features_title'] }}" maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Section Subtitle</label>
                            <textarea name="features_subtitle" class="form-control" rows="2" maxlength="500">{{ $content['features_subtitle'] }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Section Description</label>
                            <textarea name="features_description" class="form-control" rows="3" maxlength="1000">{{ $content['features_description'] }}</textarea>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Enterprise Features Heading</label>
                            <input type="text" name="features_enterprise_heading" class="form-control" value="{{ $content['features_enterprise_heading'] }}" maxlength="255">
                        </div>
                    </div>
                </div>

                @php
                    $featureCards = [
                        1 => ['Inventory & Product Management', 'Full product cataloging, real-time stock tracking across multiple warehouses, physical stock opname with variance tracking, warehouse-to-warehouse stock transfers, low-stock alerts, barcode support, and expiry tracking.'],
                        2 => ['Sales, CRM & Quotations', 'Complete sales order management, Point of Sale (POS), customer database with purchase history, sales pipeline for opportunity tracking, and quotation generation with full lifecycle management including approval workflows, revision history, and seamless conversion to invoices or sales orders.'],
                        3 => ['Purchase & SRM', 'Full purchase order lifecycle, supplier management with historical data, and efficient purchase return processing.'],
                        4 => ['Financial & Accounting Suite', 'Chart of Accounts, General Ledger, Manual Journal Entries, Trial Balance, Income Statement, Balance Sheet, and AR/AP Aging Reports.'],
                        5 => ['HR & Payroll', 'Comprehensive employee attendance tracking, automated salaried proration, customizable allowances, and automated two-step accounting sync.'],
                        6 => ['Multi-Currency Support', 'Configure your currency display with custom symbols, thousands and decimal separators, and decimal places for a consistent, localized experience.'],
                        7 => ['Returns Management', 'Complete sales and purchase return workflows with automatic stock adjustments and accounting entries.'],
                        8 => ['User & Role Management', 'Secure access control with 101 granular permissions. Assign roles like Superuser, Staff, or POS operator.'],
                        9 => ['Sales Forecasting AI', 'AI-powered sales predictions using Holt-Winters algorithms and linear regression for accurate demand forecasting.'],
                        10 => ['RESTful API Access', 'Full API access with Sanctum authentication. Integrate with external systems and build custom integrations.'],
                        11 => ['Audit Trail & Compliance', 'Comprehensive audit logging for all system changes. Track user actions, modifications, and maintain compliance records.'],
                    ];
                @endphp
                <div class="card shadow border-0 rounded-4 mt-3">
                    <div class="card-header" data-bs-toggle="collapse" data-bs-target="#featureCardsCollapse" role="button" aria-expanded="false" style="cursor: pointer;">
                        <h3 class="card-title"><i class="ti ti-layout-list me-2 text-muted"></i>Feature Cards</h3>
                        <div class="card-actions">
                            <span class="text-muted small me-2">11 cards</span>
                            <i class="ti ti-chevron-down"></i>
                        </div>
                    </div>
                    <div class="collapse" id="featureCardsCollapse">
                        <div class="card-body">
                            <p class="text-muted small mb-3">Each feature card has a title and description shown on the landing page.</p>
                            @foreach($featureCards as $i => $defaults)
                            <div class="border rounded-3 p-3 mb-3">
                                <div class="fw-semibold mb-2 text-body-emphasis">Card {{ $i }}: <span class="text-muted fw-normal">{{ $defaults[0] }}</span></div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small">Title</label>
                                        <input type="text" name="feature_card_{{ $i }}_title" class="form-control" value="{{ $content['feature_card_' . $i . '_title'] }}" maxlength="255">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label small">Description</label>
                                        <textarea name="feature_card_{{ $i }}_desc" class="form-control" rows="2" maxlength="1000">{{ $content['feature_card_' . $i . '_desc'] }}</textarea>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="card shadow border-0 rounded-4 mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="ti ti-currency-dollar me-2 text-muted"></i>Pricing Section</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Badge Text</label>
                            <input type="text" name="pricing_badge" class="form-control" value="{{ $content['pricing_badge'] }}" maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Section Title</label>
                            <input type="text" name="pricing_title" class="form-control" value="{{ $content['pricing_title'] }}" maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Section Subtitle</label>
                            <textarea name="pricing_subtitle" class="form-control" rows="2" maxlength="500">{{ $content['pricing_subtitle'] }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Feature Badges</label>
                            <div class="row g-3 mt-1">
                                <div class="col-md-4">
                                    <input type="text" name="pricing_no_credit_badge" class="form-control" value="{{ $content['pricing_no_credit_badge'] }}" maxlength="255" placeholder="e.g. No credit card required">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="pricing_trial_badge" class="form-control" value="{{ $content['pricing_trial_badge'] }}" maxlength="255" placeholder="e.g. 30-day free trial">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="pricing_cancel_badge" class="form-control" value="{{ $content['pricing_cancel_badge'] }}" maxlength="255" placeholder="e.g. Cancel anytime">
                                </div>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-semibold">Plan Card Labels</label>
                            <div class="row g-3 mt-1">
                                <div class="col-md-4">
                                    <input type="text" name="pricing_most_popular_badge" class="form-control" value="{{ $content['pricing_most_popular_badge'] }}" maxlength="255" placeholder="e.g. most popular">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="pricing_trial_included" class="form-control" value="{{ $content['pricing_trial_included'] }}" maxlength="255" placeholder="e.g. 30 days free trial included">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="pricing_enterprise_support" class="form-control" value="{{ $content['pricing_enterprise_support'] }}" maxlength="255" placeholder="e.g. Enterprise support & SLA">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow border-0 rounded-4 mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="ti ti-speakerphone me-2 text-muted"></i>Call-to-Action Section</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Section Title</label>
                            <input type="text" name="cta_title" class="form-control" value="{{ $content['cta_title'] }}" maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Section Description</label>
                            <textarea name="cta_description" class="form-control" rows="2" maxlength="500">{{ $content['cta_description'] }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card shadow border-0 rounded-4 mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="ti ti-settings me-2 text-muted"></i>Footer & Contact</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Contact Email</label>
                            <input type="email" name="contact_email" class="form-control @error('contact_email') is-invalid @enderror" value="{{ $content['contact_email'] }}" maxlength="255">
                            @error('contact_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Footer Text</label>
                            <input type="text" name="footer_text" class="form-control @error('footer_text') is-invalid @enderror" value="{{ $content['footer_text'] }}" maxlength="500">
                            @error('footer_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-end border-top">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="ti ti-device-floppy me-1"></i>Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endSection
