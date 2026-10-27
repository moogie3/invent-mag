<?php

namespace App\Services\Concerns;

/**
 * One shared implementation of list search for every paginated admin list.
 *
 * Why a trait and not a copy-pasted block per service: search has to behave
 * identically everywhere or the lists become impossible to reason about —
 * different fields, different wildcard handling, and different interactions
 * with the tenant scope on different pages. Centralising it means the escaping
 * rules, the empty-term handling and the OR-grouping live in exactly one place.
 *
 * Usage:
 *
 *     $this->applyListSearch($query, $filters, ['invoice', 'status']);
 *     $this->applyListSearch($query, $filters, ['invoice'], ['customer' => ['name']]);
 *
 * The predicate is always wrapped in a single grouped `where(function () {})`.
 * That grouping is load-bearing: without it the first `orWhere` would bind to
 * the enclosing query and escape any `tenant_id` / soft-delete clause added
 * before the search — a cross-tenant data leak rather than a wrong-but-safe
 * result. See docs/FILTERING_PITFALLS.md #18 and #34.
 */
trait BuildsListSearch
{
    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array<string, mixed>  $filters  Request-derived filters; only 'search' is read here.
     * @param  list<string>  $columns  Columns on the model's own table, preferably table-qualified.
     * @param  array<string, list<string>>  $relations  relation name => columns on that relation's table.
     *                                                      Matched with `whereHas`, so it filters the parent
     *                                                      row rather than only the eager-loaded children.
     */
    protected function applyListSearch($query, array $filters, array $columns = [], array $relations = []): void
    {
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search === '' || ($columns === [] && $relations === [])) {
            return;
        }

        // Escape LIKE metacharacters so a literal "%" or "_" typed by the user
        // searches for itself instead of matching every row. Backslash is
        // MySQL's default LIKE escape character.
        $like = '%'.addcslashes($search, '%_\\').'%';

        $query->where(function ($outer) use ($columns, $relations, $like) {
            // The first predicate MUST be `where`, never `orWhere` — see the
            // class docblock on OR-grouping and the tenant scope.
            $first = true;

            foreach ($columns as $column) {
                if ($first) {
                    $outer->where($column, 'LIKE', $like);
                    $first = false;

                    continue;
                }

                $outer->orWhere($column, 'LIKE', $like);
            }

            foreach ($relations as $relation => $relationColumns) {
                foreach ($relationColumns as $column) {
                    if ($first) {
                        $outer->whereHas($relation, fn ($q) => $q->where($column, 'LIKE', $like));
                        $first = false;

                        continue;
                    }

                    $outer->orWhereHas($relation, fn ($q) => $q->where($column, 'LIKE', $like));
                }
            }
        });
    }
}
