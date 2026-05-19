@php
    $routeName = Route::currentRouteName();
    $tutorialService = app(\App\Services\TutorialService::class);
    $context = $tutorialService->getContextForRoute($routeName);
    
    $contextTitle = __($context['title']);
    $contextIcon = $context['icon'];
    $contextDesc = __($context['desc']);
    $contextSteps = array_map(fn($step) => __($step), $context['steps'] ?? []);
@endphp

@php
    $tutorialTopics = config('tutorial.topics', []);
    foreach ($tutorialTopics as &$topic) {
        foreach ($topic['items'] as &$item) {
            if (isset($item['route'])) {
                try {
                    $item['url'] = route($item['route']);
                } catch (\Exception $e) {
                    $item['url'] = '#';
                }
            }
        }
    }
@endphp

<div class="modal modal-blur fade" id="tutorial-modal" tabindex="-1" role="dialog" aria-hidden="true" data-completed="{{ auth()->check() && auth()->user()->tutorial_completed ? 'true' : 'false' }}" data-route="{{ $routeName }}" data-topics="{{ json_encode($tutorialTopics) }}">
    <div class="modal-dialog modal-dialog-centered modal-lg tutorial-modal-dialog" role="document">


        <div class="modal-content shadow-lg border-0" style="border-radius: 20px; overflow: hidden;">

            <!-- Header with Gradient -->
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #1e40af, #3b82f6); border-bottom: none; padding: 20px 24px;">
                <h5 class="modal-title fw-bold fs-3" id="tutorial-modal-title">
                    <i class="ti ti-rocket me-2"></i> {{ __('tutorial.welcome_title') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="tutorial-btn-close"></button>
            </div>

            <!-- STEP 1: Workflow Overview (For First Time Login) -->
            <div class="modal-body py-5 px-4 px-md-5 tutorial-step" id="tutorial-step-1">
                <div class="text-center mb-5">
                    <h2 class="fw-bold text-dark mb-3">{{ __('tutorial.welcome_subtitle') }}</h2>
                    <p class="text-secondary fs-4">
                        {{ __('tutorial.welcome_description') }}
                    </p>
                </div>

                <div class="row">
                    <div class="col-md-10 offset-md-1">

                        <div class="tutorial-list-item">
                            <div class="tutorial-icon-box bg-primary-lt text-primary me-4 shadow-sm">
                                <i class="ti ti-settings"></i>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold fs-4">1. Configuration</h3>
                                <p class="text-secondary mb-0">Set up your base parameters: <strong>Units, Categories, Currencies, and Taxes</strong>.</p>
                            </div>
                        </div>

                        <div class="tutorial-list-item">
                            <div class="tutorial-icon-box bg-success-lt text-success me-4 shadow-sm">
                                <i class="ti ti-database"></i>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold fs-4">2. Master Data</h3>
                                <p class="text-secondary mb-0">Input your core entities: <strong>Warehouses, Suppliers, Customers, and Products</strong>.</p>
                            </div>
                        </div>

                        <div class="tutorial-list-item">
                            <div class="tutorial-icon-box bg-warning-lt text-warning me-4 shadow-sm">
                                <i class="ti ti-shopping-cart"></i>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold fs-4">3. Operations</h3>
                                <p class="text-secondary mb-0">Manage daily tasks: <strong>Purchase Orders, Sales, Returns, POS, and Pipeline</strong>.</p>
                            </div>
                        </div>

                        <div class="tutorial-list-item">
                            <div class="tutorial-icon-box bg-pink-lt text-pink me-4 shadow-sm">
                                <i class="ti ti-users-group"></i>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold fs-4">4. HR & Payroll</h3>
                                <p class="text-secondary mb-0">Manage your workforce: <strong>Employees, Attendance tracking, and Payroll processing</strong>.</p>
                            </div>
                        </div>

                        <div class="tutorial-list-item">
                            <div class="tutorial-icon-box bg-info-lt text-info me-4 shadow-sm">
                                <i class="ti ti-chart-bar"></i>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold fs-4">5. Accounting & Reports</h3>
                                <p class="text-secondary mb-0">Track your finances: <strong>Journals, Chart of Accounts, Income Statements</strong>.</p>
                            </div>
                        </div>

                        <div class="tutorial-list-item">
                            <div class="tutorial-icon-box bg-purple-lt text-purple me-4 shadow-sm">
                                <i class="ti ti-shield-lock"></i>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold fs-4">6. Administration & Security</h3>
                                <p class="text-secondary mb-0">Configure system preferences on the <strong>Settings page</strong>, and monitor system changes via the <strong>Audit & Activity Logs</strong>.</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- STEP 2: Topics Grid -->
            <div class="modal-body py-5 px-4 px-md-5 d-none tutorial-step" id="tutorial-step-topics">
                <div class="text-center mb-5">
                    <h2 class="fw-bold text-dark mb-2">{{ __('tutorial.what_to_explore') }}</h2>
                    <p class="text-secondary">{{ __('tutorial.select_category') }}</p>
                </div>

                <div class="row g-4">
                    @foreach(config('tutorial.topics', []) as $key => $topic)
                    <div class="col-md-4">
                        <div class="card topic-card h-100 shadow-sm bg-{{ $topic['color'] }}-lt cursor-pointer tutorial-topic-trigger" data-topic="{{ $key }}">
                            <div class="card-body text-center p-4">
                                <div class="icon-wrapper bg-{{ $topic['color'] }} text-white mx-auto shadow">
                                    <i class="ti {{ $topic['icon'] }}"></i>
                                </div>
                                <h4 class="fw-bold text-dark">{{ $topic['label'] }}</h4>
                                <p class="text-secondary small mb-0">{{ $topic['subtitle'] }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- STEP 2.5: Sub-topics List (Dynamic) -->
            <div class="modal-body py-4 px-4 px-md-5 d-none tutorial-step" id="tutorial-step-subtopics">
                <div class="mb-4">
                    <button type="button" class="btn btn-ghost-secondary btn-sm" id="tutorial-btn-back-to-topics">
                        <i class="ti ti-arrow-left me-1"></i> {{ __('tutorial.back_to_categories') }}
                    </button>
                </div>
                
                <div class="text-center mb-4">
                    <div id="subtopics-icon-wrapper" class="icon-wrapper text-white mx-auto shadow mb-3" style="width: 60px; height: 60px; line-height: 60px; font-size: 24px;">
                        <i id="subtopics-icon" class="ti"></i>
                    </div>
                    <h2 class="fw-bold text-dark mb-1" id="subtopics-title">Category Name</h2>
                    <p class="text-secondary" id="subtopics-subtitle">Category description</p>
                </div>

                <div class="list-group list-group-flush shadow-sm rounded border" id="subtopics-list">
                    <!-- Populated via JS -->
                </div>
            </div>

            <!-- STEP 3: Contextual Guide -->
            <div class="modal-body py-5 px-4 px-md-5 d-none tutorial-step" id="tutorial-step-context">
                <div class="text-center mb-5">
                    <div class="icon-wrapper bg-primary text-white mx-auto shadow mb-3">
                        <i class="ti {{ $contextIcon }}"></i>
                    </div>
                    <h2 class="fw-bold text-dark mb-2">{{ $contextTitle }}</h2>
                    <p class="text-secondary">{{ $contextDesc }}</p>
                </div>

                <div class="row">
                    <div class="col-md-10 offset-md-1">
                        <ul class="step-list list-unstyled">
                            @foreach($contextSteps as $index => $step)
                            <li class="d-flex align-items-start mb-4">
                                <div class="step-number bg-blue text-white fw-bold rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-3 shadow-sm" style="width: 32px; height: 32px; font-size: 14px;">
                                    {{ $index + 1 }}
                                </div>
                                <div class="step-text pt-1 text-dark fs-4">
                                    {!! $step !!}
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Footer with Navigation -->
            <div class="modal-footer bg-light border-top-0 py-3 px-4 d-flex justify-content-between align-items-center" style="border-radius: 0 0 20px 20px;">
                <button type="button" class="btn btn-outline-secondary btn-pill px-4 fw-medium" id="tutorial-btn-skip" >
                    {{ __('tutorial.skip_tutorial') }}
                </button>
                <button type="button" class="btn btn-outline-secondary btn-pill px-4 fw-medium d-none" id="tutorial-btn-back" >
                    {{ __('tutorial.back') }}
                </button>
                
                <button type="button" class="btn btn-primary btn-pill px-4 fw-medium" id="tutorial-btn-next" >
                    {{ __('tutorial.next') }} <i class="ti ti-arrow-right ms-2"></i>
                </button>
                <button type="button" class="btn btn-success btn-pill px-4 fw-medium d-none" id="tutorial-btn-finish" >
                    {{ __('tutorial.got_it') }} <i class="ti ti-check ms-2"></i>
                </button>
            </div>
        </div>
    </div>
</div>


