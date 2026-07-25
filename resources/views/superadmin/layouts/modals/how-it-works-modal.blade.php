@php
    use Illuminate\Support\Str;
    $tutorials = config('superadmin-tutorial', []);
    $currentRoute = Route::currentRouteName();
    $tutorial = null;
    foreach ($tutorials as $pattern => $data) {
        if (Str::is($pattern, $currentRoute)) {
            $tutorial = $data;
            break;
        }
    }
@endphp
<style>
    #how-it-works-modal .badge { color: #fff; }
    #how-it-works-modal ol li .badge { color: #fff; }
    #how-it-works-modal .modal-body .badge { color: #fff; }
</style>
<div class="modal modal-blur fade" id="how-it-works-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h3 class="modal-title fw-bold">
                    <i class="ti ti-help-circle text-primary me-2"></i>How It Works
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @if($tutorial)
                    {{-- Contextual help for the current page --}}
                    <div class="text-center mb-4">
                        <span class="avatar avatar-lg rounded-3 mb-3" style="background: var(--tblr-primary-lt);">
                            <i class="{{ $tutorial['icon'] }} fs-2 text-primary"></i>
                        </span>
                        <h4 class="fw-bold mb-1">{{ $tutorial['title'] }}</h4>
                        <p class="text-muted mb-0">{{ $tutorial['description'] }}</p>
                    </div>

                    @if(!empty($tutorial['alert']))
                        <div class="alert alert-info">
                            {!! $tutorial['alert'] !!}
                        </div>
                    @endif

                    @if(!empty($tutorial['steps']))
                        <div class="card border shadow-sm rounded-3">
                            <div class="card-body p-4">
                                <h5 class="fw-semibold mb-3"><i class="ti ti-list me-1 text-primary"></i>Steps</h5>
                                <ol class="mb-0 ps-3" style="line-height: 2;">
                                    @foreach($tutorial['steps'] as $step)
                                        <li class="text-muted">{!! $step !!}</li>
                                    @endforeach
                                </ol>
                            </div>
                        </div>
                    @endif

                    @if(!empty($tutorial['env_table']))
                        <div class="mt-3">
                            <h6 class="fw-semibold mb-2"><i class="ti ti-settings me-1 text-primary"></i>Environment Configuration</h6>
                            @if(!empty($tutorial['env_note']))
                                <p class="small text-muted mb-2">{!! $tutorial['env_note'] !!}</p>
                            @endif
                            <table class="table table-sm table-bordered small mb-0">
                                <thead>
                                    <tr><th>Environment</th><th>{{ $tutorial['env_key'] ?? 'Value' }}</th><th>Where to Configure</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($tutorial['env_table'] as $env)
                                        <tr>
                                            <td><span class="badge bg-{{ $loop->first ? 'green' : 'blue' }}-lt">{{ $env['label'] }}</span></td>
                                            <td><code>{{ $env['value'] }}</code></td>
                                            <td>{{ $env['where'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if(!empty($tutorial['docs_file']))
                        <div class="mt-3 p-3 rounded-3 border">
                            <div class="d-flex align-items-start gap-3">
                                <i class="ti ti-book text-primary fs-3 mt-1"></i>
                                <div>
                                    <h6 class="fw-bold mb-1">Full Documentation</h6>
                                    <p class="small text-muted mb-0">
                                        <code>{{ $tutorial['docs_file'] }}</code>
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                @else
                    {{-- Overview grid when no route-specific tutorial exists --}}
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card border shadow-sm rounded-3 h-100">
                                <div class="card-body text-center p-3">
                                    <div class="mb-2">
                                        <span class="avatar avatar-md bg-blue-lt rounded-3">
                                            <i class="ti ti-building-community fs-2 text-blue"></i>
                                        </span>
                                    </div>
                                    <h5 class="fw-semibold">Tenants</h5>
                                    <p class="text-muted small mb-0">Manage tenant accounts, view details, toggle active status, and monitor plan assignments.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border shadow-sm rounded-3 h-100">
                                <div class="card-body text-center p-3">
                                    <div class="mb-2">
                                        <span class="avatar avatar-md bg-indigo-lt rounded-3">
                                            <i class="ti ti-packages fs-2 text-indigo"></i>
                                        </span>
                                    </div>
                                    <h5 class="fw-semibold">Plans &amp; Subscriptions</h5>
                                    <p class="text-muted small mb-0">Create and edit pricing plans. View all active, trialing, and expired subscriptions.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border shadow-sm rounded-3 h-100">
                                <div class="card-body text-center p-3">
                                    <div class="mb-2">
                                        <span class="avatar avatar-md bg-orange-lt rounded-3">
                                            <i class="ti ti-flag fs-2 text-orange"></i>
                                        </span>
                                    </div>
                                    <h5 class="fw-semibold">Features &amp; Tiers</h5>
                                    <p class="text-muted small mb-0">Toggle feature availability per plan. Features are grouped by tier: Core, Professional, Enterprise.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border shadow-sm rounded-3 h-100">
                                <div class="card-body text-center p-3">
                                    <div class="mb-2">
                                        <span class="avatar avatar-md bg-green-lt rounded-3">
                                            <i class="ti ti-currency-dollar fs-2 text-green"></i>
                                        </span>
                                    </div>
                                    <h5 class="fw-semibold">Billing &amp; Payments</h5>
                                    <p class="text-muted small mb-0">View revenue overview, all transaction orders, pending payments needing approval, and paid invoices.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border shadow-sm rounded-3 h-100">
                                <div class="card-body text-center p-3">
                                    <div class="mb-2">
                                        <span class="avatar avatar-md bg-teal-lt rounded-3">
                                            <i class="ti ti-world fs-2 text-teal"></i>
                                        </span>
                                    </div>
                                    <h5 class="fw-semibold">Landing Page</h5>
                                    <p class="text-muted small mb-0">Edit the public-facing website content: hero, features, pricing tiers, and contact info.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border shadow-sm rounded-3 h-100">
                                <div class="card-body text-center p-3">
                                    <div class="mb-2">
                                        <span class="avatar avatar-md bg-purple-lt rounded-3">
                                            <i class="ti ti-shield-lock fs-2 text-purple"></i>
                                        </span>
                                    </div>
                                    <h5 class="fw-semibold">System &amp; Security</h5>
                                    <p class="text-muted small mb-0">Manage permissions, view audit logs, configure system settings, and schedule backups.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="modal-footer border-0 justify-content-center pt-0">
                <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">
                    <i class="ti ti-check me-1"></i>Got it
                </button>
            </div>
        </div>
    </div>
</div>
