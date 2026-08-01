<?php

namespace Tests\Unit\Services;

use App\Services\TutorialService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TutorialServiceTest extends TestCase
{
    protected TutorialService $tutorialService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tutorialService = new TutorialService;
    }

    public function test_it_returns_context_for_exact_route_match()
    {
        $context = $this->tutorialService->getContextForRoute('admin.dashboard');

        $this->assertEquals('tutorial.dashboard', $context['title']);
        $this->assertEquals('ti-dashboard', $context['icon']);
    }

    public function test_it_returns_context_for_wildcard_route_match()
    {
        $context = $this->tutorialService->getContextForRoute('admin.product.index');

        $this->assertEquals('tutorial.products_master', $context['title']);
        $this->assertEquals('ti-packages', $context['icon']);
    }

    public function test_it_respects_order_of_specificity()
    {
        // admin.product.create is defined BEFORE admin.product* in config
        $context = $this->tutorialService->getContextForRoute('admin.product.create');

        $this->assertEquals('tutorial.create_product', $context['title']);
        $this->assertEquals('ti-plus', $context['icon']);
    }

    public function test_it_returns_default_context_for_unknown_route()
    {
        $context = $this->tutorialService->getContextForRoute('unknown.route.name');

        $this->assertEquals('tutorial.help_tutorial', $context['title']);
        $this->assertEquals('ti-help', $context['icon']);
    }

    public function test_it_returns_default_context_for_null_route()
    {
        $context = $this->tutorialService->getContextForRoute(null);

        $this->assertEquals('tutorial.help_tutorial', $context['title']);
        $this->assertEquals('ti-help', $context['icon']);
    }
}
