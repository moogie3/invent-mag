<?php

namespace Tests\Feature\Admin;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class SidebarMenuTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');
    }

    /**
     * The parent anchor the sidebar marks for the section owning the page.
     *
     * The match stops at the first </a>: a parent link nests only divs, spans
     * and icons, so the anchor of the section itself is the one returned.
     *
     * @return array<int, string>
     */
    private function markedParentAnchor(string $html): array
    {
        preg_match('/<a class="nav-link[^"]*has-active-child[\s\S]*?<\/a>/', $html, $matches);

        return $matches;
    }

    /**
     * Switch the user's navigation mode, keeping every other system setting
     * the settings screen already wrote (theme, language, ...).
     */
    private function useNavigationType(string $type): void
    {
        $this->user->system_settings = array_merge(
            $this->user->system_settings ?? [],
            ['navigation_type' => $type]
        );
        $this->user->save();
    }

    /**
     * The top-nav menu panel, extracted so the assertions below can never be
     * satisfied by some other part of the page (in 'both' mode the sidebar
     * renders too, and it carries the same class this feature uses).
     */
    private function topNavSection(string $html): string
    {
        preg_match('/<nav class="nav-dropdown[^>]*id="nav-dropdown"[^>]*>[\s\S]*?<\/nav>/', $html, $matches);

        return $matches[0] ?? '';
    }

    /**
     * Href of the child row marked as the current page inside the panel.
     */
    private function markedChildHref(string $section): string
    {
        preg_match('/<a class="dropdown-item active" href="([^"]*)"/', $section, $matches);

        return $matches[1] ?? '';
    }

    public function test_the_section_owning_the_current_page_is_marked_in_the_sidebar(): void
    {
        $html = $this->actingAs($this->user)
            ->get(route('admin.sales'))
            ->assertOk()
            ->getContent();

        // Exactly one parent may claim the page: a second would read as two
        // sections owning it.
        $this->assertSame(1, substr_count($html, 'has-active-child'));

        $anchor = $this->markedParentAnchor($html);
        $this->assertNotEmpty($anchor[0]);
        $this->assertStringContainsString('nav-link-title">Sales', $anchor[0]);
    }

    public function test_a_page_under_no_section_marks_no_parent(): void
    {
        $html = $this->actingAs($this->user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertSame(0, substr_count($html, 'has-active-child'));
    }

    public function test_a_page_beneath_a_child_marks_the_parent_when_no_child_route_matches(): void
    {
        // admin.po.create is neither the child's own route (admin.po) nor a
        // child of any other section - only its path sits under admin/po.
        // Marking it here is what keeps the parent in step with
        // menu-sidebar.js, which marks the child the same way.
        $html = $this->actingAs($this->user)
            ->get(route('admin.po.create'))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, substr_count($html, 'has-active-child'));

        $anchor = $this->markedParentAnchor($html);
        $this->assertNotEmpty($anchor[0]);
        $this->assertStringContainsString('nav-link-title">Purchasing', $anchor[0]);
    }

    public function test_a_similarly_named_path_marks_only_its_own_section(): void
    {
        // /admin/pos begins with the characters of /admin/po (Purchasing's
        // child) but is not beneath it: the path rule matches on the segment
        // boundary, never inside a segment. Purchasing must stay unmarked,
        // Sales must be marked through its own POS child.
        $html = $this->actingAs($this->user)
            ->get(route('admin.pos'))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, substr_count($html, 'has-active-child'));

        $anchor = $this->markedParentAnchor($html);
        $this->assertNotEmpty($anchor[0]);
        $this->assertStringContainsString('nav-link-title">Sales', $anchor[0]);
    }

    public function test_the_marked_parent_is_styled_in_both_sidebar_states(): void
    {
        // The class alone is only half the feature: it has to reach a rule in
        // each sidebar state, or the parent reads as unmarked the moment the
        // rail closes (or the moment it opens). Source, not the built bundle -
        // the bundle is git-ignored and absent from a fresh checkout, so
        // asserting on it would fail exactly where the guard is needed.
        $css = file_get_contents(resource_path('css/menu-sidebar.css'));

        $this->assertStringContainsString(
            '.sidebar:not(.collapsed) .navbar-nav .nav-link.has-active-child {',
            $css,
            'The expanded sidebar must paint the parent of the current page.'
        );
        $this->assertStringContainsString(
            '.sidebar.collapsed .navbar-nav .nav-link.has-active-child {',
            $css,
            'The closed sidebar (rail) must paint the parent of the current page too.'
        );
    }

    public function test_the_dark_theme_hands_the_marked_parent_its_glyph_back(): void
    {
        // dark.css pins every sidebar icon to a fixed grey with !important,
        // so the dark marked parent would show its primary tray under a grey
        // glyph - two stories on one row (audit finding #12). The override
        // has to exist, outrank the pin, and stay off the hover flash (which
        // paints the row near-white): source, not the built bundle, for the
        // same reason as the test above.
        $css = file_get_contents(resource_path('css/menu-sidebar.css'));

        $this->assertStringContainsString(
            '.nav-link.has-active-child:not(:hover) .nav-link-icon i',
            $css,
            'The dark marked parent must repaint its own glyph, but only while the row is not hovered.'
        );
        $this->assertStringContainsString(
            'color: color-mix(in srgb, var(--tblr-primary, #0054a6) 62%, #ffffff) !important;',
            $css,
            'The glyph must be a lifted primary (contrast on the dark tray) strong enough to beat dark.css\'s pin.'
        );
    }

    // ---------------------------------------------------------------------
    // Top-nav (navbar) mode: the same "you are here" claim, so both
    // navigation types tell the same story about the page being viewed.
    // ---------------------------------------------------------------------

    public function test_the_section_owning_the_current_page_is_marked_in_the_top_nav(): void
    {
        $this->useNavigationType('navbar');
        $html = $this->actingAs($this->user)
            ->get(route('admin.sales'))
            ->assertOk()
            ->getContent();

        // In navbar mode the sidebar is not rendered at all, so the whole
        // document must carry exactly one claim on the page.
        $this->assertSame(1, substr_count($html, 'has-active-child'));

        $section = $this->topNavSection($html);
        $this->assertNotEmpty($section, 'The top-nav menu panel must be on the page.');

        $anchor = $this->markedParentAnchor($section);
        $this->assertNotEmpty($anchor[0], 'The Sales section must wear the mark.');
        $this->assertStringContainsString('href="#navbar-sales"', $anchor[0]);

        // And the mark must sit on the Sales item's own row, not on some
        // later anchor that happens to follow it in the markup.
        $this->assertMatchesRegularExpression(
            '/id="sales-nav-item"[\s\S]{0,300}?has-active-child/',
            $section
        );
    }

    public function test_the_current_child_row_is_marked_in_the_top_nav(): void
    {
        $this->useNavigationType('navbar');
        $section = $this->topNavSection(
            $this->actingAs($this->user)
                ->get(route('admin.sales'))
                ->assertOk()
                ->getContent()
        );

        // Exactly one row in the panel may claim to be the current page.
        $this->assertSame(1, substr_count($section, 'dropdown-item active'));
        $this->assertSame(route('admin.sales'), $this->markedChildHref($section));
    }

    public function test_a_leaf_page_marks_its_own_link_in_the_top_nav(): void
    {
        // Product has no children: the claim lands on the link itself, never
        // on a parent that does not exist.
        $this->useNavigationType('navbar');
        $section = $this->topNavSection(
            $this->actingAs($this->user)
                ->get(route('admin.product'))
                ->assertOk()
                ->getContent()
        );

        $this->assertSame(0, substr_count($section, 'has-active-child'));
        $this->assertSame(1, substr_count($section, 'class="active"'));

        preg_match('/<li><a href="([^"]*)" data-turbo="true" class="active">/', $section, $matches);
        $this->assertSame(route('admin.product'), $matches[1] ?? '');
    }

    public function test_a_page_under_no_section_marks_no_parent_in_the_top_nav(): void
    {
        $this->useNavigationType('navbar');
        $html = $this->actingAs($this->user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertSame(0, substr_count($html, 'has-active-child'));
    }

    public function test_a_special_section_marks_its_parent_and_child_in_the_top_nav(): void
    {
        // Reports is one of the three "special" branches (reports, accounting,
        // administration) whose children render with no @can check - the parent
        // is then marked by the same rule that decides which children are on
        // screen, or the panel would show an unmarked parent over a marked row.
        $this->useNavigationType('navbar');
        $section = $this->topNavSection(
            $this->actingAs($this->user)
                ->get(route('admin.reports.recent-transactions'))
                ->assertOk()
                ->getContent()
        );

        $this->assertSame(1, substr_count($section, 'has-active-child'));
        $this->assertMatchesRegularExpression(
            '/id="reports-nav-item"[\s\S]{0,300}?has-active-child/',
            $section
        );
        $this->assertSame(
            route('admin.reports.recent-transactions'),
            $this->markedChildHref($section)
        );
    }

    public function test_in_both_modes_the_page_keeps_exactly_one_owner(): void
    {
        // 'both' renders the sidebar AND the top bar. The page belongs to the
        // sidebar there (it is the one with the rail states the settings call
        // "opened"), so the bar must stay silent: two claims in one document
        // would read as two sections owning the same page.
        $this->useNavigationType('both');
        $html = $this->actingAs($this->user)
            ->get(route('admin.sales'))
            ->assertOk()
            ->getContent();

        $section = $this->topNavSection($html);
        $this->assertNotEmpty($section, 'The top-nav panel renders in both mode too.');
        $this->assertSame(0, substr_count($section, 'has-active-child'));
        $this->assertSame(0, substr_count($section, 'dropdown-item active'));

        $this->assertSame(1, substr_count($html, 'has-active-child'));
        $anchor = $this->markedParentAnchor($html);
        $this->assertStringContainsString('nav-link-title">Sales', $anchor[0]);
    }

    public function test_the_top_nav_mark_is_painted_by_its_own_rules(): void
    {
        // Same guard as the sidebar's styling test: a class with no rule
        // behind it is invisible. Source, not the built bundle - the bundle is
        // git-ignored, so asserting on it would fail on a fresh checkout.
        // The rules are scoped to the top-nav panel on purpose: the panel is
        // white-on-near-black in BOTH themes, so it needs its own register
        // (weight + inset underline) rather than the sidebar's primary text.
        $css = file_get_contents(resource_path('css/navbar.css'));

        $this->assertStringContainsString(
            '#nav-dropdown > ul > li > a.has-active-child',
            $css,
            'The top-nav parent of the current page must be painted.'
        );
        $this->assertStringContainsString(
            '.nav-dropdown .dropdown-menu .dropdown-item.active {',
            $css,
            'The current page\'s row inside an open top-nav section must be painted.'
        );
    }
}
