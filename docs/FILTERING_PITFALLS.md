# Database & List Filtering — A Catalogue of Common Project-Killing Problems

Every problem in this file is the *same shape* as the two production bugs
documented in [`CHANGELOG.md`](./CHANGELOG.md) and
[`INTERVIEW_QA.md`](./INTERVIEW_QA.md) §54:

> **The filter looked correct. It returned no error. It returned a plausible
> answer. The answer was wrong, or the query was ruinous, and nothing in the
> stack complained.**

Read the two anchor bugs first if you haven't:

| Anchor bug | Shape |
|---|---|
| **View composer ran 61× per page** | The unit of *work* (request) didn't match the unit of *execution* (view). A multiplier. |
| **Search filtered only page 1 of 6** | The filter's *universe* was the current page, not the catalog. Silent wrong answer. |

The unifying principle for everything below:

> **A filter can only ever be correct about the data it was given. Every bug
> here is either (a) the filter was handed the wrong universe, (b) the filter's
> state was reconstructed from an incomplete copy, or (c) the database could
> not execute the predicate the way the developer assumed it would.**

There is a [quick-reference table](#quick-reference) at the end.

---

## Part I — The Wrong Universe

*The filter is correct. What it was pointed at is not the whole dataset.*

### 1. Client-side filter over a server-paginated table

**The anchor bug.** Full write-up in §54.2.

The DOM contains page 1. The catalog lives in MySQL. A `textContent.includes()`
loop can only ever match what the server chose to send.

```js
// WRONG — tableRows is 10 rows out of 56
const rows = document.querySelectorAll("#invoiceTableBody tr");
input.addEventListener("keyup", () => rows.forEach(r =>
    r.style.display = r.textContent.includes(input.value) ? "" : "none"));
```

**Fix.** Move the predicate to the database and swap the table region. Never
filter in the browser when the list is paginated server-side.

**Detection.** If the list has a paginator *and* a client-side filter, it is
broken by construction. There is no version of this that works.

---

### 2. Filtering a loaded collection instead of the query

```php
// WRONG — loads 50,000 rows into PHP, then throws 49,990 away
$products = Product::where('tenant_id', $tid)->get()
    ->filter(fn ($p) => str_contains(strtolower($p->name), $term))
    ->values();
```

Two failures, and the second one is the trap:

- **Memory.** 50k Eloquent models ≈ hundreds of MB. `Allowed memory size
  exhausted` at 3 a.m.
- **It cannot be paginated.** `->filter()` returns a `Collection`. A
  `Collection` has no `paginate()`. So the developer reaches for
  `collect()->forPage()` and now every page load re-runs the full scan.

**Fix.** Express it as SQL. Eloquent's query builder exists for exactly this.

```php
$products = Product::where('tenant_id', $tid)
    ->where(fn ($q) => $q->where('name', 'like', "%{$term}%")
                         ->orWhere('sku', 'like', "%{$term}%"))
    ->paginate(10);
```

**Rule of thumb.** `->get()` followed by `->filter()` / `->map()` /
`->reject()` / `->sortBy()` on a table that has a paginator is a bug unless the
dataset is provably small *and* bounded.

---

### 3. Filtering *after* pagination — totals go wrong

```php
// WRONG — paginator counted 53 rows, the view shows 4
$sales = $service->getSalesIndexData($filters, $entries); // 53 total
$rows  = $sales->getCollection()->filter(fn ($s) => matches($s, $term));
```

`$sales->total()` still says 53. The footer renders *"Showing 1 to 4 of 53
entries"*. The user concludes pagination is broken, or that the search is
broken, when actually **two correct things were composed incorrectly**.

**Fix.** The predicate must be inside the query that also produces the count,
because `paginate()` runs its `COUNT(*)` with the *same* `WHERE` builder. Push
the filter down.

**This is the single most common variant of the anchor bug.** If you ever find
yourself calling `->filter()` on a paginator's collection, stop — you are
about to display a count that disagrees with the rows.

---

### 4. Filtering the parent, paginating the child (or vice versa)

```php
// WRONG — paginating customers, filtering on a relationship
$customers = Customer::with('sales')
    ->where(fn ($q) => $q->whereHas('sales', fn ($s) => $s->where('status', 'paid')))
    ->paginate(25);
```

This is actually *correct* — but the adjacent mistake is not:

```php
// WRONG — `with` + constraint does NOT filter the parent
$customers = Customer::with(['sales' => fn ($q) => $q->where('status', 'paid')])
    ->paginate(25);
```

`with(['rel' => $constraint])` filters **only the eager-loaded rows**. Every
customer still appears; their `sales` collection is just missing the unpaid
ones. A developer who wants "customers who have a paid sale" gets "all
customers with a filtered relation" and the count is unchanged.

**The three tools, and what each actually does:**

| Call | Filters parent? | Loads relation? | Adds count column? |
|---|---|---|---|
| `with('sales')` | No | Yes | No |
| `with(['sales' => $fn])` | **No** | Yes (filtered) | No |
| `whereHas('sales', $fn)` | **Yes** | No | No |
| `withCount(['sales' => $fn])` | No | No | Yes |
| `whereHas(...)->withCount(...)` | **Yes** | No | Yes |

**Interview trap.** "`with()` with a closure" is the single most commonly
misunderstood Eloquent API. It looks like a filter. It is a projection.

---

### 5. `whereHas` + `withCount` disagreeing

```php
// WRONG — "2 paid sales" in the count column, but the row is here
// because it has an unpaid sale
Product::withCount(['sales as paid_sales_count' => fn ($q) => $q->where('status', 'paid')])
    ->whereHas('sales', fn ($q) => $q->where('status', 'unpaid'))
    ->paginate();
```

Each clause ran independently and both are "right". The UI implies a
relationship between the count and the row's presence that does not exist.

**Fix.** Make every relationship clause on a row express the *same* predicate,
or make the intent explicit in the column header.

---

### 6. JOIN fan-out corrupting aggregates

```sql
-- WRONG: a customer with 3 sales and 2 payments produces 6 rows
SELECT c.name, COUNT(o.id) AS orders, SUM(p.amount) AS revenue
FROM customers c
JOIN sales o ON o.customer_id = c.id
JOIN payments p ON p.paymentable_id = o.id
GROUP BY c.id;
```

`COUNT(o.id)` returns 6 for a customer with 3 orders (because each order
appears once per payment). `SUM(p.amount)` double-counts every payment. The
numbers look fine. They are not.

**Fixes, in order of preference:**

1. **Aggregate in subqueries, then join the results.** Each subquery is
   independently correct.
   ```sql
   SELECT c.name, oc.orders, pc.revenue
   FROM customers c
   LEFT JOIN (SELECT customer_id, COUNT(*) orders FROM sales GROUP BY customer_id) oc
          ON oc.customer_id = c.id
   LEFT JOIN (SELECT o.customer_id, SUM(p.amount) revenue
              FROM payments p JOIN sales o ON o.id = p.paymentable_id
              GROUP BY o.customer_id) pc ON pc.customer_id = c.id
   ```
2. **`COUNT(DISTINCT o.id)`** for counts — but note this does **not** fix
   `SUM()`, which has no `DISTINCT` form for a computed value.
3. **Separate queries.** Usually the right answer in an ERP. Two simple
   queries beat one clever one, and each is independently testable.

**Rule.** Any time you `GROUP BY` after more than one `JOIN`, count the rows in
your head for a record that has multiple children. If the number doesn't match,
you have fan-out.

---

### 7. Soft deletes leaking into counts

```php
// WRONG — count includes soft-deleted rows the list doesn't show
Product::withCount('orderItems')->paginate();
```

`withCount` **does** respect the global `SoftDeletingScope` on the related
model, so this specific case is fine — but the pattern fails once you mix in
`withTrashed()`, `onlyTrashed()`, or a `withCount` closure that calls
`withTrashed()`:

```php
Product::withCount(['orderItems as lifetime_items' => fn ($q) => $q->withTrashed()])
```

Now `lifetime_items` and the visible `orderItems` count differ on the same
row, with no indication why.

**Fix.** Decide deliberately whether a count includes history, name the column
to say so (`lifetime_items` vs `active_items`), and never let two counts on the
same row silently disagree.

---

## Part II — The Database Can't Execute the Predicate You Wrote

*The query is logically right and index-wise catastrophic. It works on staging
with 800 rows and takes 40 seconds in production with 400,000.*

### 8. Leading wildcard defeats the index

```sql
WHERE name LIKE '%elco%'    -- full table scan, always
WHERE name LIKE 'elco%'     -- index range scan ✓
```

A B-tree index is sorted. `LIKE 'elco%'` knows where `elco*` begins and ends.
`LIKE '%elco%'` does not — every row is a candidate.

**This is the unavoidable cost of substring search**, and it is why the
whole-catalog search in this project uses `LIKE '%term%'` knowingly: at
hundreds of thousands of rows it is fine, at tens of millions it is not.

**The upgrade path, in order:**

1. **Prefix search where possible** — SKU / code / invoice fields usually
   accept `term%`.
2. **`FULLTEXT` index** (MySQL, InnoDB). Best ROI. Requires
   `WHERE MATCH(col1, col2) AGAINST (? IN BOOLEAN MODE)`. Note minimum token
   length (`innodb_ft_min_token_size`, default 3) — "el" will not match.
3. **A trigram / n-gram index** — store all 3-grams of the string in a child
   table and join on those. Turns substring search into an equality join.
4. **A search service** (Meilisearch, Typesense, Elasticsearch, Algolia).
   Justified when you need typo tolerance, faceting, or sub-50 ms across
   millions of rows.
5. **A materialized `search_text` column** — `CONCAT_WS(' ', name, sku,
   description)` maintained by an observer, indexed. Cheap, and it collapses a
   5-column `OR` into a single-column scan.

**Do not reach for a search service before measuring.** `LIKE '%term%'` over
50k rows is ~20 ms. That is faster than a network round trip to Meilisearch.

---

### 9. `OR` across columns defeats the index

```sql
WHERE name LIKE '%x%' OR sku LIKE '%x%' OR description LIKE '%x%'
```

Even with a perfect index on each column, `OR` means MySQL must union the
candidate sets from all three — and with leading wildcards, all three are full
scans anyway. Three scans plus a dedup.

**Fixes:**
- A **composite index** only helps if the predicates can be satisfied by a
  range scan of that index — it does *not* help a multi-column `OR` of
  leading-wildcard `LIKE`s.
- The **`search_text` materialized column** (above) is the practical answer:
  one column, one index, one scan.
- `UNION` of three indexed `SELECT`s when the columns have real indexes and
  you need equality/prefix matching.

---

### 10. A function on the column, so the index is ignored

```php
// WRONG — MySQL cannot use an index on order_date here
->whereMonth('order_date', 10)->whereYear('order_date', 2026)
```

```php
// WRONG — same trap, spelled differently
->where(DB::raw('DATE(order_date)'), '2026-10-24')
->whereRaw('LOWER(name) = ?', [$term])
->where('UPPER(status)', 'PAID')
```

`WHERE MONTH(order_date) = 10` evaluates `MONTH()` for **every row** and then
compares. The index on `order_date` is unreachable — you cannot binary-search a
sorted column through a function.

**Fix — range scan on the raw column:**

```php
$from = '2026-10-01';
$to   = '2026-11-01';   // exclusive upper bound

->whereBetween('order_date', [$from, $to])          // if date-only
->where('order_date', '>=', $from)
->where('order_date', '<',  $to)                    // if datetime — see #26
```

For case-insensitive matching, fix it with **collation**, not `LOWER()`:

```php
->where('name', 'like', $term)   // uses utf8mb4_unicode_ci — already case-insensitive
```

**General rule.** If the left-hand side of a comparison is anything other than
a bare column, assume the index is gone and prove it with `EXPLAIN`.

---

### 11. `EXPLAIN` — the tool everyone skips

```sql
EXPLAIN SELECT ... WHERE ...
```

Read three columns: `type` (you want `ref`/`range`, not `ALL`), `rows` (the
estimate scanned), and `Extra` (anything containing `Using filesort`,
`Using temporary`, or `Using where` after a scan is a red flag).

**Make it a habit on every new filter.** A query that is correct on 800 rows
and `ALL`-scans 400,000 is a production incident with a fuse on it.

---

### 12. Deep pagination with `OFFSET`

```sql
SELECT * FROM sales ORDER BY id LIMIT 10 OFFSET 999990;
```

MySQL must generate and discard **1,000,000 rows** to return the last page.
`LIMIT 10 OFFSET 100` is fast. `LIMIT 10 OFFSET 100000` is not. The cost is
linear in `offset`, and it is paid on every page view.

**Fixes:**

1. **Keyset / cursor pagination** — the correct answer.
   ```sql
   WHERE id > :last_seen_id ORDER BY id LIMIT 10
   ```
   Constant cost regardless of depth. Laravel's `cursorPaginate()` implements
   this; the trade-off is that you cannot jump to an arbitrary page number.
2. **Lazy loading / infinite scroll** — hides the problem but doesn't remove
   it; still keyset under the hood.
3. **Cap the offset** — force search/refilter past page 50 rather than
   allowing page 9,999.
4. **Bound the window** — date-range filters cut the scanned set down.

**When to care.** Users who page past ~100 pages have a missing search feature,
not a pagination problem. Fix the search.

---

### 13. Unbounded `->get()` for exports and filter dropdowns

```php
// WRONG — renders 200k option tags into a <select>
$customers = Customer::orderBy('name')->get();
```

Two distinct failures:
- **Memory / response size.** A 200 MB HTML response nobody can open.
- **It is also a filter dropdown** — so it runs on *every* page that includes
  the form, which is usually every page.

**Fixes:**
- **Search-as-you-type endpoint** returning 20 matches (this is what the
  product/POS pickers do).
- **`pluck('name', 'id')`** instead of loading models — 10–50× less memory.
  Accessors are not invoked, so it is also much faster.
- **Paginate or chunk** the export:
  ```php
  Sales::query()->chunkById(1000, fn ($batch) => ...);  // never ->get()
  ```
- **`lazy()`** for streaming.

---

### 14. Missing composite index for the actual filter combination

A single-column index on `status` and another on `warehouse_id` will often be
used for *one* of them; MySQL picks the more selective one and filters the rest
row-by-row. A **composite** `(tenant_id, status, warehouse_id)` lets it range
scan on all three.

**Order matters**, and the rule is: **equality columns first, then range
columns, then sort.**

```sql
-- filters: tenant_id = ?, status = ?, order_date BETWEEN ? AND ?
INDEX (tenant_id, status, order_date)
```

Putting `order_date` before `status` wastes the index after the range begins.

**Multi-tenancy shortcut.** `tenant_id` almost always belongs as the **leading**
column of every index in a tenanted app, because every query filters on it.
Check your migrations: if the tenant key isn't leading, every index in the
application is doing extra work.

---

## Part III — String Matching Lies to You

### 15. Unescaped LIKE wildcards

```php
// WRONG — a user typing "100%" matches every row
->where('name', 'like', "%{$term}%")
```

`%` and `_` are wildcards. A search for `INV-000_1` matches `INV-00011`,
`INV-00091`, everything. A search for `50%` matches the entire table. Silent,
plausible, wrong.

**Fix (used in this project):**

```php
$escaped = addcslashes($search, '%_\\');
->where('name', 'like', "%{$escaped}%")
```

MySQL's default `LIKE` escape character is the backslash, so no `ESCAPE` clause
is needed unless `NO_BACKSLASH_ESCAPES` SQL mode is on — in which case add
`ESCAPE '\\'` explicitly and be aware it then changes string-literal parsing.

**Related:** `addcslashes` is the right tool here; `addslashes` /
`mysql_real_escape_string` are for SQL *quoting* and are the wrong layer — they
escape quotes but leave `%` and `_` live.

---

### 16. Collation surprises — case, accents, and "why doesn't my filter match"

```sql
WHERE name = 'ABC'      -- matches 'abc'      (utf8mb4_unicode_ci)
WHERE name = 'cafe'     -- matches 'café'     (unicode_ci: é ≈ e)
WHERE name = 'café'     -- ALSO matches 'cafe'
```

`_ci` collations fold accents as well as case. So:
- A search for `cafe` returns `café` — users think the filter is loose.
- A search for `café` also returns `cafe` — users think it's broken.
- A uniqueness constraint on an `_ci` column lets you store both `Admin` and
  `admin`.

And the reverse trap — a `_bin` or `_cs` collation silently excludes rows the
developer expected.

**Fixes.**
- Know your column collation. Check with `SHOW FULL COLUMNS FROM products;`.
- Need case-sensitivity? `COLLATE utf8mb4_bin` **in the query** (not the
  column) so you don't break every other query.
- Need accent-sensitivity without case-sensitivity? `utf8mb4_general_ci` vs
  `unicode_ci` differ here; or normalize in PHP with `iconv`/`Normalizer`.

**Indonesian-specific note.** Product names with `ö`, `é`, or Chinese/Javanese
script will behave differently under `_ci` than you expect. Verify against real
data, not ASCII test fixtures.

---

### 17. MySQL ignores trailing spaces in comparison

```sql
WHERE sku = 'ABC-1'     -- also matches 'ABC-1   ' (three trailing spaces)
```

For non-binary string comparison, trailing spaces are insignificant. `CHAR` is
right-padded. So:
- `where('sku', 'ABC-1')` matches rows whose stored value is longer.
- A **unique** constraint won't stop you storing both `'ABC-1'` and `'ABC-1 '`.
- `strlen()` in PHP says 5; MySQL says they're equal.

**Fix.** Validate and `trim()` **on input, at the request boundary** — not on
read, where you'll miss some paths. If it matters, use `CHAR` semantics
deliberately or `BINARY` comparison.

---

### 18. Multi-word search: "red pen"

Three different things a user can mean, and `LIKE '%red pen%'` only implements
one of them:

| User means | Correct predicate |
|---|---|
| Both words, any order | `LIKE '%red%' AND LIKE '%pen%'` |
| Exact phrase | `LIKE '%red pen%'` |
| Either word | `LIKE '%red%' OR LIKE '%pen%'` |

The common bug is implementing "either word" via `explode()` and a loop of
`orWhere()`, which then **overrides an unrelated `where()`** because `orWhere`
has lower precedence than you think:

```php
// WRONG — the tenant filter gets OR'd away
Product::where('tenant_id', $tid)
    ->where('active', true)
    ->orWhere('name', 'like', '%red%')   // ← now any tenant's 'red' matches
    ->orWhere('name', 'like', '%pen%')
    ->paginate();
```

**This is a data-leak bug, not just a logic bug.** The tenant scope is
bypassed and rows from other tenants appear.

**Fix — group the OR:**

```php
Product::where('tenant_id', $tid)
    ->where('active', true)
    ->where(function ($q) use ($terms) {
        foreach ($terms as $term) {
            $q->orWhere('name', 'like', "%{$term}%");
        }
    })
    ->paginate();
```

**Rule.** Never call `orWhere()` at the top level of a scoped query. Always
wrap a group of ORs in a closure. This applies to *every* multi-tenant,
multi-attribute filter you will ever write.

---

### 19. `NOT IN` / `!=` and NULL

```sql
WHERE status != 'cancelled'    -- NULL rows silently disappear
WHERE status NOT IN ('cancelled', 'draft')   -- returns NOTHING if any row has status NULL
```

`NULL != 'cancelled'` evaluates to `NULL`, which is not `TRUE`, so the row is
excluded. And `x NOT IN (...)` where the list contains a `NULL` yields `NULL`
for every row — **the result set is empty, with no error.**

**Fix.**

```php
->where(function ($q) {
    $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
})
->whereNotNull('status')->whereNotIn('status', ['cancelled', 'draft'])
```

**Detection.** A filter that suddenly returns zero rows on a table you know has
data is almost always NULL semantics, not an empty result.

---

## Part IV — Time and Boundaries

### 20. The off-by-one day

```php
// WRONG — "orders on 2026-10-24"
->whereBetween('order_date', ['2026-10-24', '2026-10-24'])
```

`order_date` is a `DATETIME`. `2026-10-24 00:00:00` and `2026-10-24 23:59:59`
both fall *inside* the literal range, so this works — until an order lands at
`2026-10-24 23:59:59.500` (MySQL rounds to `23:59:60`/next second in some
modes) or the column is `TIMESTAMP` in UTC.

**Fix — half-open interval, always:**

```php
->where('order_date', '>=', '2026-10-24 00:00:00')
->where('order_date',  '<', '2026-10-25 00:00:00')
```

Half-open `[start, end)` is correct for every boundary: consecutive periods
never overlap and never leave a gap. `whereBetween` is inclusive on both ends,
so two adjacent `whereBetween` periods **double-count the shared boundary**.

---

### 21. Timezone mismatch — the filter is right, the clock is wrong

Three clocks exist in almost every Laravel app and they frequently disagree:

1. `config('app.timezone')` — what `now()`, `today()`, and Carbon use.
2. The MySQL session tz — what `CURDATE()` and `NOW()` return in raw SQL.
3. The **user's** timezone — what the business actually means.

```php
// WRONG — a Jakarta user's "yesterday" computed in UTC
->whereDate('created_at', today())
```

An order placed at **2026-10-25 05:00 WIB** is **2026-10-24 22:00 UTC**. Under
a UTC app timezone, it appears in the wrong day's report — every day, for
every user, silently.

**Fix.**
- Store everything in **UTC**.
- Convert to the **tenant's** timezone at the boundary before computing ranges.
- Prefer `->whereBetween` with Carbon values built in the user's tz:
  ```php
  $tz  = $request->user()->timezone;      // 'Asia/Jakarta'
  $day = Carbon::parse($request->date, $tz);
  $from = $day->copy()->startOfDay()->utc();
  $to   = $day->copy()->addDay()->startOfDay()->utc();
  ```
- Set `config(['app.timezone' => 'Asia/Jakarta'])` **only** if you are certain
  nothing writes UTC. Mixed conventions are worse than either pure option.

**Indonesian note.** WIB (+7) has **no DST**, which makes this easier than
most markets — but it is still wrong relative to a UTC app timezone, and
tenants can span zones.

---

### 22. `DATE()` in the select, raw column in the where

```php
// WRONG — group by a formatted value, filter on the raw one
->selectRaw('DATE(order_date) as day, SUM(total) as revenue')
->where('order_date', '>=', $from)      // index-friendly, but...
->groupBy('day');
```

This is *fine* — until someone changes the `where` to `whereDate()` (#10) or
the `groupBy` to the raw column. Mixed granularity between `SELECT`, `WHERE`
and `GROUP BY` on the same temporal column is a recurring source of rows that
appear in one report and not another.

**Rule.** Pick one granularity per report and use it consistently in all three
clauses.

---

## Part V — State Reconstructed From an Incomplete Copy

*The sibling of the anchor bug: a value lives in one place and is rebuilt
somewhere else from a partial list.*

### 23. Filter state dropped on pagination / page-size change

**The anchor bug's sibling.** Fixed in this project.

```html
<!-- WRONG — rebuilds the query string from one param -->
<select data-onchange="window.location.href='?entries=' + this.value;">
```

```php
// WRONG — same bug server-side
return redirect()->route('sales.index', ['entries' => $request->entries]);
```

Both throw away `search`, `month`, `year`, `status`, and `sort`.

**Fix — carry the whole query string, then override one key:**

```js
const u = new URL(window.location.href);
u.searchParams.set('entries', value);
u.searchParams.delete('page');   // a new page size invalidates the offset
window.location.href = u.toString();
```

```php
return redirect()->route('sales.index', $request->except(['entries', 'page']));
```

**Audit this everywhere.** Grep for `window.location.href = '?'` and for
`redirect()->route(... , [` on any index route. Every occurrence is a
candidate.

---

### 24. Filter state not in the URL at all

If filtering mutates state held only in JS memory, then refresh loses it, the
back button lies, two tabs diverge, and a user cannot send a colleague a link to
the exact view they're looking at.

**Fix.** The URL is the state. Every filter writes to it (`replaceState` or
navigation), every filter reads from it on load.

This is the property the shared search module guarantees: searching updates
`?search=` via `history.replaceState`, so the address bar, the refresh button
and the bookmark all agree with what's on screen.

---

### 25. Sort state lost when filtering (and vice versa)

```php
// WRONG — filter form only carries filter params
return redirect()->route('product.index', $request->only(['search', 'entries']));
```

`?sort=total&dir=asc` is gone. The user sorted by total, filtered, and is now
looking at an unsorted list.

Same fix as #23: `$request->except(['page'])`.

---

### 26. Blank-string filters that silently match everything

```php
$filters = $request->only(['month', 'year', 'status', 'warehouse_id']);

if ($request->filled('status')) {
    $q->where('status', $request->status);
}
```

`filled()` is right. But a colleague writes:

```php
if ($request->has('status')) {           // ← 'has', not 'filled'
    $q->where('status', $request->status);   // status='' → WHERE status = ''
}
```

`$request->has('status')` is true for an empty string. The query becomes
`WHERE status = ''`, matching nothing. The user sees an empty list and assumes
there's no data.

**Fix.** `filled()` (non-empty string) or `->hasValid()` with a nullable rule.
And make the `<option value="">All</option>` genuinely empty — never
`value="all"` compared against a real column value.

---

### 27. Implicit AND when the user meant OR

Two dropdowns: Warehouse and Status. A user picking "Warehouse A" + "Status:
paid" almost always means *intersection* — and that's what you get. But on a
search box with several fields, users often expect *any field contains*.

The failure is shipping AND and documenting OR, or vice versa. Worse: shipping
both in different places, so the same two inputs behave differently on two
pages.

**Fix.** Decide once, state it in the UI ("Search in: name, SKU, description"),
and make every list behave identically. In this project the shared module means
there is exactly **one** search implementation to reason about.

---

## Part VI — Async, Concurrency and Caching

### 28. Out-of-order async responses

```js
// WRONG — the 'e' response can land AFTER the 'elco' response
fetch(`/search?q=${term}`).then(r => r.json()).then(render);
```

Type `elco` quickly and you may fire for `e`, `el`, `elc`, `elco`. If the `e`
query is slower (cold cache, lock contention) it resolves last and **overwrites
the correct results with the stale ones**. The UI shows results for `e` while
the box says `elco`.

**Fix — abort the previous request:**

```js
let controller = null;
async function run(term) {
    if (controller) controller.abort();
    controller = new AbortController();
    const res = await fetch(url, { signal: controller.signal });
    ...
}
```

Alternative: a monotonically increasing **request token**, and discard any
response whose token isn't the latest. Necessary when you can't abort (e.g. the
request has side effects).

---

### 29. No debounce — one request per keystroke

Each keystroke fires a full server round trip *and* a full table render. Ten
characters = ten queries plus ten re-renders, most thrown away.

**Fix.** 300 ms debounce. 250–400 ms is the accepted range: fast enough to feel
instant, slow enough that it fires once the user pauses.

Combine with #28 — debounce alone still lets a slow request finish after a fast
one if you don't also abort.

---

### 30. Stale closure over filter state

```js
// WRONG — rows captured once, at init
const rows = document.querySelectorAll("tr");
setTimeout(() => rows.forEach(...), 5000);  // rows are detached by then
```

This is exactly the `selectable-table.js` bug (#54.2 follow-up): a reference
captured at init outlives the DOM it points at.

**Fix.** Re-resolve on access (lazy getters), or re-bind after every swap. And
**never** re-run an initializer that attaches a `document`/`window` listener
without proving it's idempotent — the failure mode is a destructive action
firing twice.

---

### 31. Cache key missing a filter parameter

```php
// WRONG — page 2 and page 3 share a key
Cache::remember('sales_list_' . $request->only('status'), 60, fn () => ...);
```

`$request->only('status')` returns an **array**; interpolating it yields
`Array`, so every page/status/tenant combination collides onto one key. In a
multi-tenant app this is a **cross-tenant data leak**.

**Fix.**

```php
$key = 'sales_list.' . implode('.', [
    $tenantId, $status ?? '-', $entries, $page, md5($search ?? ''),
]);
```

**Rule.** Every variable that changes the response belongs in the key —
including **tenant ID**. Build cache keys with `md5(json_encode($params))` if
the param set is long; it's harder to get wrong by omission.

---

### 32. Cache never invalidated

Cached filter results that outlive the write that should have invalidated
them. Classic in a listing page: create a sale, come back, it isn't there.

**Fix.** Prefer `Cache::remember()` with a **short** TTL over a long TTL plus
an invalidation network. Tag-based invalidation (`Cache::tags([...])->flush()`)
works on Redis/Memcached but **not** on the `file` driver — a portability trap
that only shows up when you change drivers.

---

## Part VII — Tenancy, Authorization and NULL (the ERP-specific set)

### 33. Raw queries bypass the global tenant scope

Documented in this codebase's own §2.

```php
// WRONG — no tenant scope
DB::table('sales')->where('status', 'paid')->get();
```

Eloquent global scopes apply to `Model::` queries. **`DB::table()` bypasses
them entirely.** Forget the `where('tenant_id', ...)` once and you have a
cross-tenant data leak reachable from a filter form.

**Fixes.**
- Use `Model::query()` in preference to `DB::table()` wherever a model exists.
- Where you need raw SQL, wrap it in a helper that injects the tenant id, so
  there is one place to get right.
- **Test it.** A feature test that creates rows in two tenants and asserts a
  filter never returns the other tenant's rows is the only thing that catches
  this reliably.

---

### 34. `orWhere` escaping the tenant scope

Covered in #18 — worth repeating as its own entry because it is the same leak
via a different door, and it is far more common than the raw-query variant
because it looks like ordinary code.

---

### 35. Filter dropdown built from unscoped data

```php
// WRONG — the dropdown itself leaks other tenants' warehouse names
$warehouses = Warehouse::orderBy('name')->get();
```

A leak doesn't have to be in a table row. Every `<option>` in an unscoped
dropdown is a disclosure.

---

### 36. Authorization checked on the list but not the row

The index query is scoped, so the list is right — but a deep link to
`/sales/{id}` on another tenant's record succeeds because the `show()` method
never re-checked. Filtering correctly on the list does not imply correct
authorization on the detail.

**Fix.** Scope on every read (`findOrFail` against a scoped query), or a policy
that verifies tenancy. The list filter is not the security boundary.

---

## Quick Reference

| # | Problem | Symptom | One-line fix |
|---|---|---|---|
| 1 | Client filter over server pagination | Matches silently missing | Query the server, swap the table |
| 2 | `->get()->filter()` | Memory exhaustion, unpageable | Predicate in SQL |
| 3 | Filter after pagination | "Showing 1–4 of 53" | Push filter into the query |
| 4 | `with(...)` closure mistaken for a filter | Parent unfiltered | `whereHas()` filters the parent |
| 5 | `whereHas` + `withCount` disagree | Count contradicts presence | Same predicate in both |
| 6 | JOIN fan-out | Inflated COUNT/SUM | Aggregate in subqueries, or separate queries |
| 7 | Soft deletes in counts | Two counts disagree | Name the column for its semantics |
| 8 | Leading-wildcard `LIKE` | Full scan | FULLTEXT / search_text / search service |
| 9 | Multi-column `OR` | N scans + union | One indexed `search_text` column |
| 10 | Function on the column | Index ignored | Range scan on the raw column |
| 11 | Never ran `EXPLAIN` | Slow only in production | EXPLAIN every new filter |
| 12 | Deep `OFFSET` | Page 9,999 takes 40 s | Keyset / `cursorPaginate()` |
| 13 | Unbounded `->get()` | 200 MB response | `pluck()` / `chunkById()` / `lazy()` |
| 14 | Missing composite index | One column filtered, rest scanned | Equality cols first, then range, then sort |
| 15 | Unescaped `%` / `_` | Search matches everything | `addcslashes($s, '%_\\')` |
| 16 | Collation folding | `cafe` matches `café` | Know your column collation |
| 17 | Trailing-space equality | Duplicate keys stored | `trim()` at input |
| 18 | Top-level `orWhere` | **Tenant scope bypassed** | Group ORs in a closure |
| 19 | `!=` / `NOT IN` with NULL | Empty result, no error | `whereNull` OR, `whereNotNull` first |
| 20 | Inclusive date range | Boundary double-counted | Half-open `[start, end)` |
| 21 | Timezone mismatch | Records on the wrong day | Store UTC, range in user tz |
| 22 | Mixed date granularity | Rows in one report, not another | One granularity per report |
| 23 | Pagination drops filters | Search gone after page change | `$request->except(['page'])` |
| 24 | Filter state not in URL | Refresh loses view | URL is the state |
| 25 | Sort lost on filter | Order resets | Carry all params |
| 26 | `has()` instead of `filled()` | Empty list, no error | `filled()` / nullable validation |
| 27 | Implicit AND vs OR | Same inputs, different meaning | One convention, stated in UI |
| 28 | Out-of-order responses | Stale results overwrite fresh | `AbortController` or request token |
| 29 | No debounce | One request per keystroke | 300 ms + abort |
| 30 | Stale DOM closure | Shortcuts act on detached rows | Lazy getters, re-resolve on access |
| 31 | Incomplete cache key | **Cross-tenant leak** | Every varying param in the key, incl. tenant |
| 32 | Cache never invalidated | New record missing from list | Short TTL; remember `tags()` ≠ `file` driver |
| 33 | Raw query skips global scope | **Cross-tenant leak** | `Model::query()` or a tenant-injecting helper |
| 34 | `orWhere` escaping scope | **Cross-tenant leak** | Group ORs |
| 35 | Unscoped filter dropdown | Leaked names in `<option>` | Scope every lookup |
| 36 | List scoped, `show()` isn't | Direct link bypasses the list | Scope every read, not just the index |

---

## The Three Questions That Catch Almost All of These

Ask them on every filter you write or review:

1. **What is the exact universe this predicate runs against — the whole table,
   or whatever happened to be in memory/DOM right now?**
   *(Catches #1, #2, #3, #13.)*

2. **What happens to this query at 100× the current row count, and at page
   500? Run `EXPLAIN`.**
   *(Catches #8–#14.)*

3. **Does this predicate still hold its tenant/soft-delete/authorization
   scope when another clause is OR'd, joined, or raw?**
   *(Catches #18, #33, #34, #35, #36 — the data-leak family.)*

And the fourth question, which is really the meta-lesson of both anchor bugs:

4. **If this returns the wrong answer, will anything tell me?**
   *(If the honest answer is "no" — write the test that asserts a **count**,
   against a fixture **larger than one page**, before you ship it.)*
