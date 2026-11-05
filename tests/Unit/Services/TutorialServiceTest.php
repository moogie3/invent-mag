<?php

namespace Tests\Unit\Services;

use App\Services\TutorialService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
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

    /**
     * The "What would you like to explore?" grid must cover every page the
     * sidebar menu links to — when a feature gains a page, the tutorial has
     * to list it. Dashboard is deliberately excluded (always visible as the
     * first sidebar item, and it has its own contextual guide).
     */
    public function test_every_sidebar_page_appears_in_the_tutorial_grid(): void
    {
        $gridRoutes = [];
        foreach (config('tutorial.topics') as $topic) {
            foreach ($topic['items'] as $item) {
                if (isset($item['route'])) {
                    $gridRoutes[$item['route']] = true;
                }
            }
        }

        // The sidebar links the COA tree view while the grid links its
        // management view — same Chart of Accounts feature, two routes.
        $aliases = [
            'admin.accounting.chart' => 'admin.accounting.accounts.index',
        ];
        $excluded = ['admin.dashboard'];

        $sidebarRoutes = [];
        foreach (config('navigation.menu') as $item) {
            $routes = isset($item['children'])
                ? array_column($item['children'], 'route')
                : [$item['route'] ?? null];

            foreach ($routes as $route) {
                if ($route !== null && ! in_array($route, $excluded, true)) {
                    $sidebarRoutes[$route] = true;
                }
            }
        }

        $missing = [];
        foreach (array_keys($sidebarRoutes) as $route) {
            $effective = $aliases[$route] ?? $route;
            if (! isset($gridRoutes[$effective])) {
                $missing[] = $route;
            }
        }

        $this->assertSame(
            [],
            $missing,
            'Sidebar pages missing from the tutorial grid: '.implode(', ', $missing)
        );
    }

    /**
     * The modal swallows route() exceptions and renders a dead "#" link,
     * so a typo in the grid fails silently in production — catch it here.
     */
    public function test_every_tutorial_grid_route_exists(): void
    {
        $dead = [];
        foreach (config('tutorial.topics') as $category => $topic) {
            foreach ($topic['items'] as $item) {
                $route = $item['route'] ?? null;
                if ($route === null || ! Route::has($route)) {
                    $dead[] = ($route ?? '(no route)').' ('.$category.')';
                }
            }
        }

        $this->assertSame([], $dead, 'Dead routes in the tutorial grid: '.implode(', ', $dead));
    }
}
