@extends('admin.layouts.base')

@section('title', __('plan.choose_your_plan'))

@section('content')
<div class="page-body">
    <div class="{{ $containerClass ?? 'container-xl' }}">
        <div class="page-header d-print-none mb-4">
            <div class="row align-items-center">
                <div class="col-auto">
                    <a href="{{ route('admin.setting.plan') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 me-2">
                        <i class="ti ti-arrow-left me-1"></i>{{ __('plan.back_to_plan') }}
                    </a>
                </div>
                <div class="col">
                    <h2 class="page-title">{{ __('plan.choose_your_plan') }}</h2>
                </div>
            </div>
        </div>

        @if($upgradeMessage)
            <div class="alert alert-info mb-4 border-0 shadow-sm rounded-4">
                <div class="d-flex align-items-center">
                    <div><i class="ti ti-info-circle text-info fs-2 me-3"></i></div>
                    <div>{{ $upgradeMessage }}</div>
                </div>
            </div>
        @endif

        <div class="row row-cards justify-content-center">
            @foreach($plans as $plan)
                @php
                    $isCurrent = $currentPlan && $currentPlan->id === $plan->id;
                    $isSuggested = $suggestedPlan && $suggestedPlan->id === $plan->id;
                @endphp
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 rounded-4 shadow-sm border-0 {{ $isSuggested ? 'border border-2 border-primary' : '' }} {{ $isCurrent ? 'border border-2 border-success' : '' }}" style="transition: transform 0.2s;">
                        @if($isSuggested && !$isCurrent)
                            <div class="card-status-top bg-primary rounded-top-4"></div>
                        @elseif($isCurrent)
                            <div class="card-status-top bg-success rounded-top-4"></div>
                        @endif

                        <div class="card-body text-center py-5">
                            <h3 class="card-title mb-1 text-uppercase tracking-wide text-secondary">{{ $plan->name }}</h3>
                            <div class="display-5 fw-bold my-4 text-dark">
                                ${{ number_format($plan->price, 0) }}
                                <span class="fs-5 fw-normal text-secondary">{{ __('plan.billing_monthly') }}</span>
                            </div>
                            @if($plan->description)
                                <p class="text-muted mb-4">{{ $plan->description }}</p>
                            @endif

                            <div class="d-flex flex-column gap-2 text-start px-4 mx-auto" style="max-width: 250px;">
                                <div class="text-secondary">
                                    <i class="ti ti-users text-primary me-2"></i>
                                    {{ $plan->max_users === -1 ? __('plan.unlimited') : 'Up to ' . $plan->max_users }} {{ strtolower(__('plan.users')) }}
                                </div>
                                <div class="text-secondary">
                                    <i class="ti ti-building-warehouse text-primary me-2"></i>
                                    {{ $plan->max_warehouses === -1 ? __('plan.unlimited') : $plan->max_warehouses }} {{ strtolower(__('plan.warehouses')) }}
                                </div>
                            </div>

                            @if($plan->hasTrial())
                                <div class="badge bg-info-lt mt-4 rounded-pill px-3 py-2">{{ __('plan.free_trial', ['days' => $plan->trial_days]) }}</div>
                            @endif
                        </div>

                        <div class="card-body border-top border-light py-4 bg-light">
                            <ul class="list-unstyled space-y-3 px-3 m-0 text-start">
                                @foreach($features as $slug => $label)
                                    <li class="d-flex align-items-center {{ $plan->hasFeature($slug) ? '' : 'text-muted' }}">
                                        @if($plan->hasFeature($slug))
                                            <div class="bg-success-lt rounded-circle p-1 me-3 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                <i class="ti ti-check text-success" style="font-size: 14px;"></i>
                                            </div>
                                            <span class="small fw-medium">{{ $label }}</span>
                                        @else
                                            <div class="bg-secondary-lt rounded-circle p-1 me-3 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                <i class="ti ti-x text-muted" style="font-size: 14px;"></i>
                                            </div>
                                            <span class="small">{{ $label }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="card-footer bg-white border-0 text-center pb-4 pt-3 rounded-bottom-4">
                            @if($isCurrent)
                                <button class="btn btn-success w-100 rounded-pill py-2" disabled>
                                    <i class="ti ti-check me-2"></i>{{ __('plan.current_plan') }}
                                </button>
                            @else
                                <button type="button" class="btn {{ $isSuggested ? 'btn-primary' : 'btn-outline-primary' }} w-100 rounded-pill py-2 shadow-sm"
                                        data-bs-toggle="modal" data-bs-target="#upgradeModal"
                                        data-plan-name="{{ $plan->name }}"
                                        data-plan-price="{{ $plan->price }}"
                                        data-plan-slug="{{ $plan->slug }}">
                                    {{ $isSuggested ? __('plan.upgrade_now') : __('plan.switch_plan') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

<div class="modal modal-blur fade" id="upgradeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('plan.confirm_upgrade_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-status bg-primary"></div>
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <i class="ti ti-rocket text-primary" style="font-size: 3rem;"></i>
                </div>
                <h4 class="mb-2">{{ __('plan.upgrade_to') }} <span id="modalPlanName"></span>?</h4>
                <div class="text-muted">{{ __('plan.upgrade_amount') }}: <strong>$<span id="modalPlanPrice"></span></strong></div>
            </div>
            <div class="modal-footer">
                <form method="POST" action="{{ route('admin.setting.plan.change') }}" id="upgradeForm">
                    @csrf
                    <input type="hidden" name="plan" id="modalPlanSlug">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-check me-2"></i>{{ __('plan.confirm_upgrade') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var upgradeModal = document.getElementById('upgradeModal');
    if (upgradeModal) {
        upgradeModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var planName = button.getAttribute('data-plan-name');
            var planPrice = button.getAttribute('data-plan-price');
            var planSlug = button.getAttribute('data-plan-slug');

            document.getElementById('modalPlanName').textContent = planName;
            document.getElementById('modalPlanPrice').textContent = planPrice;
            document.getElementById('modalPlanSlug').value = planSlug;
        });
    }
});
</script>
