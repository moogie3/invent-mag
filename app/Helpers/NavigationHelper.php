<?php

namespace App\Helpers;

/**
 * Which menu item owns the page being viewed.
 *
 * One rule for both navigation modes - the sidebar blade and the top-nav
 * blade call this, so the two can never disagree about where "you are here"
 * is. Two properties matter and both are inherited from the sidebar's
 * original inline logic:
 *
 * - The match is menu-sidebar.js's own active rule (exact path, or a path
 *   beneath it) rather than an exact route-name match, so a detail page such
 *   as admin.sales.show - a routeIs() miss - still marks its section, exactly
 *   where the script marks the child underneath it.
 * - The permission test runs last, so the Gate is consulted only for the one
 *   child whose path matched, and a parent is never marked for a child the
 *   user cannot see.
 */
class NavigationHelper
{
    /**
     * Does this menu item own the current page through one of its children?
     *
     * Items without children never own a page this way: a parent carries no
     * route of its own in config/navigation.php.
     *
     * $respectPermissions is false only for the top-nav "special" branches
     * (reports / accounting / administration), which deliberately render
     * their children with no @can check: the parent must then be marked by
     * the same rule that decides which children are on screen, or the panel
     * would show an unmarked parent over a marked child.
     */
    public static function hasActiveChild(array $item, bool $respectPermissions = true): bool
    {
        if (! isset($item['children'])) {
            return false;
        }

        foreach ($item['children'] as $child) {
            $routeName = isset($child['route']) ? $child['route'] : null;

            if (! self::pathMatchesRoute($routeName)) {
                continue;
            }

            if (! $respectPermissions || ! isset($child['permission']) || auth()->user()?->can($child['permission'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Does the named route own the current path: exactly, or as a path
     * prefix on a segment boundary? /admin/pos must not match /admin/po.
     */
    public static function pathMatchesRoute(?string $routeName, ?string $currentPath = null): bool
    {
        if (! $routeName) {
            return false;
        }

        $route = app('router')->getRoutes()->getByName($routeName);
        if (! $route) {
            return false;
        }

        $currentPath ??= request()->path();

        return $currentPath === $route->uri()
            || str_starts_with($currentPath, $route->uri().'/');
    }

    /**
     * The sidebar's own rule for a leaf item: an exact route-name match.
     */
    public static function isCurrentRoute(?string $routeName): bool
    {
        return $routeName !== null && request()->routeIs($routeName);
    }
}
