@php
    // $routeName is passed from the route closure (not Route::currentRouteName())
    // so this partial stays correct after every Turbo navigation.
    $tutorialService = app(\App\Services\TutorialService::class);
    $context = $tutorialService->getContextForRoute($routeName);
    
    $contextTitle = __($context['title']);
    $contextIcon = $context['icon'];
    $contextDesc = __($context['desc']);
    $contextSteps = array_map(fn($step) => __($step), $context['steps'] ?? []);
@endphp
<div class="text-center mb-5">
    <div class="icon-wrapper bg-blue-lt text-blue mx-auto shadow-sm mb-3" style="width: 64px; height: 64px; border-radius: 16px; font-size: 32px; display: flex; align-items: center; justify-content: center;">
        <i class="ti {{ $contextIcon }}"></i>
    </div>
    <h2 class="fw-bold text-dark mb-2">{{ $contextTitle }}</h2>
    <p class="text-secondary fs-4">{{ $contextDesc }}</p>
</div>
<div class="row">
    <div class="col-md-10 offset-md-1">
        <div class="card shadow-sm border-0 bg-blue-lt">
            <div class="card-body p-4">
                <h4 class="fw-bold mb-3"><i class="ti ti-bulb text-warning me-2"></i>{{ __('tutorial.quick_guide') }}</h4>
                <ul class="text-secondary fs-4 context-list ps-3 mb-0">
                    @foreach($contextSteps as $step)
                        <li>{!! $step !!}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
