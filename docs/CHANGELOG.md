# Invent-MAG — Change Log & Engineering Rationale

This file records **what changed, and why**. Entries are written for the next
engineer (or interviewer) reading the code cold: the symptom, the root cause,
the fix, and the reasoning behind the specific fix that was chosen over the
obvious alternatives.

Date-ordered, newest first.

---

## 2026-11-05 — Converted Sales Finally Reach the Journal (`finalizeConvertedSale()` Mirrors `createSale()` for Both Conversion Paths, and the `pending` Dead End Goes with It), e-Faktur Phase 2 (Direct DJP Submission via the Mekari Klikpajak PJAP — Contract, Two Queued Jobs, Five Idempotency Layers, Enterprise Gate: built to `docs/ROADMAP.md` §Phase 2 with seven deliberate deviations, each recorded here), and the "What would you like to explore?" Grid Caught Up with Every Sidebar Page (5 entries, en/id parity restored, two regression guards, and one assertion that was reading its own tutorial text), Closed Out with Two Conversion Edge Tests, the Mekari Klikpajak Subscription Disclosure, and Every `docs/` File Checked for Stale Claims)

This entry closes the one item yesterday's pass left open, ships the
roadmap's differentiator, and answers the *"we added a lot of pages — is
the tutorial menu complete?"* report — in that order. The three
workstreams share a shape: each was a path that **looked** finished from
the UI (the converted sale sits in the sales table, the Klikpajak card
renders, the tutorial grid opens without error) while being wrong or
incomplete underneath. Every claim below is backed by a test added this
pass. All work remains **uncommitted** — 64 files (52 modified, 12 new) —
git is the user's to run.

### Part 1 — Yesterday's open gap, closed: both conversion paths now post exactly what `createSale()` posts

**The decision (yesterday's open question): journal at conversion.**
Between "post the journal the moment the conversion commits" and "post it
when the sale leaves `pending`", the first was chosen: one moment of
truth, no status choreography, and both conversion paths become
structurally identical to `createSale()`. The alternative would have
required inventing a transition nobody owns — the exact defect the old
`pending` status already was.

**The fix is one shared method, not two parallel patches.**
`SalesService::finalizeConvertedSale(Sales $sale, array $lineItems)`
(`app/Services/SalesService.php:666`) mirrors `createSale()` step for
step:

1. **Stock is verified for every line before anything mutates**, so a
   shortage aborts the whole conversion (both call sites run it inside
   their transaction) with `createSale()`'s exact message:
   `Insufficient stock for product: {name}. Available: {a}, Requested: {r}`.
2. `SalesItem` rows are written and `ProductWarehouse` decremented.
3. **FIFO batch costing** — `POItem` by `expiry_date` then `created_at`,
   `remaining_quantity` decremented, remainder at product cost — the same
   query `createSale()` uses, so COGS cannot diverge between paths.
4. The four required accounts (Sales Revenue, AR, COGS, Inventory) are
   resolved from accounting settings or the whole conversion throws.
5. **Invoice journal**: Dr AR / Cr Revenue at **Gross** (`total +
   pph_amount` — the stored total is already net of PPh, so AR must carry
   what the customer owes), Dr COGS / Cr Inventory for the FIFO cost.
6. **PPN split** when `ppn_enabled && ppn_amount > 0`: credit PPN
   Keluaran and shrink the revenue credit to `gross − ppn` (same
   account-missing fallback as `createSale()`).
7. **PPh withholding entry** when `pph_enabled && pph_amount > 0`:
   Dr PPh Receivable / Cr AR.
8. **Credit-usage entry** at `customer_credit_used > 0` — conversions
   currently set it to 0 explicitly, so the branch is parity insurance,
   dead code by construction.

Both call sites now pass through it: `QuotationController::convertToSales`
(`:374`) and `SalesPipelineService::convertToSalesOrder` (`:414`). The
gates are carried on the sale's own fields — `ppn_enabled` /
`pph_enabled` are computed by each conversion with the same
`isIndonesian() && is_ppn / is_pph` rule every other writer uses, so the
journal never has to know about jurisdictions.

**Every cascade from yesterday's Part 2, resolved:**

| # | Yesterday's consequence | Now |
|---|---|---|
| 1 | GL revenue missed converted sales; statements and dashboards disagreed | invoice journal posted at conversion — `JournalEntry` rows and the sales table agree |
| 2 | Pipeline skipped COGS → GL inventory drifted from physical inventory | COGS pair posted from the same FIFO cost the stock move used |
| 3 | `addPayment` debited an AR that was never debited | AR debited at gross like `createSale()` |
| 4 | Quotation status `pending` was a dead end (no transition existed) | status is now **`Unpaid`** — what dashboards count — with `customer_credit_used = 0` written explicitly like every other path |
| 5 | Returns against such sales silently skipped reversal (no original journal → ratios 0) | the original journal exists → PPN/PPh/COGS reversal ratios compute |
| 6 | Pipeline wrote no `pph_rate`/`pph_amount` at all | written explicitly as `false`/`null`/`0` — an opportunity carries no PPh; the quotation path copies its gated values instead |

**Tests (+3):**

| File | Test |
|---|---|
| `SalesPipelineServiceTest` | `it_posts_a_balanced_invoice_journal_on_conversion` |
| `QuotationControllerTest` | `test_convert_to_sales_posts_ppn_split_journal_for_indonesian_tenant` |
| `QuotationControllerTest` | `test_convert_to_sales_copies_gated_pph_and_posts_withholding_journal` |

### Part 2 — e-Faktur Phase 2: direct DJP submission through the Mekari Klikpajak PJAP

Phase 1 gave CSV parity with Zahir. Phase 2 (`docs/ROADMAP.md` §Phase 2,
lines 1174–1665) is the differentiator — one-click submission, real
status from the DJP, no CSV, no desktop app — and it is now built:

| Roadmap § | Shipped |
|---|---|
| 2.2 contract | `app/Contracts/PjapInterface.php` — the five methods verbatim (`submit`, `submitBatch`, `checkStatus`, `cancel`, `testConnection`) |
| 2.3 service | `app/Services/Pjap/KlikpajakService.php` — full upload payload, `X-Idempotency-Key: FP-{id}-{nsfp}`, `retry(3, 1s)` on `submit()`, status normalization (`APPROVED`/`DITOLAK`/unknown/missing → `approved`/`rejected`/`pending`), batch map by `faktur_id`, `cancel`, `testConnection` (reachable + HTTP-failure variants) |
| 2.4 provider | `EFakturServiceProvider` binds `PjapInterface` **per tenant** from `klikpajak_api_key`/`klikpajak_secret`/`klikpajak_use_sandbox` + `services.klikpajak` URLs (sandbox default for safety) |
| 2.5 jobs | `SubmitFakturPajakJob` (`tries 3`, `backoff 300`, `timeout 60`, `WithoutOverlapping('faktur-{id}')`, attempt bookkeeping `error_attempt_{n}`, `failed()` → revert to `draft` + `failed_permanently`) and `PollFakturPajakStatusJob` (first poll +30s, re-poll every 5 min, `tries 10`; approved/rejected are terminal; after max polls the faktur stays `submitted`/`pending` for a manual check — exactly §2.5) |
| 2.6 queue | `tax` connection added to `config/queue.php` (opt-in) and every dispatch uses `->onQueue('tax-submissions')` |
| 2.7 UI | `show.blade.php` submit form behind `@planHas('efaktur_api')` **and** `isSubmittable()` **and** a configured API key; route `admin.efaktur.submit` behind `plan.feature:efaktur_api` + `authorize('create-efaktur')`; controller rejects non-draft and unconfigured tenants with dedicated messages before dispatching |
| plan gate | `efaktur_api` slug in `config/plans.php` (label + upsell line), `PlanSeeder` Enterprise features, migration `2026_11_04_000001_add_efaktur_api_feature_to_plans`, `PlanFeatureGateTest::ENTERPRISE_ONLY_FEATURES` extended |

**Idempotency — the five layers of §2.8:**

| Layer | Implementation |
|---|---|
| Database | `UNIQUE` on `nsfp` (Phase 1, unchanged) |
| HTTP | `X-Idempotency-Key: FP-{id}-{nsfp}` on every call (asserted in both the service and job tests) |
| Queue | `WithoutOverlapping('faktur-{id}')` |
| Application | the submit guard below (widened — deviation 2) |
| Transaction | `lockForUpdate()` on NSFP allocation (Phase 1, unchanged) |

**Seven deliberate deviations from the roadmap's literal code** (the spec
is prose with illustrative snippets; each of these was needed for a
reason the snippet cannot see):

| # | Roadmap says | Built | Why |
|---|---|---|---|
| 1 | `handle(PjapInterface $pjap)` method injection | `Tenant::find($id)?->makeCurrent()` then `app(PjapInterface::class)` **inside** `handle()` | queue workers have no HTTP tenant context; the container resolves the per-tenant binding *before* `handle()` runs, so method injection would bind a null tenant |
| 2 | guard on `isSubmittable()` | `pjap_submission_id !== null \|\| ! in_array(status, ['draft','submitted'])` | a `submitted` row **without** a `pjap_submission_id` is a failed attempt being retried — `isSubmittable()` would swallow every retry; idempotency of the repeat rests on the `X-Idempotency-Key` |
| 3 | `checkStatus()` returns a normalized array | throws `RuntimeException` on API failure | a failed check must retry, never fabricate a verdict or stamp `djp_response_at` with junk; the poll job re-throws and Laravel retries (`backoff 60`) with the faktur row untouched |
| 4 | `Authorization` + `X-Idempotency-Key` | plus **`X-API-Secret`** | Klikpajak requires the secret header |
| 5 | dispatch on a dedicated redis `tax` connection | `->onQueue('tax-submissions')` on the **default** connection; the `tax` connection ships as opt-in (`TAX_QUEUE_CONNECTION`) | existing workers and the sync test environment keep working unchanged; the dedicated-worker topology of §2.6 remains a one-env-var switch |
| 6 | register in `config/app.php` `providers` | `bootstrap/providers.php` | Laravel 11+ moved the provider list; same effect |
| 2.7-adjacent | one settings form | the **Klikpajak card posts on its own form** to `admin.efaktur.settings.save`, and `saveSettings()` gates all jurisdiction logic on `$request->has('tax_jurisdiction')` | the card sends no jurisdiction fields; without a key-presence gate (`Request::has()` is key-presence — the same signal Fix D relied on) every Klikpajak save would null the tenant's jurisdiction |

**UI/settings + copy:** the settings page gained a Klikpajak card (API
key, API secret, sandbox toggle, hints) for Enterprise and an upsell
banner for plans without `efaktur_api`; CSV export stays as the
fallback on every plan. **14 new message keys × en/id**
(`efaktur_submit_to_djp`, `efaktur_queued_for_djp`,
`efaktur_klikpajak_*`, …).

**Tests (+35):**

| File | n | Proves |
|---|---|---|
| `tests/Feature/Admin/EfakturApiTest.php` | 9 | professional plan → upgrade redirect + no job; enterprise → job pushed **on `tax-submissions`** with the right faktur; non-draft and unconfigured rejected; Klikpajak form saves its own fields; a Klikpajak-only save **never touches `tax_jurisdiction`/NPWP/PKP/PPN rate**; settings card vs upsell; show-page button rendered only when enabled |
| `tests/Unit/Services/KlikpajakServiceTest.php` | 14 | exact URL/method/auth/idempotency/payload; 500 → throws after **3 attempts** (`retry(3, 1s)` retries 500s); 200-without-`submission_id` throws; status normalization ×4; `checkStatus` failure throws (1 attempt — no retry); `testConnection` ×3; batch map/failure/malformed; `cancel` true/false |
| `tests/Unit/Jobs/SubmitFakturPajakJobTest.php` | 6 | success → `submitted`/`pending_djp` + `retry_count` + delayed poll chained; skips when PJAP already holds it or state is terminal; failure → `error_attempt_1`, re-thrown, 3 HTTP attempts, no poll; a `submitted`-without-id row **resubmits**; `failed()` reverts to `draft` |
| `tests/Unit/Jobs/PollFakturPajakStatusJobTest.php` | 6 | approved → `djp_reference_number` + chain stops; rejected → terminal with reason; pending/missing → re-dispatch delayed, row otherwise untouched; no `pjap_submission_id` → silent; API failure → re-thrown, row untouched |

**Test-infrastructure facts worth recording** (each cost a debugging
round): `Http::fake()` **merges** stubs — the first matching stub wins
for the whole test, so one fake per test and `Http::sequence()` for
varying responses; `Queue::fake()` is **mandatory** in every job test
(phpunit runs `QUEUE_CONNECTION=sync`, and a delayed self-re-dispatch
would recurse); PHPUnit 11's `AssertionFailedError extends RuntimeException`,
so failure tests use the `$caught = null` flag pattern — `$this->fail()`
inside `catch (\RuntimeException)` would be swallowed by the catch.

### Part 3 — The tutorial grid: every sidebar page, and the two decisions asked before touching it

**The report was right: five pages were missing** from the "What would
you like to explore?" grid (the sidebar and the contextual per-route
guides had them; only the grid lagged):

| Missing page | Route | Added under |
|---|---|---|
| e-Faktur (PPN faktur list) | `admin.efaktur.index` | Operations |
| e-Faktur Settings (PKP/NSFP/Klikpajak) | `admin.efaktur.settings` | Configuration |
| Notifications | `admin.notifications` | Administration |
| Subscription Plan | `admin.setting.plan` | Configuration |
| Notification Settings | `admin.setting.notifications` | Configuration |

**Two decisions were asked first (user-answered), one opinion given:**

1. **Financial Health stays in Accounting** (my recommendation, taken):
   it is a live ledger *snapshot* with no period-select → generate →
   export flow, so it does not match the Reports category's contract;
   every Reports child carries its own `plan_feature` while Financial
   Health is gated on `accounting` + `view-accounting`, so moving it
   would mean re-gating for no gain; and its route is
   `admin.accounting.health` with a dashboard strip linking there.
2. **e-Faktur placement → Operations (list) + Configuration (settings)**
   — the grid's categories are thematic, not a menu copy, so a
   near-empty seventh card was avoided.
3. **Dashboard skipped** — always the first sidebar item, already has
   its own contextual guide; the grid exists for pages that need
   discovery.

**Edits:** `config/tutorial.php` gained the 5 entries; the modal's
step-1 Operations overview now names e-Faktur; `lang/en|id/tutorial.php`
`efaktur_klikpajak` was rewritten (it still said *"Coming Soon: Phase 2
will add…"* — Phase 2 shipped this session) and `efaktur_djp_tracking`
now describes the "Submit ke DJP" button plus the CSV fallback; the ID
file gained the four keys it was missing (`skip_tutorial`, `next`,
`got_it`, `back_to_categories`) — **en/id parity restored: 662/662**,
those four previously fell back to English (`fallback_locale = en`).

**Two regression guards (+2, in `TutorialServiceTest`) so this gap
cannot silently reopen:**

- `test_every_sidebar_page_appears_in_the_tutorial_grid` — walks
  `config/navigation.php` and fails with the missing route names. Two
  documented carve-outs: `admin.accounting.chart` ↔
  `admin.accounting.accounts.index` are the same Chart of Accounts
  feature on two routes (sidebar links the tree, grid links the
  manager), and `admin.dashboard` is excluded per decision 3.
- `test_every_tutorial_grid_route_exists` — the modal wraps `route()`
  in `try/catch` and renders a dead `#` link on exception, so a typo
  dies **silently in production**; the test makes it loud. (`admin.sales_pipeline.pipelines.index`
  / `opportunities.index` are JSON endpoints, not pages, and are
  correctly absent from both sides.)

### Part 4 — The two failures the new text produced: one real collision, one pre-existing flake

1. **`EfakturApiTest::show_page_renders_submit_button_only_when_enabled`
   — `assertDontSee('Submit ke DJP')` failed** because the rewritten
   `efaktur_djp_tracking` guide now quotes that exact label, and the
   tutorial modal renders on every page for every plan. The label is no
   longer a unique signal, so the test asserts on the **form action URL**
   (`route('admin.efaktur.submit', $faktur)`) for both see/dont-see —
   the button's presence, not its wording, is what the test means.
2. **`WarehouseServiceTest::it_returns_error_if_updating_to_second_main_warehouse`
   — random-order flake, pre-existing and unrelated.**
   `updateWarehouse()` checks *duplicate name* (line 65) **before**
   *second main* (line 69), and `WarehouseFactory` names come from
   `faker->word` — when both warehouses draw the same word, the wrong
   error wins (`'This warehouse already exists.'`). The test never
   pinned its names; it now does (`Main Warehouse` / `Branch Warehouse`).
   The service's check order was left alone — name-first is defensible
   pre-existing behaviour, not this session's to reorder.

### Part 5 — Hardening pass: two conversion edge tests, the Mekari subscription disclosure, and every `docs/` file brought current

**Two edge tests, both driving the real conversion path.** Added to
`tests/Feature/Admin/QuotationControllerTest.php` (file now 33 tests),
each POSTs to `route('admin.quotations.convert-to-sales')` and exercises
the real `QuotationController::convert()` → `SalesService::finalizeConvertedSale()`
stack — no application code mocked, arithmetic asserted against the same
accounting-settings keys the implementation reads:

1. `test_convert_to_sales_keeps_gross_in_revenue_when_ppn_keluaran_account_is_missing`
   — ID tenant + `is_ppn` tax, but the default `CreatesTenant` settings
   carry **no** `ppn_keluaran_account_id` (the test asserts that
   precondition so it can never silently pass on a future settings
   change). The e-Faktur snapshot still travels with the sale
   (`ppn_enabled`, `ppn_amount = 110`), yet the journal is the plain
   four-line form — Dr AR 1110 / Cr Revenue 1110 (VAT left inside
   revenue) / Dr COGS 400 / Cr Inventory 400, balanced — pinning
   `createSale()`'s documented account-missing fallback at the
   conversion call site instead of failing the whole conversion.
2. `test_convert_to_sales_for_non_indonesian_tenant_posts_plain_four_line_journal`
   — US tenant: the PPN gate is jurisdiction **and** tax `is_ppn`, so
   no snapshot applies (`ppn_enabled`/`pph_enabled` false, amounts 0),
   exactly **one** journal entry with four transactions, and
   AR = revenue = the full 1110 total with no VAT split or withholding.
   A converted sale for a non-ID tenant must be indistinguishable from
   any other non-ID sale.

**Mekari subscription disclosure (user-approved copy).**
`efaktur_klikpajak_card_hint` (en + id) now states that production
direct submission requires an **active Mekari Klikpajak subscription —
separate from and on top of the tenant's Invent-MAG plan**. Previously
the card only said credentials are "issued by Mekari Klikpajak", which
left the paid-subscription requirement to be discovered at go-live. Free
paths (CSV export, sandbox testing) are unaffected.

**Docs pass — all 16 `docs/*.md` files reviewed; 7 needed updates:**

| File | What changed |
|---|---|
| `ROADMAP.md` | Phase 2 heading → `✅ COMPLETED (2026-11-05)` with the full status block: 7-deviation table, external-dependency note, deployment prerequisites, **provider-swap procedure** (code-level via `EFakturServiceProvider`, UI selector deliberately deferred), §2.8 as-implemented footnote; TOC anchors fixed (Phase 1 was broken too); `efaktur_api` moved from "slug to add" → live; competitive table row → `✅ Enterprise`; advantages/gaps list updated |
| `FEATURE_CHECKLIST.md` | both Phase 2 boxes → `[x]` with the shipped details; pipeline-conversion line now records that both conversion paths journal at conversion |
| `PPN_EVOLUTION.md` | TOC + heading → *Completed*; deliverables table gains per-item status (✅ client/submit/poll/cancel/batch — ❌ OAuth2, Faktur Pengganti, NSFP auto-fetch); API-flow diagram annotated with the as-built request shape; backlog row → done-with-caveats |
| `ARCHITECTURE_ASSESSMENT.md` | market-fit row + gaps paragraph: Phase 2 no longer "remains planned" |
| `INTERVIEW_QA.md` | Q24.6 rewritten from planned → shipped (incl. the Mekari dependency bullet); `efaktur_api` gating note, `klikpajak_api_key` schema note, and status-flow line updated; Q21.1 conversion answer now mentions the journal-at-conversion |
| `TECHNICAL_SPECIFICATION.md` | "Committed Future Enhancements" → shipped pointer; §9.3 Future(Phase 2) block → as-built (jobs, poll cadence, gating) |
| `STRUCTURE.md` | the changeset's new files added to the tree: `app/Contracts/PjapInterface.php`, `app/Jobs/` (both new jobs **and** the pre-existing `DownloadShopeeCoverImages.php` the tree had never listed), `EFakturServiceProvider`, `Services/Pjap/KlikpajakService`, and the 6 new test files |

**Deliberately untouched:** `DEPLOYMENT_CHECKLIST*.md` (both are
release-scoped snapshots — "Applies to the changeset that…", "Aug 2026"
/ "2026-10" — and a Phase-2 deployment note would misrepresent their
scope; the live prerequisites live in ROADMAP §4's status block and in
the Deployment note below), `TAXATION_SYSTEM.md` / `TAXATION_IMPROVEMENT.md`
(factual/historical — zero Phase 2 claims), the setup guides,
`FILTERING_PITFALLS.md`, `LANDING_PAGE_CMS.md`, and `ACTUAL_TREE.txt`
(historical snapshot). `CHANGELOG.md` is this entry. A final sweep
(`grep` for `not yet live|remains planned|🔜|PLANNED:`) shows only two
intentional hits: the roadmap's future PPh 21 line and a struck-through
gap item.

### Verification

- **`php artisan test` (PHP 8.5.12): EXIT 0 — 1,356 tests / 4,687
  assertions, 0 failures** (317s). Delta over yesterday's 1,314/4,482 is
  exactly **+3 (Part 1) + 35 (Phase 2) + 2 (tutorial) + 2 edge tests
  (Part 5) = +42**.
- **Full PHPUnit on PHP 8.2 (`/opt/lampp/bin/php -d memory_limit=2048M
  vendor/bin/phpunit`): OK (1,356 tests, 4,687 assertions), 0
  deprecation lines** (5m11s) — the vendor deprecation patch from
  yesterday's entry still holds.
- `npm run build` clean.
- **Live DB intact (re-verified after Part 5):** tenant 3
  `tax_jurisdiction = 'ID'`, 16
  opening-balance rows, 1 opening journal entry. The Phase 2 migration
  **was missing on live** and was run this session: `efaktur_api` now
  `starter=0 / professional=0 / enterprise=1`.
- All work **uncommitted** (64 files: 52 modified, 12 new) by design —
  the user runs git.

**Still open (unchanged from yesterday, reported not actioned):** (1)
composer advisories / the Laravel ≥12.60 upgrade decision — also the
permanent home of the vendor deprecation patch (it reverts on the next
`composer install`/`update`); (2) pre-existing:
`updatePurchaseReturn` never touches the journal, returns have no
PPN/PPh entry UI. **Closed since yesterday:** converted sales never
posting journals (Part 1 above).

**Deployment note for Phase 2:** jobs dispatch onto the default
connection's `tax-submissions` queue — production needs a worker
listening on that queue name (or `TAX_QUEUE_CONNECTION=tax` plus the
supervisor block from §2.6) before submissions run asynchronously;
otherwise they wait in the queue table.

---
## 2026-11-04 — The Cross-Module Consistency Verdict (Every PPh Writer Audited: Zero Ungated; the Non-ID Path Given Its First Five Tests, Each Proven Against a Reverted Fix), the One Real Gap Found and Left Open — Quotation- and Pipeline-Converted Sales Never Reach the Journal at All — and `php artisan test` on the System PHP Was Never About the Code: Two PHPs on One Machine, a Child Process That Ignores `-d`, a One-in-Fifty Factory Flake, and PHP 8.5's Deprecated PDO Constant

This entry answers the session's questions, in order: *is the accounting
consistent across every module that touches the journal, is the
opening-balance work still sound, why did `php artisan test` suddenly stop
working when it "used to just run fine"?, what to do about PHP 8.5's
deprecated-PDO-constant warnings in otherwise-green test output, and what
the two VS Code test trees ("Laravel Tests" / "PHPUnit Tests") actually
are?*

### Part 1 — The audit: how PPh is gated, and where it had never been tested

**The rule.** Withholding tax (PPh) applies only when **both** conditions
hold: the tenant's `tax_jurisdiction` is Indonesia (`isIndonesian()`), and
the selected tax row is actually a PPh tax (`is_pph`). The helpers that do
the arithmetic — `SalesHelper::calculatePphAmount` / `PurchaseHelper::calculatePphAmount`
— are deliberately **pure calculators**: they know nothing about tenants.
Gating belongs to the *writer*, the code that persists the result.

**Tally across `app/` (final, this pass):**

- **7 write sites** persist `'pph_amount' => …` — `SalesService` (create,
  update), `PosService`, `PurchaseService` (×2), `QuotationController`
  (store, update) — and the **7 paired `'pph_rate' => …`** sites. Every one
  is wrapped in `$isIndonesian && ($pphTax?->is_pph ?? false) ? … : 0|null`.
  (Two further occurrences of each key are the `float` cast declarations in
  the `Sales`/`Purchase` models — not writes.)
- **7 external `calculatePphAmount()` call sites**, every one guarded:
  `$pphApplied ? …Helper::calculatePphAmount(...) : 0`. The 4 remaining
  calls are *inside* the two helpers (one per calculator per helper) —
  internal, by design.
- **Ungated writers outside the helpers: 0.**

The journal itself carries **no PPh logic** — it posts whatever transactions
the writer hands it. That is the correct layering: one gate at each writer,
and every path (sale, POS sale, purchase, quotation store *and* update)
inherits the same rule.

**The coverage gap this audit exposed.** Every existing test exercised only
the **ID path** (gate on). Nothing anywhere proved the gate *off* — so a
regression that applied PPh to a US-tenant quotation would have passed CI.
Five tests were added, mirroring the `SalesServiceTest` pattern
(`$this->tenant->update(['tax_jurisdiction' => 'US'])` plus an ID control
test):

| File | Test |
|---|---|
| `QuotationControllerTest` | `test_store_does_not_apply_pph_for_non_indonesian_tenant` |
| `QuotationControllerTest` | `test_store_applies_pph_for_indonesian_tenant` (control) |
| `QuotationControllerTest` | `test_update_does_not_apply_pph_for_non_indonesian_tenant` |
| `PosServiceTest` | `it_does_not_apply_pph_on_pos_sale_when_tenant_not_indonesian` |
| `PurchaseServiceTest` | `test_it_does_not_apply_pph_for_non_indonesian_tenant` |

**Each test was proven to catch its bug**, not merely to pass: the gate was
temporarily reverted (PPh applied regardless of jurisdiction), the test was
run, and it failed with exactly the wrong numbers — quotation total
**1,090 instead of the required 1,110** (and `pph_amount` non-zero),
POS total **216 instead of 220**, purchase failing its assertion — then the
fix was restored (diff-verified identical) and everything went green again.

### Part 2 — The one real gap: converted sales never reach the journal (found, **not** fixed)

Probing "does *every* path that creates a `Sales` row post a journal?" found
the answer is **no** — and this was not previously known or documented.
There are exactly four `Sales::create()` sites:

| Path | Journal at creation? |
|---|---|
| `SalesService::createSale` | ✅ invoice entry (Dr AR / Cr Revenue, COGS pair, PPN Keluaran split for ID tenants) + optional PPh withholding + credit-usage entries |
| `PosService` | ✅ three entries (invoice, COGS, cash) |
| `QuotationController::convertToSales` | ❌ **none** |
| `SalesPipelineService::convertToSalesOrder` | ❌ **none** |

Consequences of the two ❌ paths, in cascade order:

1. **GL revenue misses the sale.** `FinancialReportingService` (and every
   other statement) reads *posted `JournalEntry` rows*; the sales dashboards
   read the `sales` table. A converted sale therefore appears in one and
   not the other — the two disagree by exactly the converted amount.
2. **Pipeline conversions also skip COGS.** `convertToSalesOrder` decrements
   `ProductWarehouse` directly (physical stock moves) but posts no
   Dr COGS / Cr Inventory — so **GL inventory diverges from physical
   inventory** for every pipeline order.
3. **Payments make it worse, not better.** `addPayment` posts Dr Cash /
   Cr AR for cash received — against an AR that was never debited.
4. **`status = 'pending'` is a dead end.** The quotation path sets it;
   nothing in the codebase ever transitions it (the dashboard only counts
   `Unpaid`/`Partial`, and `SalesController` contains no status handling at
   all — zero matches for "status").
5. **Returns against such sales can't reverse.** `SalesReturnService`
   reads the *original* journal to compute proportional PPN/PPh/COGS
   reversal ratios; no original journal → ratio 0 → the reversal lines
   silently skip.
6. **Pipeline conversions additionally write no `pph_rate`/`pph_amount`**
   at all — inconsistent with every other writer even before journaling.

**Not fixed here.** The fix is a design decision, not a patch: should the
conversion post the journal immediately (mirroring `createSale`), or should
the sale be journaled when it leaves `pending` (and who moves it)? Both
`convertToSales` and `convertToSalesOrder` are reported for exactly that
decision rather than silently choosing one.

### Part 3 — `php artisan test`: two PHPs, one machine, and a child process that ignores `-d`

**Symptom.** Plain `php artisan test` failed with `could not find driver
(Connection: sqlite … PRAGMA foreign_keys = ON)`, later an OOM fatal, later
still 15 `GD extension is not installed` errors — while the same suite ran
green moments earlier under a different binary.

**Root cause — there are two PHPs, and the system one was never equipped:**

| | XAMPP: `/opt/lampp/bin/php` (8.2.12) | Arch system: `/usr/bin/php` (8.5.12) |
|---|---|---|
| `pdo_sqlite` / `sqlite3` | bundled, enabled | **never installed** before 2026-11-03 21:40 |
| `gd` | bundled | still missing |
| `memory_limit` | **512M** | **128M** (the suite peaks ≈171M) |

`/var/log/pacman.log` covers the machine's entire life (it starts
2026-05-01, the build date): the php package was installed 2026-05-15
(8.5.6) and upgraded six times since, but **the first-ever
`pacman -S php-sqlite` entry is 2026-11-03T21:40:33 — run by the user,
following this session's instructions** (the two extension lines in
`/etc/php/php.ini` were uncommented four minutes later, `php.ini` mtime
2026-11-03 21:44). So the system `php` could **never** have run this suite
before — this is not a regression, and **`sudo pacman -Syu` is exonerated**:
the Nov 2 upgrade only took php 8.5.12-1 → 8.5.12-3 and touched neither
sqlite nor composer. The project's own history explains the memory of it
"just working": it began on **Windows XAMPP** (the `dump_binary_path` in
`config/database.php` still reads `C:/xampp/mysql/bin`), where these
extensions are enabled by default, and every green run on this machine —
including all of this session's — used XAMPP's PHP.

The shell history pins down the user's three attempts, all on the system
PHP: **Nov 3, 19:07 and 20:09** ("could not find driver"), then **21:48** —
after the sqlite install — which got far enough to die on the two remaining
walls (128M limit, no GD).

**The detail that made it deceptive:** collision's `artisan test` **always
spawns a child** — `Process([PHP_BINARY, 'vendor/phpunit/phpunit/phpunit',
…])` (or paratest under `--parallel`) — and the child does **not** inherit
the parent's `-d` flags. `php -d memory_limit=2G artisan test` raises the
*parent*; the child still reads `/etc/php/php.ini`. **php.ini is the only
lever** for the child.

**Verification without sudo:** `PHP_INI_SCAN_DIR=/etc/php/conf.d:<dir>`
with a two-line override (`memory_limit = 1024M`, `extension=…/gd.so` in a
scratch dir) reaches the child, which made the user's exact command
runnable end-to-end on PHP 8.5.12: **`php artisan test` → EXIT 0,
1,314 tests / 4,482 assertions, 0 failures** (302s). The gd `.so` came
from the same mirror as php-sqlite (`php-gd-8.5.12-3`, loaded via `-d` into
a scratch dir — no system files touched).

**Both commands have since been applied by the user** (verified this pass:
`gd`, `pdo_sqlite`, `sqlite3` all loaded; `memory_limit = 512M` at
`/etc/php/php.ini:430`; `extension=gd` at line 921):

```
sudo pacman -S php-gd
# then in /etc/php/php.ini:
#   line 921:  ;extension=gd        →  extension=gd
#   line 430:  memory_limit = 128M  →  memory_limit = 512M
```

so plain `php artisan test` now works out of the box — re-verified
end-to-end after the deprecation fix in Part 5: **EXIT 0, 1,314 tests /
4,482 assertions, 0 failures, zero deprecation lines** (157s). (The
XAMPP alternative, `/opt/lampp/bin/php artisan test`, never needed any of
it.)

### Part 4 — The one-in-fifty flake: `NotificationServiceTest`

**Symptom.** `it_can_get_due_notifications` failed intermittently —
`Failed asserting that actual size 6 matches expected size 5` at the
`assertCount(5, …)` — roughly one run in fifty, in suites otherwise green.

**Root cause — randomness in the factory, not in the service.** The test's
fourth product was created with a bare `Product::factory()->create()`. The
factory rolls `low_stock_threshold` ∈ [5, 20] *and* (in its `afterCreating`
hook) stock ∈ [0, 1000] — so with ≈1–2% probability that product itself
lands in the low-stock window, a **6th** "Low Stock Alert" notification
appears and the count assertion breaks. The service was never wrong.

**Proven, not guessed:** forcing the exact condition (a factory product
inside the window) reproduced the identical failure at line 69; the fix
makes the product deterministic — `withStock(500)` with
`low_stock_threshold = 10`, comfortably outside any window. Result:
**60/60 consecutive green runs** of the file (the flake previously fired
~1/50).

### Part 5 — PHP 8.5's deprecated PDO constant: purged from our config, then traced into the framework's own copy and eliminated at the source (zero deprecation output on both printers)

PHP 8.5 deprecates `PDO::MYSQL_ATTR_SSL_CA` (`use Pdo\Mysql::ATTR_SSL_CA
instead`). `config/database.php` read it eagerly while building both mysql
and mariadb option arrays — on every config load, i.e. every test.

**Fix (version-safe, behaviour-identical):** the options array is now built
**only when `MYSQL_ATTR_SSL_CA` is actually set** (otherwise it was an
empty-after-`array_filter` array anyway) and, when set, uses
`Pdo\Mysql::ATTR_SSL_CA` if that class exists (PHP ≥ 8.5) falling back to
the old constant (PHP < 8.5 — the XAMPP side). Verified on both: env set →
the attribute is passed, env unset → empty array, exactly as before.

**The vendor copy — traced and eliminated (same session, after the
user's green-but-noisy runs):** two notices still originated in
`vendor/laravel/framework/config/database.php` — the framework's own
copy. A backtrace pinned the chain: on **every** boot,
`LoadConfiguration::getBaseConfiguration()`
(`Illuminate/Foundation/Bootstrap/LoadConfiguration.php:191`) `require`s
**every** `*.php` in `vendor/laravel/framework/config/` — the app's
`config/database.php` overrides the vendor file's *values*, never its
*execution*. Three dead ends were established before the working fix:

- **Masking in `phpunit.xml` does not work.** A
  `<ini name="error_reporting" value="22527"/>` line (E_ALL minus
  E_DEPRECATED on both PHPs) was tried and **proved ineffective**:
  PHPUnit's `ErrorHandler::enable()` overwrites the level at run start
  (`ErrorHandler.php:233`, `error_reporting(original &
  UNHANDLEABLE_LEVELS)`), and PHP invokes the handler regardless of the
  mask — the events were still collected. The line has been removed
  again; `phpunit.xml` is byte-identical to its committed state.
- **A PHPUnit baseline only half-works.** `--generate-baseline` / the
  root `baseline=` attribute would silence direct `phpunit` runs (the
  Collector returns early on `ignoredByBaseline` issues), but
  `php artisan test` ignores baselines entirely: collision's
  `DefaultPrinter::testPhpDeprecationTriggered` marks the test
  `DEPRECATED` **unconditionally** — it never reads the flag. Since the
  user runs `php artisan test`, a baseline alone would have left the
  noise exactly where it bothers them.
- **Upgrading the framework** (the constant is fixed upstream in
  ≥ 12.60) is the permanent cure but a major upgrade — Part 6.

**The fix applied — collision's own patch, by hand:** it turns out
`nunomaduro/collision` **ships** `scripts/fix-pdo-constant.php`, written
by its authors for this exact constant — but it targets
`__DIR__.'/../vendor/…`, a path that only exists inside collision's own
development repo, so it silently no-ops in app installs. Its transform was
applied verbatim to all four reads of the constant in the framework:
`(PHP_VERSION_ID >= 80500 ? Pdo\Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA)`
— the 2 firing sites (`config/database.php:61/81`, the mysql + mariadb
option arrays) plus 2 dormant ones in `MySqlSchemaState.php` (schema
dump/load, which would otherwise warn the moment `schema:dump` runs on
PHP 8.5). Behaviour-identical by construction: the same integer on every
PHP, and on PHP < 8.5 the ternary's else-branch is literally the original
expression. `vendor/` is git-ignored, so the working tree is untouched by
it — and it **reverts on the next `composer install`/`update`**, which is
one more reason the Laravel upgrade in Part 6 is the permanent answer.

**Result: zero deprecation lines in the output of both runners, on both
PHPs** — see Verification.

### Part 6 — Composer: 51 advisories, framework update blocked (reported, **not** actioned)

- `composer audit` (installed): **5 advisories, all `laravel/framework`** —
  incl. **PKSA-b41t-wbzk-9q51** (TokenGuard authentication bypass through
  non-string API credentials), debug-page XSS, signed-URL path confusion,
  and two CRLF-injection-in-default-email-rule reports.
- `composer audit --locked`: **51 advisories across 13 packages** (the
  wider set includes dev-only dependencies), plus **3 abandoned** packages
  (`dragon-code/*`).
- **`composer update laravel/framework` cannot resolve at all:** Composer
  2.11's advisory blocking (a feature that arrived with 2.11.0 on Oct 29)
  rejects **every** version satisfying the root constraint `^11.31`
  (v11.31.0 … v11.57.1) against **9 PKSA advisory IDs**, because the fixed
  versions are ≥ 12.60 / ≥ 13.10 — a major upgrade. The documented escape
  hatches (`policy.advisories.ignore-id`, `policy.advisories.block=false`)
  exist but were **not** used: this is the owner's call, not a silent
  downgrade of the security policy.

### Part 7 — The two VS Code test trees ("Laravel Tests" / "PHPUnit Tests") are two extensions running one suite

The Testing sidebar showed both trees listing the same tests — they are
**two extensions each registering its own test controller**, not two
suites: "Laravel Tests" comes from `laravel.vscode-laravel` (the label is
a literal in its `dist/extension.js`), "PHPUnit Tests" from
`devsense.phptools-vscode` (PHP Tools; label in `out/src/extension.js`).
Every run is therefore duplicated — keep either tree (the Laravel one
drives `php artisan test`, PHP Tools drives phpunit directly) and ignore
or disable the other; there is no data difference between them.

Counts: phpunit is authoritative — **1,314** (`--list-tests` prints 1,317
lines minus 3 headers; **0 data providers** anywhere in `tests/`, and the
only PHP files outside the two suites are `tests/TestCase.php` and
`tests/Traits/CreatesTenant.php` with **0** test methods). PHP Tools'
tree showed 1,320 (+6) — a quirk of its own discovery parser, not missing
or extra tests. (An earlier "123/123" reading was simply a partial run.)

### Verification

- **Full PHPUnit on PHP 8.2 (XAMPP): 1,314 tests / 4,482 assertions,
  0 failures** — multiple runs including the final post-Part-5 run
  (**EXIT 0, 0 deprecation lines**, 2m41s); the delta over the
  2026-11-03 entry's 1,309/4,448 is exactly the five gate tests above.
- **`php artisan test` on PHP 8.5.12 (system): EXIT 0, 1,314 / 4,482,
  0 failures, 0 deprecation lines** (157s) — now fully out of the box:
  the user's installed gd + 512M (Part 3) plus the Part 5 vendor patch;
  the same command previously ended in `! … deprecated` markers on every
  boot.
- **Flake fix:** `NotificationServiceTest` **60/60** consecutive green.
- **Module sweep probe: 15 routes HTTP 200**, no error banners, no console
  errors (Firefox `NS_BINDING_ABORTED` background-XHR flakes re-requested
  standalone → 200; `/admin/journal` 404 was the probe's wrong path —
  correct: `/admin/accounting/journal`).
- **Live tenant 3 intact:** `tax_jurisdiction = 'ID'`, `isIndonesian =
  true`, 16 opening-balance rows, 1 opening journal entry.
- `npm run build` clean.

**Still open (reported, awaiting a decision):** (1) converted sales never
post journals — Part 2; (2) composer advisories / Laravel 12 major
upgrade — Part 6, which is also the permanent home of the Part 5 vendor
patch (it reverts on the next `composer install`/`update`); (3)
pre-existing, unchanged: `updatePurchaseReturn`
never touches the journal, returns have no PPN/PPh entry UI, and
`Transaction` still has no `account_name` accessor (all remaining readers
now go through the `account` relation, so the NULL bug has no other
victims). All work remains **uncommitted** — 27 files (25 modified,
2 new) — as always, git is the user's to run.

---
## 2026-11-03 — Opening Balance (New-Tenant Onboarding): the General Ledger Counted Every Opening Figure Twice, the Page Never Re-Shown What It Had Saved, and Delete Quietly Matched Nothing on the Test Driver

Follow-up to the 2026-11-02 audit. That pass asked whether the tax and
button work left anything inconsistent; checking the answer meant opening
the one area the audit never touched — **`/admin/accounting/opening-balance`**,
the first thing a new tenant touches. It is the page that configures the
correct balance *before* accounting starts, so an error here is not a
reporting nuance: it is the foundation every later statement stands on.

Three separate defects, one of them user-visible in production on day one.

### Part 1 — The General Ledger reported double the opening balance

**Symptom.** Save an opening balance of 1,000 on the setup page, then open
**Accounting › General Ledger** for any period after that date: the opening
balance reads **2,000**. The Trial Balance for the same date reads **1,000**.
The two core statements disagree about the same number.

**Root cause — one figure written twice, then summed from both copies.**
`OpeningBalanceService::saveOpeningBalances()` persists each figure into
three places:

1. an `OpeningBalance` row (the page's own record);
2. `Account.opening_balance` — a denormalised column on the account;
3. a real **opening journal entry** (`entry_type = 'opening'`) with genuine
   `Transaction` rows, so the Trial Balance can see it.

The Trial Balance (`AccountingReportService::generateTrialBalance`) sums
**transactions only** — `withSum` over `transactions`, filtered to posted
entries on or before the end date. It counts the opening balance **once**,
correctly.

`GeneralLedgerService::calculateOpeningBalance()` did something different: it
added `Account.opening_balance` **and** separately `SUM`ed every transaction
dated before the start date, with no `entry_type` filter. Because the save
path writes both representations, the same 1,000 entered the result twice —
**2,000 vs the Trial Balance's 1,000**, a difference exactly equal to the
opening balance.

**Why nobody had seen it.** `AccountSeeder::seedOpeningBalances()` creates
the opening journal entry but **never sets `Account.opening_balance`** — the
column stays `0`. On the seeded path the field contributes nothing and the GL
is correct by accident. The bug lives only on the path a real new user takes:
the setup page. Live tenant 3 confirmed it — 16 `OpeningBalance` rows, 1
opening entry (16 transactions), `Account.opening_balance = 0` on every
account — i.e. the seeded, working shape.

**Fix — make the journal the single source of truth when it exists.** In
`calculateOpeningBalance()`, an `EXISTS` check asks whether the account has
*any* opening entry; if it does, `Account.opening_balance` is suppressed
before the sum. The journal is then counted exactly once, matching the Trial
Balance.

The check is deliberately **date-independent**. The obvious version — suppress
the field only when an opening entry falls *before* the start date — would
reintroduce the double count the moment the period reached the opening date,
and would show a doubled balance for a start date *before* it. A start date
of 2025-12-01 against an entry dated 2026-01-01 must report 0 (nothing has
happened yet), not 1,000. `EXISTS` on `entry_type = 'opening'` alone gives
that: the field contributes only when the journal is not already carrying the
figure.

**Design intent preserved.** An `Account.opening_balance` with *no* opening
entry is still honoured — `GeneralLedgerServiceTest`'s
`test_opening_balance_includes_account_opening_balance` builds exactly that
(500 on a bare account, no journal) and still passes. The fix only suppresses
the field when the journal already carries it; it does not delete the field's
meaning.

**Reach.** The GL page routes `getGeneralLedger` → `calculateOpeningBalance`,
and so do the PDF and CSV exports (`AccountingController` export branch), so
the doubled figure was visible on screen and in both export formats.

### Part 2 — The setup page never re-shown what it had saved (production, every driver)

**Symptom.** Save opening balances, reload the page: every debit and credit
input renders **blank**. The data is in the database — the summary card even
totals it — but the form comes back empty, so the user cannot see or amend
what they entered.

**Root cause — a date compared in PHP instead of SQL.** `index()` built its
prefill by filtering a Collection:

```php
$existingBalances = $this->openingBalanceService->getAllOpeningBalances()
    ->where('balance_date', $balanceDate)   // Carbon vs 'Y-m-d' string
```

`balance_date` is cast to `date`, so each model holds a **Carbon**;
`$balanceDate` is the request's `'Y-m-d'` **string**. Laravel's
`Collection::where` with two arguments uses loose equality — but a `Carbon`
*is* `Stringable`, so it bypasses the "one side is an object" short-circuit
and falls through to `$retrieved == $value`. PHP then compares the object by
its string form:

```
Carbon('2026-01-01 00:00:00') == '2026-01-01'   →   false
```

`'2026-01-01 00:00:00'` is not `'2026-01-01'`, so the comparison never
matches — **on any database driver**, because it never reaches SQL. `$existing`
was always `null` and every input fell back to `0`.

**Fix.** The date filter moved into the query, where it belongs:
`getAllOpeningBalances()` takes an optional `?string $balanceDate` and applies
`whereDate('balance_date', $balanceDate)`; `index()` passes it and drops the
PHP-side filter. Proven against live tenant 3 (16 seeded rows dated
2026-09-01):

```
OLD (Collection->where with string)  = 0 rows   ← form rendered blank
NEW (whereDate in SQL)               = 16 rows  ← form prefills
```

### Part 3 — Date equality matched nothing on the test driver (and delete silently no-op'd)

**Symptom.** `deleteOpeningBalances('2026-01-01')` returned *"Opening
balances deleted successfully."* and reset `Account.opening_balance` to 0 —
but left the opening journal entry and its transactions in place, so the
General Ledger still showed 1,000. Re-saving on the same date would likewise
have stacked a second opening entry instead of replacing the first.

**Root cause — a `DATE` column, a `date` cast, and two drivers that disagree
about the bound value.** `journal_entries.date` and
`opening_balances.balance_date` are `DATE` columns whose models cast them to
`date`. On write, Laravel binds the cast as `'2026-01-01 00:00:00'`:

- **MySQL truncates** the time part to store `2026-01-01`, so a subsequent
  `where('date', '2026-01-01')` matched — which is why production appeared
  healthy;
- **SQLite stores the value verbatim** as `'2026-01-01 00:00:00'`, so the
  string comparison against `'2026-01-01'` matched **nothing**.

phpunit.xml runs the suite on SQLite `:memory:`, so every equality filter in
this path silently matched zero rows under test. Production was correct only
by accident of MySQL's type coercion — the code was never driver-independent.

**Fix — `whereDate()` at every equality site.** Six call sites in
`OpeningBalanceService` (two in `saveOpeningBalances`'s pre-delete, three in
`deleteOpeningBalances`, one in `getSummary`) now use `whereDate(...)`, which
compares the date part on both drivers. Range comparisons elsewhere in the
codebase (`<=`, `>=` — `CashFlowService`, `BankReconciliationService`,
`FinancialReportingService`, `AccountingReportService`, …) are unaffected:
a `'2026-01-01 00:00:00'` value still orders correctly against a bare date,
so only *equality* was ever at risk. A sweep confirmed **no equality date
filters remain** in `app/`.

No index exists on `journal_entries.date` or `opening_balances.balance_date`
beyond the `(tenant_id, account_id, balance_date)` unique key, so
`whereDate`'s inability to use a bare-column index costs nothing here.

**One expectation in the new test was wrong, not the code.** The first draft
asserted that `calculateOpeningBalance(start = '2026-01-01')` returns 1,000
for an entry *dated* 2026-01-01. The query is strictly `date < startDate`, so
an entry on the start date is **in-period**, not "before" it: opening 0, the
entry appears as a transaction, closing 1,000. The test now asserts the
invariant that actually matters — **closing balance is 1,000 for every start
date** (2025-12-01, 2026-01-01, 2026-02-01) and equals the Trial Balance,
while the opening figure itself is 0 on the opening date and 1,000 after it,
never 2,000.

### Verification

- **New regression suite** `tests/Unit/Services/OpeningBalanceGlTbConsistencyTest`
  — **6 tests / 32 assertions**, covering: GL↔TB agreement after a save,
  the closing-balance invariant across three start dates, the posted entry
  being reversible by the page's Delete button, re-save replacing rather than
  stacking, the date-based prefill/summary lookups, and the bare-field case.
- **Full PHPUnit: 1,309 tests / 4,448 assertions, 0 failures** (5m42s,
  255MB peak) — up from 1,303/4,416, i.e. exactly these 6 tests and 32
  assertions. *(Superseded the next day: **1,314 / 4,482** after the five
  gate tests added in the 2026-11-04 entry below.)* Affected suites re-run
  green first: `GeneralLedgerServiceTest`,
  `OpeningBalanceServiceTest`, `OpeningBalanceControllerTest`,
  `AccountingControllerTest`, `BankReconciliationControllerTest`.
- **Live prefill probe** (headless Firefox, GET-only) on
  `/admin/accounting/opening-balance?date=2026-09-01`: **6/6 PASS** — all 5
  seeded figures prefilled with their real values (Cash 50000, Bank 200000,
  AR 75000, Allowance credit 2500, Inventory 100000) and an unrelated date
  still rendering blank inputs.
- **Health probe 50/50** across the 8 routes + settings wiring (one run
  showed a single Firefox `NS_BINDING_ABORTED` navigation flake; immediate
  re-run 50/50), including the mirror check proving the probe leaves the live
  tenant untouched (`before=ID after=ID`).
- **Module sweep probe: 15 routes, all HTTP 200**, no error banners and no
  console errors (background XHRs showed the usual Firefox
  `NS_BINDING_ABORTED` navigation flakes — each affected endpoint returned
  200 when re-requested standalone). One probe miss was the probe's own
  bug: `/admin/journal` 404s because the correct path is
  `/admin/accounting/journal`.
- **Live tenant 3 state confirmed intact** after probing: 16 opening-balance
  rows, 1 opening entry (16 txns), `Account.opening_balance = 0` everywhere,
  `tax_jurisdiction = 'ID'`, `isIndonesian = true`.
- `npm run build` clean (5.33s).

**Not changed:** `Account.opening_balance` is still written by
`saveOpeningBalances()` and still read by `BankReconciliationService` as the
bank-reconciliation prefill — that consumer wants the field and was left
alone. The three-representation design (rows + column + journal) is retained;
only the *counting* was corrected.

---

## 2026-11-02 — Buttons That Look Like Buttons (Financial Health soft pills), and the Tax-Jurisdiction Audit: Why "Success" Didn't Apply the Jurisdiction, a Scope Fix for the Dropdown Leak, and the `account_name` NULL Bug That Silently Zeroed Every Return Reversal

Two pieces of work in one pass: a small affordance fix on the Financial
Health page, and an end-to-end audit of tax jurisdiction across accounting,
sales, sales return, purchase, purchase return, and the tax pages
themselves.

### Part 1 — The soft pills read as badges, not buttons

**Symptom.** "Compare all" and the per-item severity link pills on
**Accounting › Financial Health** are `rounded-pill bg-*-lt text-*` — flat
tint, no border, no hover, no pointer cursor. First-time users never suspect
they are clickable.

**Fix.** A new `.fh-soft-action` class (`resources/css/app.css`) that keeps
the approved tint but restores every button signal:

- a 1px border tinted by `currentColor`, so **one rule** covers
  primary/warning/danger/… instead of a class per tone;
- a hover/focus-visible that deepens the tint — it needs `!important`
  because the `bg-*-lt` utility also carries `!important` and would
  otherwise win the cascade;
- `cursor: pointer` plus a faint elevation shadow, and an inset shadow on
  `:active`.

`color-mix(in srgb, currentColor …)` keeps the border translucent in **both**
themes; browsers without it simply fall back to today's borderless pill —
the fix degrades to the previous look rather than to nothing. Applied to the
Compare-all button and the severity link pills; the markup assertion in
`FinancialHealthControllerTest` was updated to match the fixed class order.

### Part 2 — Tax jurisdiction: "it saves, but it doesn't apply"

**The report.** Changing *Tax Jurisdiction* on the settings page showed a
success toast, but the change did not apply to the rest of the system.

**Root cause — two save paths, and only one of them knew about the field.**
The jurisdiction `<select id="taxJurisdictionSelect">` lives in its own
"Tax Region" section **outside** `<form id="systemSettingsForm">`, because it
saves itself over AJAX (`admin.setting.update-tax-jurisdiction`). The main
**Save Settings** button posts the *form* — which contained **no
`tax_jurisdiction` field at all**. So:

- the AJAX path worked and toasted success;
- the main-form path *also* toasted success ("System settings updated
  successfully.") while silently omitting the jurisdiction.

Which behaviour the user saw depended on which control they touched, and a
save racing an in-flight jurisdiction request could revert it. A success
toast over a dropped field is the worst possible version of this bug: the UI
was certain, and wrong.

**Fix D — one field, one helper, one message.**

1. A hidden `<input type="hidden" name="tax_jurisdiction">` now sits **inside**
   the form, under the same plan gate as the section (starter plans don't
   render the section, so the field is absent — and `Request::has()` in
   Laravel is *key-presence* based, which is exactly the signal needed to
   leave those tenants untouched).
2. A `change` listener mirrors the select into the hidden input **immediately**,
   so if the user hits Save while the AJAX request is still in flight both
   paths agree on the value.
3. `SettingsController::update()` now handles `tax_jurisdiction` through
   **`applyTaxJurisdiction()`** — the same helper `updateTaxJurisdiction()`
   uses, so the e-Faktur draft safeguard and the "switching away never
   deletes tax rows" behaviour are identical on both paths. The helper returns
   `{success, changed, message}` and is idempotent: an unchanged value reports
   "updated", never a false "jurisdiction saved".
4. The response message now **discloses** the jurisdiction outcome — including
   when a switch is *refused* (unissued PPN sale / draft e-Faktur). Settings
   still save in that case, but the toast says the jurisdiction did not move,
   instead of claiming it did. The non-AJAX redirect previously hardcoded
   `System settings updated successfully.` and dropped the note entirely; it
   now carries `$message`.
5. The toast survives the post-save reload via the existing `sessionStorage`
   handshake (`settingsSaved` / `settingsMessage`), and a failed AJAX save
   **reverts both controls** to the last persisted value so the UI never
   displays a jurisdiction that isn't real.

**Fix E — the dropdown leak (selection disagreed with the journal).**
PPN and PPh tax records only exist for the Indonesian jurisdiction, but
`Tax::active()` / `Tax::default()` returned them to *every* tenant. A
non-ID tenant could therefore select PPN 11% as its default and have the
forms offer it, while the journal side (correctly) gated on
`isIndonesian()` — so the document would store a tax the books ignored.

The fix is a single `scopeForJurisdiction()` on `Tax`, applied **inside**
`scopeActive()` and `scopeDefault()`, which covers all **25** call sites at
once rather than hunting them individually. Non-ID tenants now see only
non-PPN/PPh taxes in dropdowns *and* in fallback resolution (an unbound
tenant is treated as non-Indonesian). The `TaxController` management page is
deliberately **left unfiltered** — an admin must still be able to see and
edit every tax row.

**The bug underneath: `account_name` was always NULL.**
While auditing the return paths, a pre-existing defect surfaced that made
*every* proportional reversal silently do nothing. `Transaction` has no
`account_name` column and no accessor — its schema is
`id, tenant_id, journal_entry_id, account_id, type, amount`. Every
`$transaction->account_name` read therefore evaluated to `null`, so:

- the "what did the original journal actually post to VAT / withholding"
  lookups matched nothing → ratio denominators and numerators were 0;
- the code treated that as "nothing to reverse" and **skipped the PPN, PPh
  and COGS reversal lines entirely**, leaving input VAT and withholding
  permanently overstated on every return.

Fixed at **7 read sites** (SalesReturnService ×2 blocks,
PurchaseReturnService ×1 block of 3 reads) by resolving
`$transaction->account?->name` and eager-loading `transactions.account` on
the source entries. `AccountingService` already used `account_id` correctly,
and the export/audit services already went through the `account` relation —
the return services were the only victims. Grep now finds zero
`transaction->account_name` reads under `app/`, `resources/`, `tests/`.

**Accuracy fixes found during the same audit:**

- **PPh gating.** Every `calculatePphAmount()` call site is now gated on
  `$pphApplied = isIndonesian() && is_pph` (Sales create + update, Purchase
  ×2, Pos, Quotation ×2). Previously a non-ID tenant selecting a PPh tax
  stored and *subtracted* withholding it must never apply.
- **AR booked at gross.** The sales journal now debits receivables at
  `$grandTotal + $pphAmount`. The separate withholding entry already credits
  AR for the PPh; booking AR at the net total deducted it **twice**.
- **Sales-return PPN reversal** uses the original journal's *net* revenue
  credit as the ratio base, not `$sale->total` (which is gross — using it
  under-reversed a full return: 19.82 instead of 22.00).
- **Purchase-return reversal** reads what the original journal actually
  posted to PPN Masukan and PPh Payable and reverses those proportionally,
  rather than recomputing from today's rates.

### Verification

- **Full phpunit: 1303 tests / 4416 assertions, 0 failures** — including
  the previously known `FinancialReportingServiceTest::test_generate_income_statement_calculates_net_income_correctly`
  flake, which passed this run.
- **vitest: 209 / 209** (172 files).
- New/extended tests: `SettingsTaxJurisdictionTest` (8 — hidden-field
  persist, no-field untouched, idempotent no-false-claim, blocked switch
  discloses but still saves), `SalesServiceTest` (29 — AR gross 222 +
  withholding journal amounts, update non-ID no-shrink with a `total_tax`
  discriminator that distinguishes all four fix states), `SalesReturnServiceTest`
  (9 — full PPN reversal 22.00 off the net base), `PurchaseReturnServiceTest`
  (6 — PPN Masukan 22 / PPh Payable 4 / AP net 218 reversal), and a new
  `TaxJurisdictionScopeTest` (6 — non-ID excludes PPN/PPh from active +
  default, ID sees them, both create-dropdowns filtered with a non-null
  fallback, and an empty-list/null-default case that must not fatal).
- Browser probe **50/50** across `/admin/sales`, `/admin/sales/create`,
  `/admin/po`, `/admin/po/create`, `/admin/sales-returns`, `/admin/por`,
  `/admin/efaktur`, `/admin/settings` — no console errors, no failed
  requests, no error banners — plus the settings wiring checks (select,
  options, hidden field inside the form, live mirror, and that the probe
  itself leaves the tenant untouched).
- A live save round-trip proved **both** paths end-to-end: the AJAX toggle
  persists and toasts ("Yurisdiksi pajak berhasil diperbarui."), and the main
  Save Settings path persists a jurisdiction value that was armed *without*
  firing the AJAX handler — i.e. the hidden field alone carried it. The live
  tenant was verified back on `ID` afterwards.
- `npm run build` clean; compiled view cache cleared after blade edits.

### Reported, not fixed (pre-existing, out of scope for this pass)

- **`updatePurchaseReturn` never touches the journal.** Updating an existing
  purchase return adjusts stock and totals but posts no reversing entry, so
  the GL drifts from the document on every edit. `createPurchaseReturn` is
  correct; only the update path is missing. Left as-is deliberately — it is
  a separate behavioural change, not part of the jurisdiction work.
- **Returns have no PPN/PPh UI.** Sales- and purchase-return screens don't
  expose tax controls; tax comes from the parent document's snapshot. Fine
  for full returns, but a *partial* return can't be given its own tax
  treatment from the UI.
- `Transaction` still has no `account_name` accessor — the reads were fixed
  at the call sites rather than by adding a real accessor/relationship
  alias, which would be the more durable fix if more code starts reading it.

---

## 2026-10-31 — Financial Health: Net Margin beside Gross Margin, Debt-to-Equity + DSO cards, a Full-Page Explainer, a Period-Comparison Engine (per-card modal + "Compare all"), a Global Period Filter, and a UI-Pass (dashboard chart, outlook spacing, themed buttons, collapsible explainer)

Extends the **Accounting › Financial Health** feature (2026-10-30). Same
service (`App\Services\FinancialHealthService`), same posted-GL-only
doctrine, same `config/financial-health.php` threshold basis. This batch
adds two cards, teaches the whole page, and gives every number a way to be
compared against its own past — either the month before, or a period the
user picks.

### The two new indicator cards

- **Net Margin** now sits **beside Gross Margin** so the two read against
  each other: gross is the product before overheads, net is the true bottom
  line after every expense (incl. tax). Same window, same noise floor.
- **Debt-to-Equity** (liabilities ÷ equity) and **Days Sales Outstanding**
  (AR ÷ recent revenue, scaled to the window's real day count) join the
  grid as cards 11 and 12. Both carry config thresholds and reason strings
  (`de_low`/`de_watch`/`de_high`, `dso_fast`/`dso_watch`/`dso_slow`) with a
  plain-word narrative for each, en + id.

### A full-page explainer ("Understanding These Metrics")

A DRY, array-driven explainer at the bottom of the page: all **12 cards**,
the **4 outlook concepts** (confidence, expected range, runway, collection
pace), and the **color legend**, each rendered through
`__('messages.'.$label)` so it ships in English and Indonesian. It answers
"what is this number and why does it matter" without leaving the page.

### The period-comparison engine

One engine (`getComparison()`) feeds two surfaces so the math is never
duplicated:

- **Per-card modal** — every card gets a compare button that opens a modal
  showing that metric against the prior period.
- **"Compare all"** — the same engine rendered as a full 12-row table in one
  modal, with a mini sparkline per flow metric.

**Honesty rules** (the part that makes it trustworthy):

- Both sides are **single complete months** by default (the in-progress
  month is never compared as if it were finished); with a filter, the whole
  selected period is compared against the **preceding window of equal
  length**.
- **5 flow metrics** (cash, revenue, net margin, gross margin, purchases)
  carry a real monthly series and get a true delta.
- **7 balance-sheet snapshots** (receivables, payables, payroll, turnover,
  working capital, debt-to-equity, DSO) are **point-in-time by design** and
  are returned **locked** with a `lock_reason` rather than a fabricated
  delta — the UI shows a lock icon on the card and in the table row.
- Margins are recomputed **from each window's own revenue and cost** (never
  a trailing average), and suppress the aggregate when the window's revenue
  is below the noise floor (`noise.amount`, 100k) — so a thin month shows
  "—", not a made-up percentage.

### The global period filter

A preset **and** custom range, both reducing to one `[from..to]` window so
everything downstream sees exactly one thing:

- **Presets** `last_month` / `last_3` / `last_6` / `ytd` / `last_year`, plus
  a **custom** from/to month range. Cap `period_max_months` (24).
- Validation, in order: unparseable input → default rolling window; never
  the future or the in-progress month; `from` can never sit after `to`;
  span capped at the max. The resolved `window` (indicator span),
  `comparison_window`, and `series_from` (loads one extra window early so
  the prior side has real data) all derive from that one range.
- **Reason/narrative strings carry `{period}` / `{period_adj}` slots** so no
  line ever claims "3 months" when a different span is on screen. A one-month
  window reads "a single month"/"monthly"; a 3-month window reads "3
  months"/"3-month". en + id.
- The dashboard strip keeps its own default window (it has no filter), so
  only the full page is filter-scoped.

### The UI pass (this batch's polish)

- **Dashboard chart** — the animated cash/revenue line the Financial Health
  page already drew is now also on the dashboard strip, driven by the same
  `chart` payload the summary already computes (no extra queries).
- **Outlook header** — the "Next Month Outlook" title and its confidence
  badge were cramped; the badge now sits on its own row with breathing room.
- **"Compare all" button** — restyled to a soft `bg-primary-lt` pill that
  matches the page's badge language instead of a generic button.
- **Compare modal graph** — flow metrics now carry a **sparkline column**
  (with real month labels in the per-card view); the aggregate is omitted
  rather than faked when the window is below the noise floor.
- **Collapsible explainer** — "Understanding These Metrics" is now
  **collapsed by default** (it is reference material, not daily reading),
  one click from the header, via `financial-health.js` (CSP blocks inline
  handlers and the Turbo data-api is unreliable).

### Verification for this change

- **phpunit (full):** **1,279 tests / 4,319 assertions**, 1 failure — the
  **pre-existing** `FinancialReportingServiceTest::test_generate_income_
  statement_calculates_net_income_correctly` (a month-end date bug in
  untouched code, confirmed against baseline via stash/pop; not fixed here).
- **FinancialHealthServiceTest:** **30 tests / 338 assertions, 0 failures** —
  adds period-resolution, comparison-window, sparkline-label, and
  Indonesian-placeholder coverage.
- **vitest:** **172 files / 209 tests, 0 failures.**
- **npm run build:** clean after every JS/Blade edit; view cache cleared.
- **Health probe (headless Firefox):** **32/32 checks pass** — filter bar,
  preset↔custom sync, lock icons, outlook spacing, collapsible explainer,
  both modals (per-card + compare-all), dashboard chart, and the 3-month
  filtered view ("Showing: Jul 2026 — Sep 2026").
- **Note:** the three new card icons (`ti-rotate`, `ti-balance`,
  `ti-hourglass`) load from the Tabler CDN webfont and render correctly in a
  real browser; a headless screenshot may show a fallback glyph if the font
  has not finished loading.

---

## 2026-10-30 — Financial Health: a Dashboard Strip + Full Accounting Page (red/green money status, 6-month trends, plain-word next-month outlook), with the Threshold Basis for Every Verdict, a Corrected VAT Note, a Fixed Translator Placeholder, and a Tutorial Page

New feature under **Accounting › Financial Health** (route
`admin.accounting.health`). It replaces the old 8-card "Accounting Summary"
on the dashboard with a compact **health strip** that links to a full page.
Both are driven by one service, `App\Services\FinancialHealthService`,
composing the existing ledger/subledger services so there is a single source
of truth. All figures are **posted-GL-only** (draft/void entries excluded);
sales and purchase flows are read from the subledgers by `order_date`. ~11
bounded aggregate queries, driver-aware (sqlite in tests, mysql in prod).

**The page** shows: an overall verdict banner, 8 indicator cards, a
"What Stands Out" rotating plain-word narrative (noise-gated so it only
speaks to salient moves), a **Next Month Outlook** that chains a projection
to one closing number with a plain-sentence statement, 3 six-month trend
charts (ledger revenue-vs-spending, sales-vs-purchases, cash balance), and
a "Needs Attention" list with drill-down links to the relevant report.

**Outlook chain** (option: chain, not a bare trend line): Holt-Winters
revenue → 6-month collection ratio → cash-in → 3-month weighted expenses
(purchases + scheduled payroll + operations, weights 1/2/3) → projected
month-end cash + runway, each stage carrying a confidence level. A
best/worst-of-last-6 **expected range** is shown only when there are ≥2
complete scenario months; otherwise it is hidden by design rather than
shown as a false-precision band.

### The threshold basis — what makes each verdict "healthy"

Every red/amber/green traces to one rule in `config/financial-health.php`,
so "why is this red?" always has a stated answer. These are the comparisons
the user asked to have documented:

| Indicator | What is measured | Green (healthy) | Amber | Red |
|---|---|---|---|---|
| **Cash** | Months of spending the balance covers = cash ÷ avg monthly expense (last 3 complete months) | ≥ **3.0** months cover | ≥ **1.0** | < 1.0 |
| **Revenue** | Last complete month vs the average of the 3 before it | ≥ **95%** of recent average | ≥ **80%** | < 80% |
| **Profit margin** | Net margin, trailing 3 complete months (GL, posted) | ≥ **10%** | any positive | negative |
| **Purchases** | Purchases ÷ sales, trailing 3 months (subledger) | ≤ **1.00** (spend within means) | ≤ **1.15** | > 1.15 |
| **Receivables** | Share of outstanding that is **31+ days** overdue | < **10%** | < **25%** | ≥ 25% |
| **Payables** | Overdue-31+ payables relative to cash on hand (1.0 = overdue bills equal the cash you hold) | < **1.0×** cash | < **1.5×** | ≥ 1.5× |
| **Payroll** | Paid payroll ÷ GL revenue (trailing 3 months) | < **1.0** (wage bill under income) | — | ≥ 1.0 (wages alone exceed income) |
| **Inventory turnover** | Annualised COGS (6-mo GL) ÷ inventory GL balance | ≥ **4.0** turns/yr | ≥ **1.5** | < 1.5 |
| **Runway** | Months of cash left burning at the current net rate | ≥ **6.0** months | ≥ **3.0** | < 3.0 |

Note the indicators are deliberately *not* a single multiple-of-turnover
rule — cash is judged as **months of cover** (spending-based), margin as a
**percentage of revenue**, payables **relative to cash on hand**, and
turnover as **annualised turns**. The trend window is 6 months, and every
"vs average" comparison uses **complete months only** (the in-progress
month is excluded) so day-2-of-month never looks like a revenue crash.
Narrative lines are noise-gated (skipped when a move is under Rp 100 000
**and** under 5%) and capped at 6, with the closing outlook statement added
on top — keeping it "smart and not repetitive."

### What this pass fixed alongside the feature

1. **Translator placeholder bug (`:pct` / `:n` rendered literally).** The
   page passed `[':pct' => …]` and `['':n' => …]` to `__()`. Laravel's
   `Translator::makeReplacements` **prepends `:` to every key** itself
   (`$shouldReplace[':'.$key]`), so a key of `':pct'` is looked up as
   `'::pct'` and never matches the message's `:pct`. The screenshot showed
   the literal `after your :pct% collection pace`. Fixed by passing bare
   keys — `['pct' => …]`, `['n' => …]` — in
   `financial-health.blade.php`. **Lesson:** message strings carry `:name`;
   the replace-array key is the **bare** `name`, never `:name`.

2. **VAT note corrected (it was not entirely true).** The old caption
   claimed "ledger figures are VAT-net" flat-out. Checked against the real
   tax system (`TaxService`, Settings → Taxes, `SalesService` booking):
   taxes are **per-tenant, user-created** records (rate/type/active/default,
   `is_ppn`/`is_pph`); each sale carries an **optional** `tax_id`, and
   `sales.total` is the grand total **including** any tax charged. The GL
   credits revenue **net of VAT only when** the tenant is Indonesian **and**
   the tax is PPN **and** a PPN-Keluaran account is configured — otherwise
   revenue is credited the **gross** total. So VAT-net is *conditional*, not
   universal. Reworded (en + id) to: sales figures include any tax charged
   on the sale; ledger revenue is net of VAT **only where PPN posting
   applies**.

3. **Header spacing.** Tabler's `.card-header` is a flex row with no gap,
   so the "Last 6 Months" title and its caption were jammed together
   (`Last 6 MonthsSales figures…`). Added `ms-3` to the caption div in
   `financial-health.blade.php` — kept as a trailing subtitle (not
   right-aligned `ms-auto`) because the caption is long, and a gap reads
   correctly as a subtitle.

4. **Tutorial (simplified, main points only).** Added a route context for
   `admin.accounting.health` in `config/tutorial.php` (5 steps, placed
   before the catch-all accounting patterns) with en/id strings in
   `lang/*/tutorial.php`: overall verdict, cash & runway, receivables vs
   payables, profit margin, next-month outlook — the handful of numbers
   usually worth keeping track of. Also added Financial Health to the
   Accounting "What to explore?" topic grid, and refreshed the now-stale
   dashboard `accounting_summary` step (the old panel is gone; it now
   describes the health strip and links to the page). No further steps were
   added — the page itself stays the detailed view; the tutorial only names
   the main numbers.

   **Follow-up (same day):** the tutorial *context* existed but the page had
   **no icon to open it** — every other page puts a
   `trigger-tutorial-modal` anchor inside its `page-title` `h2`, and my
   header had omitted it. Added the anchor to `financial-health.blade.php`,
   so the info icon now appears beside "Financial Health" and opens the
   5-step modal (verified live: icon present, modal opens, all 5 steps
   render).

### New test file — `tests/Unit/Services/FinancialHealthServiceTest.php`

There was no coverage for this service (only the older
`FinancialReportingServiceTest`). Added **12 tests / 162 assertions**:
payload structure for `getSummary`/`getOverview`, all 8 indicator keys with
the exact shape the view reads (`key/label/icon/value/display/status/
reason/delta`), `overallStatus` ranking (worst-wins) via a subclass probe,
the empty-tenant degradation path (never flags critical on no data), the
outlook chain's arithmetic invariants (`projected = cash_now + net`,
`net = cash_in − expenses`, `cash_in = revenue × collection_ratio`,
confidence clamped 0–95), range-suppression under 2 scenario months,
attention-item drill-down URLs, driver-awareness (asserts `sqlite` so the
`strftime`/`DATE_FORMAT` branches are actually exercised), and a posted-GL
case proving a revenue posting drives the profit-margin indicator to
`1.0`/`good`. That last test initially targeted the *revenue* indicator and
failed — by design the revenue indicator reads the **sales subledger**
(`order_date`), while the GL feeds **profit/margin**; the test was corrected
to assert the GL-fed indicator instead.

### Two Blade lessons recorded while building this

- **`@json()` must not contain nested `__()` calls.** Blade's non-greedy
  match truncates at the first `)` inside nested parens, silently producing
  a broken payload. Build the array in a `@php` block first, then
  `@json($payload)`.
- **Never write a directive name (e.g. `@php`) inside a `{{-- … --}}`
  comment.** Blade compiles directives even inside comments, so a literal
  `@php` in a comment pairs with the real `@endphp` and breaks the block.

### Verification

`healthprobe` **21/21 ALL PASSED** (strip 8 cells + narrative + link, CTA
lands, page title/8 cards/outlook with 8 chain rows + statement, JSON data
island, 3 charts drawn on **both** Turbo visit and cold load, attention
rows, sidebar puts Financial Health first under Accounting, zero page
errors). Live-page check confirms the `:pct`/`:n` placeholders now render
(`after your 46% collection pace`), the corrected VAT caption, and the
`ms-3` gap on the trends header, and the tutorial info icon opening the
5-step Financial Health modal. Translation audit: all **56** feature message
keys present in **both** `lang/en` and `lang/id`, plus all **7** tutorial
keys in both locales — no missing or orphaned strings. vitest **209/209
(172 files)**; PHPUnit **OK — 1,261 tests / 4,145 assertions** (includes
the new 12-test `FinancialHealthServiceTest`). Screenshots:
`/tmp/opencode/ffdriver/health-strip.png`, `health-page.png`,
`health-trends.png`, `health-tutorial.png`. All work left **uncommitted**
(per the standing arrangement — the user handles git). `docs/CHANGELOG.md`
updated last; no code or environment edits follow this entry.

---

## 2026-10-29 — Post-Pull Incident: the Custom 500 Was a Cross-User View-Cache Permission Problem (Not the Pulled Code), and the Full Post-Pull Integration Pass

The user committed the local work as `508d1f05` ("refining the sidebar
animation for sub menu") and pulled `92348f41` ("fixing the turbo bug" —
touches `menu.blade.php`, `menu-sidebar.blade.php` [notification-bell route
rename], `app.js`, `PosService`, `PosServiceTest`). Immediately
`/admin/settings/profile` — and in truth every page using the layout —
showed Invent-MAG's custom 500.

**Root cause** (`laravel.log`): `file_put_contents(storage/framework/views/
c4a810a5467c056875cca1d377c27273.php): Failed to open stream: Permission
denied (View: …/menu.blade.php)`. The pull changed the layout blade, so its
compiled-view cache entry went stale; Laravel recompiles by writing **in
place** (no unlink first), but that file was `emberfall`-owned mode 644
(left behind by artisan/phpunit runs executed as the emberfall user) while
Apache/PHP runs as `daemon` → `EPERM` → 500 on every page sharing the
layout. The views dir is 777, so *new* files were never a problem — it only
bites when an **existing** entry must be overwritten, i.e. right after a
pull touches a widely-used blade. (The sidebar blade change itself was
trivial and innocent: a route name on the bell link.)

**Fix**: `rm -rf storage/framework/views/*` — unlinking needs only
directory write (777 works cross-user); PHP then recompiles every entry
fresh as `daemon`, restoring a single owner. No passwordless sudo exists on
this machine, so a `chown` sweep was impossible. **Recurrence rule:** if a
pull is followed by a custom 500 whose log line reads `Permission denied
(View: …)`, clear the compiled views and the page heals on the next hit.
(Prevention, not applied — needs root: give `storage` a single owner, or
have user-side tooling clear views instead of writing them.)

**Verification**: `profilecheck.js` — `/admin/settings/profile` returns
**200** after login, the form renders, zero page errors. Because the pull
touched the sidebar blade, the rail feature was re-verified end-to-end
against the merged code: `npm run build` **3.07s**; **railprobe 47/47 ALL
PASSED** (drawer, 30px marker, flush accent, disc hover, tooltips, both
collapse races, fold, F5); vitest **209/209 (172 files)**; PHPUnit **OK —
1,249 tests / 3,983 assertions** (3m39.9s; includes the pull's new PosService
cases). `/tmp` was wiped a **second** time by the system restart
(`markervariants.js`, `hoverdbg.js`, `drawershot.js` and the Change 49/50
captures lost again; `railprobe.js` + `drawershot.js` rewritten from the
session record). Working tree intentionally left **uncommitted**.

---

## 2026-10-28 — The Collapsed Rail Shows Where You Are: Active Chain as an Icon Group, `Section › Page` Tooltips, a Drawer Group (Frosted Tray + Flush Primary Accent), a 30px Circular Marker with Circle-Preview Hover, Both Collapse Paths Fixed, and a Red CI Build Fixed

Scope: the rail feature designed in yesterday's Q&A (active group only +
tooltips, section click unchanged), the three polish reports that followed the
first look at it ("the placement of the menu that is not a submenu [is]
awkward", "can't we make the submenu icon smaller when we click and accessing
it page", "the aligned top to bottom of the menu is awkward too that they move
to the left a little bit, any better idea?"), and the GitHub Actions failure
that came back after batch 1 was pushed. A follow-up review the same day
rejected the shipped marker ("much more worse than before") and asked for a
better "you are here" — Change 47 replaces the pill and hairline with a
circular marker and a group bracket, chosen from rendered candidates.

### Change 45 — Collapsed rail: the active page's group only, as icons with `Section › Page` tooltips

**The design, as answered:** option 1 — the rail shows *only* the active
submenu (no remembered extras claiming to be where you are); option 3 —
tooltips, so a bare icon row in 70px is self-describing; clicking a section in
the rail keeps today's behaviour (expands the sidebar and opens that submenu).

**One source of truth for "where you are":** `getActiveChainPanels(sidebar)`
(menu-sidebar.js, hoisted function declaration — the readyState boot path
calls restore during module evaluation) derives the chain from the URL with
syncActiveState's own match rule (exact hit or path prefix, so
`/admin/product/edit/9` keeps Product open). Every purpose that must never be
remembered routes through it:

- **Restore** adds `.show` to the chain in *both* modes, but the remembered,
  user-opened extras reopen only in the expanded sidebar (Change 43's memory —
  unlabeled rows in the 70px rail would pose as the active group). The gate is
  `sidebar.classList.contains('collapsed') || localStorage.getItem('sidebarCollapsed') !== 'false'`
  — `!== 'false'` matches `getSidebarState()`'s null-default (unset means
  collapsed), so restore and init can never disagree about the mode on first
  boot. Panels restored to `.show` get their Collapse instance via
  `getOrCreateInstance(panel, {toggle: false})` (the rail collapse hides open
  panels through `getInstance()`; a null would leave the group stuck open in
  expanded mode), and `aria-expanded` is resynced to the restored truth.
- **Before-cache snapshot** excludes the chain: memory = user extras only, so
  riding along as memory can never keep yesterday's section open next to
  today's. F5 still forgets the extras by construction.
- **`closePanelsExceptChain(sidebar)`** runs from all three paths that enter
  the rail — manual toggle, click-outside auto-close, sub-768 resize. The
  latter two previously left open panels surviving the collapse as *invisible*
  rows (`.sidebar.collapsed` hid submenu items by design); the outside-click
  path also gained the `addTooltips()` call it alone was missing (hover in the
  rail stayed silent after an outside click).

**Tooltips are resolved at hover, not at build:** `tooltipManager.create`
receives a getter (`() => railTooltipText(link)`) and re-reads it on show,
because the element is created once while the active page changes per
navigation. A submenu row reads `Accounting › General Ledger` (its section,
then itself); the section row reports where you actually are while its group
is open (`Accounting › General Ledger`), and just `Purchasing` while its
children are elsewhere.

**The fold:** `.sidebar` scrolls with `scrollbar-width: none`, and a full
section list plus the chain runs past the fold on short screens (the user's is
1366×768) — a cut-off group gives no hint that rows continue below.
`scrollActiveRowIntoView(sidebar)` (`scrollIntoView({block: 'nearest'})` on the
active link) runs from all three collapse paths and from restore's rAF (after
Change 43's scroll memory is applied): it moves *only* when the row is fully
out of view, so an already-visible row keeps the remembered position untouched.

**The alignment answer — one straight column, hierarchy by size instead of a
jog.** The reports were caused by one rule:
`.sidebar.collapsed .collapse.show .nav-submenu { padding-left: 0.5rem }` —
the nudge that made the group "hang off" its section. Measured consequences at
70px:

- The padding inflated the nav's **intrinsic width**: the submenu subtree
  measures 44 + 8 = 52, and `.sidebar-nav` (flex column, `align-items:
  center`) sizes its `.navbar-nav` by fit-content — so with the group open,
  *every section* parked at **x9** while the children sat at **x17**; without
  it the column is x13. The whole menu stepped sideways depending on whether a
  group happened to be showing — literally "they move to the left a little
  bit" when scanning past it.
- Nudge removed, the subtree is a stable 44 everywhere: **all 20 rows — 11
  sections, 6 children, 3 footer items — now measure x=13**, group shown or
  not.
- The footer items (Tutorial/Settings/Logout) were the "placement of the menu
  that is not a submenu" outlier at x13 *while the sections were at x9*. The
  fix is not to fill (`width: 100%` + `align-items: stretch` was tried first
  and anchored them at x8 — fill and fit do **not** land on the same line
  here, reasoned out in the rule's comment) but to keep the footer's
  shrink-to-fit + centre, which is exactly what the main nav's fit-content +
  centre now computes: x13 both.
- **Smaller child glyphs, same hit target:** `.sidebar.collapsed .collapse.show
  .nav-submenu .nav-link-icon` = `1rem/16px` against the sections'
  `1.25rem/20px`; rows stay 44px. That was the literal ask ("can't we make
  the submenu icon smaller…") and it carries the hierarchy once the lateral
  offset is gone.
- **Glyph centres align exactly:** a fresh 2px discrepancy (child centre 33 vs
  section 35) traced to `.nav-link-title { flex: 1 }` — in a centred row the
  hidden title *grows* into the leftover (20px row − 16px icon = 4px), and the
  4px tail shifted the glyph left. `.sidebar.collapsed .nav-link-title` now
  sets `flex: 0 0 0`; both centres measure **35**.
- **Hairline as the group bracket:** `left: -0.4rem` puts the 1px line at the
  gutter's midpoint (x6, 6.4px from the column, 6px from the rail edge). Two
  traps found by pixel measurement: the base `.nav-submenu` has
  `overflow: hidden`, which would clip a negative-offset `::before` — and also
  clip any group taller than `max-height: 500px` in the rail — so the rail
  rule sets `overflow: visible` (Bootstrap's `.collapse` already hides closed
  panels; the max-height belongs to the expanded mode's animation). And dark
  uses `--frz-edge-strong` (0.11) rather than `--frz-edge` (0.06): measured
  over the rail bg, +26 levels vs +13 — in expanded mode the line is
  decoration beside a 2.5rem indent, in the rail it is one of only two grouping
  cues, so it takes the ladder's next step up. Light stays `rgba(0,0,0,.16)`.

**Not changed:** clicking a section in the rail still expands the sidebar and
opens its submenu (probe-covered), and the chain's `.show` panels never
receive the stagger's `.visible` class — the opacity rules were re-scoped
(`.collapse:not(.show)` parks, `.collapse.show` renders) so restore-opened
groups paint fully either way.

### Change 46 — CI: the vitest step tripped laravel-vite-plugin's HMR-in-CI guard

**The failure (after batch 1 was pushed):**

```
Error: You should not run the Vite HMR server in CI environments. …
    at ensureCommandShouldRunInEnvironment (…/laravel-vite-plugin/dist/index.js:165:11)
```

**Why:** `npx vitest run` boots a Vite *server* to run the tests, and
`ensureCommandShouldRunInEnvironment(command, env)` early-returns only for
`command === "build" || env.LARAVEL_BYPASS_ENV_CHECK === "1"` — everything
else throws when `typeof env.CI !== "undefined"`, which GitHub Actions always
satisfies (`CI=true`). The `env` there is
`loadEnv(mode, userConfig.envDir || process.cwd(), "")` evaluated at config
time; with the empty prefix Vite copies **every** `process.env` key into it
(`key.startsWith("")` is always true), so a step-level env var is in scope.

**Fix:** one env line on the failing step in `.github/workflows/ci.yml`:

```yaml
- name: Run JS Tests
  run: npx vitest run
  env:
    LARAVEL_BYPASS_ENV_CHECK: '1'
```

That is the exact remedy the error message names. A `vite.config.js` shim
(`if (process.env.VITEST) process.env.LARAVEL_BYPASS_ENV_CHECK = '1'`) also
works — config-file bodies run before the plugin's config hook — but was
rejected: the workflow owns CI policy, and keeping test-mode conditionals out
of the build config keeps that file single-purpose.

**Proved locally, both halves:** `CI=true npx vitest run <one file>` →
reproduces GitHub's error and stack line-for-line;
`CI=true LARAVEL_BYPASS_ENV_CHECK=1 npx vitest run <one file>` → 1 passed.

### Verification for this change set

**Suites (both green):**

- `npm run build` after every edit round — last run **4.84s**, clean.
- vitest **172 files / 209 tests passed** (59.48s).
- PHPUnit **OK — 1,246 tests / 3,974 assertions** (7m27.898s, 239MB), run as
  `/opt/lampp/bin/php -d memory_limit=2048M vendor/bin/phpunit` (system php
  still lacks `pdo_sqlite`; environment notes unchanged from the batch above).

**`railprobe` — ALL CHECKS PASSED, 33/33, zero real page errors** (headless
Firefox, login → ledger → journal → sales round trip):

- expanded mode: chain open, remembered Purchasing survives a turbo hop,
  aria-resync (6);
- rail: chain-only (non-chain closed), active row visible/topmost/opaque,
  hairline drawn, aria (8);
- geometry: children **and** footer in the section column (x13 = x13 = x13),
  glyph sizes 16 vs 20, **glyph centres equal**, hairline at the gutter
  midpoint (5);
- tooltips: child = `Accounting › General Journal`, section-with-active-child =
  `Accounting › General Journal`, plain section = `Purchasing` (2);
- section click expands the sidebar and opens Sales (2); turbo hop to sales
  then collapse shows *only* the sales group (4); F5 keeps rail + chain and
  nothing else (3);
- the fold: at 600px viewport height the active row is revealed
  (`top=556, bottom=600, scrollTop=43`) (1);
- **zero page errors** (1).

The only console noise remains `downloadable font … status=2152398850`
(CDN font fetches, same classification as in the batch above) — reported as
INFO by the probe instead of failing it, since it is network, not app JS.

**CI fix:** local repro + green re-run described in Change 46; the workflow
file is otherwise untouched.

### Change 47 — The rail marker, redone: a 36px circle + a group bracket (chosen from the rendered candidates)

**The rejection (same day, after looking at it):** "much more worse than
before". The diagnosis, honestly: the 44px rounded-square pill is the same
shape as the row it sits in, so it read as a blob swelling out of the column —
and a white rounded square is *the* generic active-state treatment, visible in
every admin template. The hairline beside it was a 1px whisper that read as a
rendering artifact rather than "these rows belong together". Two instincts were
offered for a fix ("the background card wrapping the icon smaller", "a circle
instead of square") plus one firm requirement: tooltips must carry the chain
only on the *selected* row.

**The marker — both instincts merged (menu-sidebar.css, after "Active
Navigation Link"):** the active link is now a **36px circle inside the 44px
row** — smaller than the band (wraps just the glyph, the "smaller card"
instinct) and round (shape contrast does the "you are here" calling; squares
are rows, circles are position). Sections and chain children share the rule, so
the circle means the same thing everywhere in the rail. `padding: 0.5rem`
keeps the 20px section glyph inside the 36px box (20 + 16 = 36 exactly);
`overflow: hidden` (base link rule) + `border-radius: 50%` clips cleanly.

**The rhythm guard (same file, `.sidebar.collapsed .navbar-nav .nav-item`):**
`min-height: 44px; align-items: center`. Without the explicit band, the one
active `li` would shrink to its 36px link and every row beneath it would jump
8px every time the active page changes; without centring on the *cross* axis,
the circle would sit flush top-left in its band and its glyph would drift off
the column's centre line. Measured after: active link 36×36 at x16.5 (centre
34.5 ≈ 35, the column line), band exactly 44px, all rows still centre x35.

**The group cue — bracket, not line (both `::before` rules rewritten):** three
variants were rendered live against the current build (injected CSS, no build)
and shown side by side with the baseline: (g1) children stepped 8px right —
rejected: too small to read as hierarchy in a 70px rail, big enough to knock
the group off the section column, and it's the common admin-rail look anyway;
(g3) an "open" dot under the section glyph — elegant, but hints at *state*
rather than *grouping*; (g2, **chosen**) the hairline upgraded into a proper
**bracket**: `width: 7px`, `border: 1px` on three sides (`border-right: none`
— the open side faces the rows it contains), `border-radius: 3px 0 0 3px`
caps top and bottom. The caps are the whole idea: they turn a line into a
container, the universal "these rows are one contained group" mark. Position
unchanged in spirit — `left: -0.35rem` keeps the spine at the gutter
midpoint, `top/bottom: 0.35rem` insets it from the rows.

**Alpha 0.18, deliberately above the edge ladder:** the ladder
(tokens.css) tops out at `--frz-edge-strong` 0.11 for "things that float";
the first build of the bracket used that token and was visibly fainter than
the preview the user had approved. The bracket is not a surface edge — it is
the rail's only grouping *mark*, a lone 1px stroke on bare background with no
surface to reinforce (the old hairline at 0.11 is exactly what read as an
artifact). Dark now spends raw `rgba(255,255,255,0.18)`, with the rationale
in the rule's comment; light mirrors the hairline's raw `rgba(0,0,0,0.16)`.

**Tooltips — the requirement, verified to the letter:** the ask ("the tooltip
works only on the submenu that is selected; the others keep showing the
correct name") is what Change 45 already shipped, so nothing changed in JS —
but it is now *proved* by the probe rather than asserted: on the journal page,
hovering the **General Journal** row (active) shows `Accounting › General
Journal`, while hovering the **General Ledger** row (its sibling) shows
`Accounting › General Ledger` — its own name under the same section, never
the active page's. Each row is self-describing; only the row you are *on*
carries the "you are here" chain.

**Probe updates (railprobe.js):** geometry now compares row **centres** (the
36px circle's x differs from the 44px rows by design; the column lives at the
centre line, all = 35); new assertions for the circle (36px, `border-radius:
50%`, glyph centre on the column, 44px band) and for the sibling tooltip;
hairline checks became bracket checks (3-sided border, right open, left
-5.6px). One probe pitfall fixed while adding the sibling check: a synthetic
`mouseenter` on the section trigger (used earlier in the probe) never gets a
matching `mouseleave`, so its tooltip stays `.show` and wins the probe's
`querySelector` — the sibling check clears stale shown tooltips and hovers
with the real mouse.

### Verification for Change 47

- `npm run build` after every edit round — last run **4.80s**, clean.
- **`railprobe` — ALL CHECKS PASSED, 37/37, zero real page errors** (same
  round trip as before): including *active marker is a 36px CIRCLE*, *circle
  glyph centre on the column line* (35 = 35), *active row band stays 44px*,
  *group bracket drawn* (`1px/rgba(255,255,255,0.18)/0px` — border, right
  side open), *tooltip non-active row = Section > own name*.
- vitest **172 files / 209 tests passed** (67.87s).
- PHPUnit **OK — 1,246 tests / 3,974 assertions** (5m14.554s, 247MB).
- Visual proof (page-coordinate captures, 300% zoom):
  `/tmp/opencode/ffdriver/final-bracket-big.png` — circle wrapping just the
  glyph, centred in its band; bracket hugging the group; column unbroken.
  Candidate previews kept beside it: `g0-current`, `g1-indent`, `g2-bracket`,
  `g3-opendot` (the four rendered options).

**Visual proof for Changes 45–46 (page-coordinate screenshots — DOM-exact, plus the usual kiosk
grim pass on the 1366×768 display):**

- `/tmp/opencode/ffdriver/rail-page-strip.png` (70×668) — the full column:
  sections, group, all one line.
- `/tmp/opencode/ffdriver/rail-page-hover.png` (340×668) — real mouse hover on
  the active row: white pill + tooltip bubble `Accounting › General Ledger`
  at x67 (= pill right 57 + 10).
- `/tmp/opencode/ffdriver/hairline.png` — pixel column at the gutter: x6 =
  `(44,45,47)` against bg `(18,20,22)` down the whole group (0.11 token; at
  0.06 it measured `(31,33,35)` — too faint to earn its job).
- Kiosk grim captures: `rail-group.png`, `rail-tooltip.png` (+ `-crop`
  variants), window `1322x668+22+78`.

**Probe hygiene:** `/tmp/opencode/ffdriver` was wiped again before this round —
driver re-created (`puppeteer-core@25.16.0`, works with Firefox 158.0.1);
scripts for this feature are `railprobe.js` (37 checks after Change 47),
`align.js`/`icon2.js` (geometry), `hairshot.js`/`pageshot.js`/`cropactive.js`
(captures), `groupcue.js` (the bracket candidate previews), `railshot.js`
(kiosk grim).

- All changes intentionally left **uncommitted** — version control is handled
  manually.

### Change 48 — The bracket became a file tree: children tabbed 8px with an L elbow per row, and both collapse paths fixed

**The rejection (same day, after seeing the bracket built):** "it just a
bolder line". Fair — and it identifies why both line treatments failed twice:
a mark drawn BESIDE the rows can only ever read as emphasis. The user proposed
the pattern every tree UI already teaches: *"the sub menu is tab space a little
bit, and having a line like L directed to them"* — indent plus elbow, so the
line CONNECTS parent to child instead of standing next to them.

**The tab is a transform, never padding (menu-sidebar.css, after "The collapsed
rail renders ONE submenu"):** `.sidebar.collapsed .collapse.show
.nav-submenu .nav-item { transform: translateX(8px) }`. Change 45 measured why
padding is forbidden on this subtree: horizontal padding re-inflates the nav's
fit-content width and shoves every SECTION left; transform moves pixels without
touching layout maths. Measured after: sections x13, children x21, footer still
x13, glyph centres step with their rows (35 → 43).

**Two specificity battles on the way:** (1) the rail's reveal rules at the
file's end set `transform: none` on the same selector — same specificity, later
in file, so it silently won and the children stayed at x13 (probe caught it:
`childX` 13, computed transform `none`). The reset is now deleted rather than
out-specified: its parking partner is scoped to `:not(.show)`, so a shown row
was never parked and needs no reset — one rule owns the transform. (2) the
`.nav-submenu .nav-item.visible` stagger rule (3 classes) loses to the rail rule
(6 classes) on its own, no change needed.

**The elbow (same file, per-row `::before`):** `left: -10px; top: -26px;
bottom: 50%; width: 10px`, `border-left` + `border-bottom` 1px,
`border-bottom-left-radius: 4px`. The radius is what makes it read as `└` and
not a `T`. The geometry chains: rows are 44px + 4px gap = 48px pitch, the box
spans exactly one pitch (26 above the row + 22 to its centre), so consecutive
elbows form ONE continuous spine that visually starts under the section glyph;
the 10px width puts the spine at x11 (left of every glyph) and lands the tick
on the tabbed row's edge at x21. Ink is the bracket's decision, kept: 0.18 raw
white on dark (above the edge ladder's 0.11 ceiling — a lone 1px stroke on bare
background, rationale in the rule's comment), `rgba(0,0,0,0.16)` on light. The
bracket rules are retired to a comment, not deleted blind.

**Collapse path 1 — "closing the sidebar when the submenu [dropdown] closed":**
reproduced exactly as reported: on General Ledger, click the Accounting
trigger (on the ACTIVE page it is a normal toggle — it CLOSES its own
dropdown), then collapse. The rail showed **nothing**: 0 group rows, active
link 0x0 — no marker, no "you are here" at all. Cause: `closePanelsExceptChain`
only *spared* the chain; nothing ever OPENED it. Fix: it now brings the chain
to `.show` itself (with `getOrCreateInstance(panel, {toggle:false})`, the same
instance rule restore uses) and resyncs every trigger's `aria-expanded` to the
truth it just forced. The chain is derived from the URL every time, so this is
the same truth F5 applies — the rail always shows where you are.

**Collapse path 2 — "close the sidebar when the submenu [is] triggered" (the
rail section-click race):** the ghost's true origin was found with a
stack-trace probe, and it was NOT the suspected 500ms timer: tabler's bundled
Bootstrap registers its own collapse data-api (delegated on document, which
runs ahead of our link-level `stopPropagation`), and it starts opening the
clicked submenu at t≈5ms — the same click. A re-collapse inside that ~350ms
window met a panel that was neither open nor closed: mid-transition this
bundled Bootstrap REPLACES the `collapse` class with `collapsing` (measured:
className is exactly `"collapsing"`, so `.collapse.collapsing` matches
nothing), the old `.collapse.show`-only query skipped it, and its pending
transition completed afterwards — planting the ghost (measured: `open =
[submenu-2, submenu-8]`, chain only allowed). Three fixes, each measured:
(1) `closePanelsExceptChain` now also queries bare `.collapsing`; (2) before
`hide()`, a synthetic `transitionend` is dispatched so Bootstrap's pending
completion callback is consumed immediately instead of firing into the rail
later (`hide()` no-ops while `_isTransitioning`, which is why dispatching
alone was not enough — the instance must then hide for real); (3) the 500ms
pending explicit open got its own guard, now checking BOTH `collapsed` AND
`sidebar-fast-close` — the latter is added synchronously by the willCollapse
branch, closing the measured 50ms window where only `collapsed` was checked and
a firing timer still saw "not collapsed".

**Probe (railprobe.js, 37 → 44 checks):** geometry checks rewritten for the
tree (children x = section x + 8, glyph centres +8, circle centre on the child
column, elbow `1px/1px/4px/-10px/10px`); both collapse paths became permanent
scenarios: *closed-chain collapse* (dropdown closed → collapse → chain OPENS,
rows>0, marker 36x36, aria true) and *rail re-collapse race* (click section in
rail → toggle at +250ms → wait past the 500ms window → chain only, no ghost).

### Verification for Change 48

- `npm run build` after every edit round — last run **4.88s**, clean.
- **`railprobe` — ALL CHECKS PASSED, 44/44, zero real page errors** —
  including *children tabbed +8px* (`section=13 child=21`), *per-row L elbow*
  (`1px/1px/4px/-10px/10px`), *36px CIRCLE on the child column* (`act=43
  child=43`), *closed-chain collapse: chain OPENS for the rail* (`rows=6
  active=36x36 aria=true`), *rail re-collapse race: no ghost group, chain
  only* (`open=submenu-8`).
- vitest **172 files / 209 tests passed** (67.04s).
- PHPUnit **OK — 1,246 tests / 3,974 assertions** (5m50.995s, 241MB).
- Visual proof: `/tmp/opencode/ffdriver/elbow-hidpi.png` (2× scale) — one
  continuous spine descending under the section glyph, L ticks landing on each
  tabbed row, circle on the child column; `/tmp/opencode/ffdriver/circle-group.png`
  (1× page crop).
- Debug/repro scripts kept beside them: `racedbg.js` (class/panel timeline),
  `racedbg2.js` (MutationObserver on the ghost), `racedbg3.js`
  (Collapse.prototype stack traces — the data-api catch), `racedbg4/5/6.js`
  (instance state + mid-transition classList sampling), `struct.js` (flex
  ground truth), `closedchain.js` (path-1 repro).

- All changes intentionally left **uncommitted** — version control is handled
  manually.

### Change 49 — The bracket/elbow lineage died for good: the group became a DRAWER (frosted tray + 3px primary accent bar), children back on the section column

The user rejected Change 48's elbows on sight ("still bad… maybe something
much more interesting and not too plain"), which forced the real diagnosis:
three grouping treatments in a row — hairline (0.11), bracket (0.18),
indent + L elbows — were **all lines**. A 1px stroke has no body at 70px on
a 90%-zoomed screen: at low alpha it reads as a rendering artifact, at high
alpha as "a bolder line". You cannot fix a line by making it a different
line. The group does not need a stroke drawn *near* it — it needs a BODY.

The drawer: the `li` wrapping the section link and its open panel gets a
frosted tray (fill + soft edge) — the app's existing elevation language
(frosted cards) at rail scale — plus a 3px rounded primary bar hugging its
left edge, the "open file tab" mark. A fill is an area, not an edge, so it
survives zoom/scaling; a container is the honest metaphor for a group; and
the accent bar is *functional* — in a monochrome rail it is the one thing
that says which section owns the white circle before you read the glyph.

Four candidates (t1 6% tray / t2 11% tray / t3 folder-tab plate + tray /
t4 tray + bar) were rendered live via injected CSS — no build — and the user
chose t4. The bar's color is the app's **real** primary read off a live
`.btn-primary`: `rgb(0, 84, 166)` = `#0054a6` (tokens.css redefines tabler's
stock `#206bc4`), shipped as `var(--tblr-primary, #0054a6)`.

Geometry (measured on the built page, 1366×768, 2× capture): the tray's
margin/centre math leaves every row exactly where it was — section links
44×44 at x13, child links + the 36px circle 36×36 at x17, column centre x35,
tray border-box centred on the same line, so the circle is NOT clipped and
no rhythm rule needed touching. Children sit BACK on the section column:
with a container doing the grouping, the 8px tab and the per-row elbows had
no job left, and both were retired (rules and probe assertions deleted).

Dark tray flips to the same frosted white every floating surface uses
(`rgba(255,255,255,0.11)` fill / `0.14` edge); light spends ink instead
(`0.05`/`0.08`), the established ink-vs-frost decision.

Hygiene note: `/tmp` was wiped mid-round by the system tmp cleaner, killing
the variant renders and the screenshots Change 48 referenced
(`elbow-hidpi.png`, `circle-group.png` are gone — historical references in
the text above). The puppeteer driver (`puppeteer-core@25.16.0`) and
`railprobe.js` were rebuilt from scratch with the drawer's assertions
(tray radius/fill, accent bar width/color, one-column centres); change 48's
JS fixes stand untouched (chain-opening, bare `.collapsing`, synthetic
`transitionend`, dual 500ms guard).

### Verification for Change 49

- `npm run build` clean — **5.75s**.
- **railprobe — 44/44 ALL PASSED, ZERO PAGE ERRORS**, including *group
  DRAWER drawn* (`10px/rgba(255,255,255,0.11)/3px/rgb(0,84,166)`), *drawer
  rows centred on one column* (`section=35 child=35 foot=35`), *tray
  positioned, 10px radius*, *primary accent bar* (3px/2px/`rgb(0,84,166)`),
  the 36px circle on the column line inside its 44px band, the fold, both
  collapse races, and all three tooltip shapes.
- vitest **209/209 passed (172 files)** — 68.93s.
- PHPUnit — **OK, 1,246 tests / 3,974 assertions** (recorded from the
  re-run; the first attempt hit an intermittent `CrmServiceTest` failure at
  line 99 — green in isolation and in every full run since, pre-existing
  flake unrelated to this CSS/JS work; full story under 2026-10-29).
- Visual: `/tmp/opencode/ffdriver/drawer-hidpi.png` (2× rail crop) — tray,
  accent bar, circle marker.
- Scripts (rebuilt in `/tmp/opencode/ffdriver`): `railprobe.js` (44 checks),
  `drawershot.js` (drawer capture), `trayvariants.js` / `traygeo.js`
  (pre-wipe render/measurement harnesses, kept as history).

### Change 50 — Circles everywhere: the marker shrank to 30px, the accent became the tray's FLUSH edge, and hover previews the marker as a frosted disc (the white-square hover retired)

First look at the drawer: "overall much better, BUT" — the marker circle is
still too big, hover on the submenu should use the circle wrap instead of
the square, and the circle collides with the blue accent line; what's my
opinion before proceeding?

Measured diagnosis, all three complaints confirmed:
- The collision was literal: the 36px marker spans x17-x53, the floating
  accent bar sat at x15-x18 — a 1-2px overlap, not just visual tension.
- The hover "square wrap" was a near-white `#f8f9fa` plate (the dark-theme
  rail hover, `!important` with FIVE attribute-selector variants) — bigger
  AND brighter than the active marker itself, so hover outshone "you are
  here". It also beat single-selector `!important` overrides on specificity,
  which is why the first live variant renders showed the plate instead of
  the disc (found via a computed-style dump: `background-color` stayed
  `rgb(248,249,250)` under an `!important: transparent` — then the pixel
  scan located the white band).
- The marker's own size (36 in a 44px band) read as a plate, not a marker.

Four candidates rendered live (injected CSS, no build): **t5** flush edge +
disc hover everywhere / **t6** floating bar + disc everywhere / **t7** no
bar + disc everywhere / **t8** flush edge + disc on children only (the
user's literal spec). The user picked **t5**.

Shipped:
1. **Marker 36px → 30px** — hierarchy restored (20px section glyph, 16px
   child glyph, 30px marker) and ~5px of clearance from the bar; padding
   `.5rem` → `.25rem` so the 20px section glyph still fits the box
   (20+8=28 ≤ 30).
2. **Accent: floating bar → FLUSH left edge** (`left: 3px`/`radius 2px` →
   `left: 0`/`radius 10px 0 0 10px`) — the bar merges with the tray's own
   border and reads as the drawer's colored side; the collision becomes
   impossible by construction instead of merely avoided.
3. **Hover: square plate + `scale(1.05)` → a 28px frosted disc** centred on
   the glyph, painted as a `radial-gradient` on the square row box. Every
   rail row (section or child) is a 44x44 box with its glyph centred, so a
   centred gradient IS a circle wrap — no pseudo-element, no layout cost.
   28px is deliberately just under the 30px marker: hover reads as "about
   to become position" without outshining position. Ink disc on light
   (6% black), frost on dark (10% white); the icon brightens instead of
   inverting; `:not(.active)` so the marker never gets a second disc.
4. **Why not t8 (children-only circles):** the renders showed two shape
   languages in one 70px strip — white square on section hover, quiet disc
   on children — the exact mixed-feel this whole effort deletes. The rail
   now speaks ONE system: square rows, circle position, circle preview;
   the expanded sidebar stays rectangles.

Also **corrected a Change 49 measurement error**: "child links 36x36 at
x17" was wrong — the first `.nav-item` query had matched the ACTIVE link
(the selected page is its submenu's first row). ALL rail rows are 44x44 at
x13; only the active link shrinks, to the circle. CSS comments updated to
the true geometry.

### Verification for Change 50

- `npm run build` clean (5.75s).
- **railprobe — 47/47 ALL PASSED, ZERO PAGE ERRORS** — new checks: *30px
  CIRCLE* (`w=30 r=50%`), *accent FLUSH on the tray edge*
  (`3px/10px 0px 0px 10px/rgb(0,84,166)`), *hover child row: frosted DISC,
  no plate, no scale* (radial-gradient, `rgba(0,0,0,0)`, `none`),
  *hover SECTION row: same disc*, *hover ACTIVE row: no second disc*.
- vitest **209/209 (172 files)**.
- PHPUnit was in flight when the server restarted; the post-pull re-run is
  OK (1,249 / 3,983) — see 2026-10-29.
- Visual: `/tmp/opencode/ffdriver/drawer-hidpi.png` (2×, recaptured after
  the pull) — 30px marker with air above the flush blue edge.
- Scripts: `markervariants.js` (t5-t8 renders) and `hoverdbg.js`
  (computed-style debugging) were lost to the SECOND `/tmp` wipe before
  their outputs could be committed here; `railprobe.js` and `drawershot.js`
  were rewritten from the session record.

- All changes intentionally left **uncommitted** — version control is handled
  manually.

---

## 2026-10-27 (third pass) — Blur With Borders, Badges That Win, Settings Pages in Line, Shortcuts That Stay Home

Scope: four asks after the second pass — the menu-hover scrim had to frost
*the page and nothing else* (never the rail, never the bar) in both themes; the
Low Stock badge was being hidden behind long product names; the backup password
tab sat misaligned, with the instruction to "make sure every page won't have a
problem like this"; and keyboard shortcuts were to become unique per page,
starting with deleting the tax shortcuts after first checking whether the
taxing system still needs them. Three more the same evening: the scrim had to
sit above *every* table header it blurred (which meant auditing every
`z-index` the app raises — and a real-hammer probe of every table page), the
sidebar had to reapply its open submenus and scroll after each Turbo visit
while F5 still resets, and this second machine's `.env` had to match
`.env.example` key for key. All left uncommitted as usual.

### Change 39 — The menu-hover blur got its borders: page only, both themes

**Scope (the round's brief).** The scrim behind the logo-triggered menu must
blur the PAGE — whole viewport width, at any scroll position — and nothing
else: never the sidebar, never the navbar, in light and dark.

**Root causes (three, all about where the layer lived):**

1. **The scrim sat above the rail.** z 99998 parked it over the sidebar, so
   hovering the title frosted the whole rail together with the page.
2. **The blur rode the bar's scroll state.** Nested in `<header>`, it only
   reacted at the top of the page — scrolled, the page stayed plain and only
   the bar dimmed; and the bar's own backdrop-filter made the header its
   containing block, shrinking the layer to a bar-tall strip (the "only the
   navbar reacts" bug).
3. **The dim was black.** `rgba(0,0,0,.4)` turned the lights out over the
   content for as long as the menu was open.

**Fix (`navbar.css`, `.nav-overlay`):**

- **z 1020 — the ceiling claim was wrong; raised to 1025 by Change 42.** As
  set here it was justified as "content z max 1001, dropdowns 1000" and sat
  below both navigations (rail 1030 at root; bar 1030 at rest, 99999 while
  `.active`), below modals (1055) and toasts (1060). The whole-app audit in
  Change 42 shows the real content ceiling is **1020** — Tabler's `sticky-*`
  utilities and ~50 inline `z-index: 1020` sticky table headers — and at
  *equal* z, DOM order wins: the table (inside the turbo-frame) painted over
  the scrim, so the page blurred but the header stayed sharp. Only the number
  moved, into the empty 1021–1029 gap; the scope guarantees stand unchanged:
  no hover state can frost either navigation, and a menu hover can never dim
  a modal or a toast.
- **The layer lives at body level** (`position: fixed`, outside `<header>` —
  navbar.blade.php), so it blurs the whole page at any scroll, independent of
  `.scrolled`.
- **Milk in light** — `rgba(255,255,255,.55)` keeps hierarchy (panel shadows
  still land) without killing the lights; dark keeps `rgba(16,18,20,.5)`; both
  at `blur(12px) saturate(140%)`. It is shown from the same `.active` state
  via `:has()` — with the overlay outside the header the old descendant
  selector could no longer reach it, and `:has()` derives from the same class,
  so the two can never desync (the descendant selector is kept for the nested
  case).
- **The bar rises above it** — `.nav-container.active` (and the sticky twin)
  lifts to z 99999: the scrim (1020 at the time, 1025 after Change 42) clears
  a *sticky* bar's 1030 but not a plain one, and without the lift the frosted
  layer would veil the bar's own text.
- **The menu panel itself stays near-black in light** (`rgba(20,20,20,.95)`):
  the dropdown's contrast comes from the dark panel, so only the scrim got the
  light treatment; in dark the panel takes the frosted-ladder rung instead
  (translucent + blurred, `--frz-edge` hairline, deep lift). The sidebar's
  `.nav-submenu` was deliberately **not** rethemed — it lives inside the rail,
  which this blur never touches.
- **The `no-animations` menu items got theme-correct colours** (app.css): the
  old unthemed rule painted `#ffffff` items in *both* themes, so with
  animations off the light menu drew white text on the white panel it itself
  paints. Light now follows the palette the `:root` vars set for the animated
  menu, dark keeps white-on-dark — base and hover/focus, both themes.

**Verified (grim — the only honest witness for `backdrop-filter`):** captures
on the real Wayland desktop, light *and* dark: page pixels blurred,
rail/bar/menu pixels sharp at the same instant, in both themes. Headless and
headed Puppeteer screenshots never raster `backdrop-filter`, and `DISPLAY=:1`
is rootless Xwayland (x11grab/ffmpeg returns black) — `grim` screencopy is the
ground truth. Probe numbers are in the Verification block below.

### Change 40 — The badge long names could bury, and the backup tab whose box had no header

Two UI reports from the same day, one shared lesson: things that live in flex
rows need their overflow declared, and the settings-section pattern only works
when *every* section plays by it.

**40a — the Low Stock badge lost to long product names.** In the
notifications' Low Stock tab the row was `title + badge`, and a long product
name pushed the badge out of the visible row. The fix is the same pattern in
all four notification tab partials (`lowstock`, `financial`, `expiring`,
`system`):

- title gets `text-truncate` (ellipsis instead of pushing),
- the badge gets `flex-shrink-0` (it never gives up its space),
- the row gets `gap-2` (breathing room instead of collision),
- the meta row's description truncates while the threshold stays pinned right,
- the system tab's nested flex needed `.min-width-0` — the utility Change 28
  introduced (Bootstrap's `.min-w-0` is absent from this Tabler build); a
  flex child only truncates below its content width if it may shrink, and
  without the reset the ellipsis never appears.

**40b — the backup password tab was an orphan.** `.settings-section-content`
carries `margin-left: 56px` on purpose — it aligns the box under the header's
icon (40px icon + 16px gap). The password tab shipped *without* a
`.settings-section-header`, so its box hung indented with nothing above it,
and its Save button sat outside the box as a `.card-footer`. Fixed: the tab
got the missing header (`ti-key` + "Backup Password", title-only like its
siblings — no new i18n keys) and Save moved inside the content box.

**"Make sure every page won't have a problem like this."** Two sweeps:

1. **Static audit** of every `.settings-section` user: only backup had the
   orphan; profile/currency/accounting/plan were already balanced pairs; the
   preferences page's 8-vs-7 count turned out to be an inline `<style>` block
   counted as a section — a false alarm, not a defect.
2. **Visual sweep** of what the audit could not judge by eye — profile (4/4
   paired), plan (3/3), accounting settings (2/2), plus the settings index
   (9 sections) and backup's own tabs: screenshots reviewed, zero orphans,
   buttons inside their boxes, everything on the 56px rail.

**Verified (fixprobe):** baseline run FAILED the new checks (badges out of
row across the 586 Low Stock notifications, the orphaned content indent);
after the fixes, ALL CHECKS PASSED — 586/586 badges inside their row,
ellipses actually exercised, header present, delta 0 against the sibling tab,
alert/sec edge spacing matched, Save in box. Compiled views
(`storage/framework/views/*.php`) cleared before every run.

### Change 41 — Shortcuts stopped crossing page borders; the tax pair went after the system checked out

**Reports.** (a) "the global keyboard shortcut on tax isn't really used, we
are using a new taxing system than before, so i think its wise do delete the
shortcut since its redundant, you can try checking the taxing system first
before proceeding"; (b) make shortcuts unique per page — "on the product page,
will only allowed a, b, c shortcut but no d because it's redundant" — applied
throughout the whole route or page, sub page, menu, sub menu.

**The tax check first.** The taxing system is alive and load-bearing:
`SalesService`/`PurchaseService` resolve `Tax::active() / ::default() /
::find()` per line, documents carry `tax_id`/`tax_rate`, PPh withholding taxes
ride on the same model, the `admin.setting.tax` CRUD routes exist, and the
newer `update-tax-jurisdiction` setting builds on it. So the *page* stays. What
was redundant is what the report named: the *shortcuts* — tax.js registered
`alt+n` ("New Tax") and `ctrl+s` ("Save Tax") with no guard, while
`script.blade.php` loads every page script on every page.

**Why "global" happened at all.** The documented convention is "each script
guards itself via pathname checks" (script.blade.php) — tax.js violated it. On
each Turbo navigation `clearPageShortcuts()` wipes everything except `/`,
`shift+?`, `escape` (turbo:before-cache/-render), then every script's
`onPageLoad` re-registers **in load order**; `register()` overwrites the same
key, so for the two most-wanted keys the last loader wins *app-wide*. tax.js
loads after product/customer/warehouse/… — on the product page, `alt+n`
answered "New Tax". Deleting the tax pair was the first half of the fix; the
second half makes the class of bug impossible.

**The mechanism — route-scoped registration:**

1. `<body data-route="{{ Route::currentRouteName() }}">` (base.blade.php) —
   Turbo swaps the body, so the attribute always names the current route. The
   existing `#page-route-indicator` already used the same source; nothing else
   reads `body[data-route]`.
2. `register(keys, callback, description, scope)` in ShortcutManager:
   - `'global'` — active everywhere (the three base shortcuts declare this),
   - a route prefix (`admin.product`) — active when it matches `data-route` on
     a segment boundary (`admin.product.create` matches, a sibling like
     `admin.products-old` would not),
   - **omitted** — a snapshot of the route at registration time, so a page
     script registering during its own load is page-scoped automatically:
     element-guarded scripts (category/unit return early unless their modal
     exists) and flag-driven ones (selectable-table's alt+e/alt+d scope to
     whichever page opted in) are covered without touching them.
3. `handleKeydown` skips non-matching scopes; the help modal (`shift+?`)
   filters to the active set before deduping — it lists exactly this page's
   keys and nothing else.
4. `unregister(keys)` keeps its legacy "kill this key everywhere" semantics for
   existing one-argument callers (product.js, sales-order.js,
   purchase-order.js); `unregister(keys, scope)` replaces within a scope, which
   is what `register()` now uses — same key under two different pages can
   coexist.

**What each page looks like now (probe dump):**

| page | keys |
|---|---|
| dashboard | `/`, `shift+?`, `escape` only |
| product index | `alt+e`, `alt+d`, `alt+n` — all `{admin.product}` |
| product create | `ctrl+s{admin.product.create}` |
| categories / units | `alt+e`, `alt+d`, `alt+n`, `ctrl+s` — each scoped to its own route |
| tax | globals only (pair deleted) |
| profile | `ctrl+s{admin.setting.profile.edit}` |
| settings index | `ctrl+s{admin.setting.index}` ("Save System Settings") |

**Verified (shortcutprobe — ALL CHECKS PASSED):** `data-route` correct on all
9 dumps; no tax shortcut anywhere; no inert or empty-scope registrations;
`alt+n` inert on the dashboard (no toast, no navigation) while a live `alt+n`
on the product index lands on `/admin/product/create`; the help modal's row
count equals the active set on dashboard and product and contains `Alt + N`
where expected; a Turbo round-trip dashboard → product → categories →
dashboard ends with the dashboard clean again — the old leak would have left a
stranger's `alt+n` behind.

### Change 42 — Table headers that stayed sharp above the scrim: a whole-app z audit

**The report.** "when i hover to navbar which should be blurring the whole
background, but on a specific page that have a table, the table header didn't
blur, i think it's the z index problem… make sure every page that have a table
won't have this problem or any other thing on the z index."

**Root cause — Change 39's ceiling number was wrong.** The scrim's z 1020 was
justified as "content z max 1001". The audit found the page-content band
actually reaches **1020**:

- Tabler's `.sticky-top` (`.sticky-sm` … `.sticky-xxl`) utilities ship
  `z-index: 1020` by design, so any sticky table header enters the navigation
  band the scrim thought it owned;
- 12 views render `sticky-top`, and ~50 inline
  `style="z-index: 1020"` sticky `<th>` elements ride that exact value;
- **equal z → DOM order decides**, and the `<table>` lives inside
  `turbo-frame#main-content` — later in the DOM than the overlay in the
  navbar include — so the header painted *over* the scrim: page blurs,
  header stays sharp.

**Probe (elementFromPoint stack = paint order; baseline, before the fix):** on
`/admin/sales` and `/admin/product` the stack at the thead centre reads
`["input#selectAll.form-check-input", "th.sticky-top", "div#nav-overlay.nav-overlay", "th.sticky-top", "table.table.card-table…", "div.table-responsive"]`
— the scrim *below* the header — and the product grid had **6/30** content
points landing on sticky headers/sort buttons. `/admin/quotations` and
`/admin/po` fail the same way; `journal`, `chart-of-accounts` and `units`
(plain `<thead>`) already pass. The reported journal page's own header is
plain and never failed the hit-test; the failing class was the sticky-header
pages — which is every page the fix now guarantees.

**Fix — one step into an empty band, plus two strays pulled out of the nav
band:**

- **`.nav-overlay` 1020 → 1025** (`navbar.css`). The 1021–1029 gap was
  unused: content tops out at 1020, navigations start at 1030 (rail at root,
  `body.sticky-navbar .nav-container`, `.fixed-top`, `.navbar .dropdown-menu`),
  then offcanvas backdrop 1040, modals 1050/1055, toasts 1060, loader 10000,
  turbo progress 10001. 1025 clears every sticky table — the 12 current
  `sticky-top` views and any added later — with **zero view edits**, and the
  inline 1020 `<th>`'s keep their table-internal ordering (the whole band
  moves together, above them).
- **Bulk-actions bars (sales + product partials) 1030 → 1024.** Page content
  parked inside the navigation band: with rows selected they sat *above* the
  scrim (1030 > 1025). 1024 keeps them above their tables' sticky 1020
  headers — the reason they were raised in the first place — while dropping
  them under the scrim.
- **Left alone, deliberately:** base.blade's debug badge (10000 — only
  rendered with `enable_debug_mode`; app chrome like the loader, not page
  content), `.offcanvas-backdrop` (1040 — exists only while an offcanvas is
  open, a navigation-layer moment, and mobile has no title hover anyway),
  auth/error pages (1001/9999 — no navbar and no scrim there).

**Verified (zprobe — baseline 5 failures over 4 table pages → ALL 7 PAGES
PASS after):** journal, sales, product, chart-of-accounts, quotations, po,
settings/units — thead centre *and* a body cell both report the scrim
topmost, the 30-point content grid is clean on every page, and the scrim
still never covers sidebar or bar (Change 39's scope rule), with
`overlay=1025@fixed`. Compositor truth (grim, headed Firefox, **real mouse
hover** on the title): sales' sticky header band and the journal table are
blurred while the rail, the bar and the open menu stay sharp in the same
frame — `/tmp/opencode/ffdriver/hover-{sales,journal}.png`.

### Change 43 — The sidebar reapply state from memory: submenus and scroll across Turbo, F5 forgets

**The brief.** "lets proceed on the reapply state from memory" — after the
observation that the submenu select state wasn't kept ("i see you haven't
applied the feature yet"). Every Turbo visit swapped the body, so all submenus
snapped shut and the rail's scroll reset; and because the server renders every
trigger `aria-expanded="false"`, even the page you were *on* had `.active`
buried inside a closed panel.

**Design: memory in the module, not in storage.** No `data-turbo-permanent`
(frozen DOM fights the body swap), no localStorage (F5 must reset — the
memory dies with the page, which is the point of *reapply from memory* rather
than *persist*).

**Mechanism:**

1. **Snapshot the live DOM on `turbo:before-cache`** — `.collapse.show` ids +
   `.sidebar.scrollTop` — rather than bookkeeping from click events:
   class-based state cannot drift from what the user actually saw (a rail
   collapse that already closed panels is captured as closed).
2. **Restore before `initSidebar()` on all four boot paths** (`turbo:load`,
   `usersettingsloaded`, the immediate readyState path, DOMContentLoaded), so
   the sidebar's own boot work — rail state, listeners — lands on its final
   state.
3. **Keying truth:** blade renders `<a data-bs-target="#submenu-N">` →
   `<div class="collapse" id="submenu-N">` → `<ul class="nav-submenu">`
   (no id). Bootstrap toggles the **div**; the `<ul>` never gets `.show`.
   Snapshot, restore and probes all key `.collapse.show` — keying the ul (the
   intuitive guess) is exactly what the first probe run caught.
4. **The active chain derives from the URL**, same match rule as
   `syncActiveState` (`linkPath === path || path.startsWith(linkPath + '/')`),
   walking up `closest('.collapse')` to the outermost panel. It also runs on
   full loads: F5 forgets the remembered extras, but the current page's own
   section still opens — derived state, not memory.
5. **Every restored `.show` gets `bootstrap.Collapse.getOrCreateInstance(el, { toggle: false })`**:
   the rail-collapse path hides open panels through `getInstance()` (a null
   would strand a submenu open inside the 70px icon rail), and `toggle: false`
   stops the constructor flipping an already-open panel closed. Bootstrap
   5.3's `toggle()` reads state from the class (`_isShown()`), so class-level
   restore stays consistent with later clicks; `aria-expanded` is re-synced
   from the restored truth (the server renders all `false`).
6. **Rail gate:** with the rail collapsed, restored panels would lay out
   invisible rows inside 70px (`.sidebar.collapsed` hides submenu items by
   design), so remembered and chain opens apply only to the expanded rail; the
   collapsed rail keeps its load-time look exactly.
7. **Scroll in rAF** — after the same-tick `initSidebar()` has applied the
   persisted rail state (the rail changes the scroll box's height; a pre-init
   set would clamp against the pre-init box).

**Two bugs the probe caught before this shipped:**

- **TDZ — a page error on every load.** The readyState boot path runs
  *during module evaluation*, so `let
  rememberedOpenSubmenus/rememberedSidebarScroll` declared below it was still
  in its temporal dead zone: `ReferenceError: can't access lexical
  declaration 'q' before initialization` (minified name), thrown on load and
  aborting `initSidebar()` with it. The declarations now sit at the top of the
  file, with a comment saying why they must stay there.
- **Wrong keying** (mechanism 3): snapshot and restore keyed `.nav-submenu`,
  so every panel read `NO-ID` and 10/10 probe checks failed.

**Verified (sidebarprobe — ALL CHECKS PASSED):** open two submenus + scroll
250px → Turbo hop keeps both open and the scroll (±0) with the rail still
expanded → close one panel, Turbo to `/admin/accounting/journal`: the chain
re-opens it (`aria-expanded=true`) while the other panel's memory survives
the second hop → full reload: remembered panel **closed**, scroll **0**, rail
state persisted (the existing localStorage contract untouched), chain open.
`diag` after the fix: **zero page errors** (the only console noise left is
the pre-existing, unrelated "Failed to load forecast").

### Change 44 — `.env` ⇄ `.env.example` reconciled for the second machine

**The brief:** "nothing missing between" the two files — key sets identical,
and no secret value ever copied into the example.

- `.env` gained `CACHE_PREFIX=` (before `CACHE_STORE`, the example's slot) and
  the three `MIDTRANS_*` placeholders (`false`/empty, copied from the
  example's lines); its tail was then reordered to mirror the example block
  for block — `…SESSION_LIFETIME → SESSION_SECURE_COOKIE → SESSION_PATH →
  MIDTRANS_* → VITE_*` (it had PATH before SECURE_COOKIE, with Midtrans wedged
  before `PHP_CLI_SERVER_WORKERS`).
- `.env.example` gained `SESSION_SECURE_COOKIE=false`, in the example's own
  alphabetical slot between `SESSION_LIFETIME` and `SESSION_PATH`.
- Example-only comment headers (the Midtrans how-to, `# CONTACT_EMAIL=`) stay
  where they are — comments aren't keys, and no value travels into the
  example.
- Result: **55 keys in each file, `comm -3` empty in both directions**, no
  duplicate keys, and `php artisan config:show session.driver` boots clean.

**Runtime notes:** `CACHE_PREFIX=` is empty exactly as the example ships it;
`config/cache.php:106` therefore uses `''` instead of its
`Str::slug(APP_NAME).'_cache_'` default — a one-time cache-namespace shift on
this machine (existing entries simply age out), after which both machines
share the convention. The Midtrans keys are read with `''`/`false` defaults
already, so adding them changes nothing at runtime.

### Verification for this change set

**Probes (headless Firefox, same credentials/viewport as before):**

- `shortcutprobe` — **ALL CHECKS PASSED** (9 page dumps, 2 live keypresses,
  help-modal parity, turbo round-trip); re-run **green on the final build** of
  this round, after the sidebar and `navbar.css` edits.
- `zprobe` (Change 42 — scrim coverage on 7 table pages: thead centre + a body
  cell + 30-point content grid + sidebar/bar scope, via elementFromPoint) —
  baseline **5 failures over 4 pages** (sales, product incl. 6/30 grid points
  on sticky headers/sort buttons, quotations, po — journal, chart-of-accounts
  and units pass), **ALL 7 PAGES PASS** after the fix with
  `overlay=1025@fixed`.
- `sidebarprobe` (Change 43) — first run **FAIL 10/10** exposing the TDZ page
  error and the `.nav-submenu` mis-keying, then **ALL CHECKS PASSED** on the
  fixed build; `diag` after the fix: **zero page errors**.
- grim compositor captures this round (headed Firefox, real mouse hover on
  `#brand-trigger`): sales' sticky header band and the journal table blurred
  while rail, bar and open menu stay sharp in the same frame —
  `/tmp/opencode/ffdriver/hover-{sales,journal}.png` (headless Puppeteer
  never rasters `backdrop-filter`; grim is the truthful channel).
- `fixprobe` (badge/backup) — baseline FAIL reproduced, then **ALL CHECKS
  PASSED** after the fixes (586/586 rows).
- settings sweep — profile 4/4, plan 3/3, accounting 2/2 sections paired,
  **0 orphans**; screenshots reviewed (dark theme) alongside backup's tabs and
  the 9-section settings index.
- From the blur/swish rounds: nav probe **27/27 twice headless + once
  headed**; swish probe **21/21 with `fluid_layout` on**; grim real-pixel
  captures in light and dark (page blurred, rail/bar sharp).
- Probe hygiene: the auto-opening tutorial is suppressed via
  `localStorage['invent_mag_seen_tutorials']`; the Puppeteer driver lives in
  `/tmp/opencode/ffdriver` (wiped twice mid-task — scripts are re-creatable,
  screenshots land in `/tmp/opencode/shots`).

**Static/build:** `npm run build` green after every edit round (last: 2.50s).
vitest **172 files / 209 tests** passed (65.18s; final re-run 66.08s).

**Env:** `.env` ⇄ `.env.example` key diff — **55 keys each, `comm -3` empty in
both directions**, no duplicate keys, artisan boots on the edited `.env`.

**PHP:** PHPUnit **OK — 1,246 tests / 3,974 assertions** (final re-run
3m45.645s, 241MB peak; earlier run this set: 5m27s), run as
`/opt/lampp/bin/php -d memory_limit=2048M vendor/bin/phpunit`. Environment
note for whoever runs it next: the system `php` (8.5.12) has no
`pdo_sqlite`, and phpunit.xml runs tests on sqlite `:memory:` — so
`php artisan test` on the default binary dies with "could not find driver"
before a single test (and its 128M CLI `memory_limit` exhausts during route
load even before that). XAMPP's PHP (8.2.12) has both. This run's random
seed was tail-truncated; PHPUnit prints it at the head of each run.

**Known pre-existing:** one contrast failure —
`.system-notif-action.btn-warning` (navbar.css:415) — present before these
rounds, not introduced by them.

---

## 2026-10-26 (second pass) — Three Regressions After Brand Centring: Bar Content Held Still, Focus Paint That Only the Keyboard Gets, Twin Red Dots — and the Swish, Kept Deliberately

Scope: the three reports that came back after Change 27's brand-centring
work — the bar's content swished sideways (and could read as empty) while the
sidebar opened or closed, the navbar theme toggle kept its white focus paint
after a plain mouse click, and the two red notification dots no longer played
the same animation. All three are fixed, probe-verified, and left uncommitted
as usual. A fourth request the same day then reversed part of the first fix:
with the content pinned, the bar had lost its swish entirely — the ask was to
keep the "swished to the right and gone" motion, but on purpose (Change 38).

### Change 35 — The bar's content stopped swishing sideways while the sidebar slides

**Symptom (user report).** "After we centered the invent-mag… when I open or
closing the sidebar, the navbar have a animation where it hiding all the
content in the navbar… all the content in the navbar is swished to the right
and gone… toggling the sidebar close will showing it." The title itself was
correctly centred ("the good news is the invent-mag indeed centered") — only
the rest of the bar misbehaved.

**Root cause — two, one measurable and one only visible on a GPU:**

1. **The container clamp re-fired on every toggle.** The bar's content sat in
   a `container-xl` (max-width 1320px) centred in the bar. The bar's width
   *crosses* that line mid-slide — rail 1518px → open 1308px at a 1600px
   window — so each toggle reshaped the container: its left edge leapt +111px
   and everything inside it, the right-hand icon cluster above all, swished
   with it (cluster right edge 1473 → 1572 on open, back on close). That is
   the deterministic half of the report.
2. **The frosted layer was rebuilt on every frame.** The bar's glass is
   `backdrop-filter: blur(14px) saturate(160%)` on `.nav-container::before`,
   which promotes the bar to its own GPU layer; the ancestor's
   `margin-left`/`--main-ml` transition reflows the bar every frame, and on
   some GPUs that layer drops its contents outright — the bar reads as an
   empty sheet for the length of the animation and recovers when the slide
   ends, which is why toggling the other way "showed it". Headless Firefox
   could not reproduce the disappearance: computed styles and frame-by-frame
   screenshots show the text painted in **every** frame. The fix is therefore
   defensive — stop the layer from existing during exactly those frames.

**Fix, three parts:**

1. **Fluid container** (`navbar.css`): `.nav-container > .container-xl {
   max-width: none }`. The container *is* the bar now, and the bar's right
   edge never moves, so the cluster sits at the same x in both states
   (1572 → 1572). The centring formula from Change 27 is untouched by this:
   `left: calc(50% - var(--main-ml)/2)` lands on the window centreline for
   any container that fills — or is centred in — the bar, so the title stays
   at 794/794 in both states. Below 1400px the clamp never engaged, so those
   widths are pixel-identical to before.
2. **Glass off while sliding** (`navbar.css`):
   `body:has(.main-content.main-content-fast-close,
   .main-content.main-content-smooth-open) .nav-container::before {
   backdrop-filter: none }`. `:has()` is what lets a descendant's transient
   state reach the bar; with the bar's tint at .8–.99 alpha the blur is
   barely visible for those 150–300ms, and the plain-path paint removes the
   layer-dropout class of bug entirely.
3. **The phase classes clean up after themselves** (`menu-sidebar.js`):
   module-level `schedulePhaseCleanup()` drops `*-fast-close` /
   `*-smooth-open` from both `.sidebar` and `.main-content` 400ms after each
   toggle, guarded by `getAnimations().length > 0` so a newer slide keeps
   its classes and schedules its own cleanup (amended by Change 38: the
   wait is bounded now — after four passes the classes come off anyway,
   because the bar's content hides while they exist). Previously each toggle only
   added its own phase class and the pair lingered **forever** — with both
   present, the later-in-source `smooth-open` rule won the cascade, so every
   close ran the 0.3s open easing instead of the 0.15s fast path (and the
   new glass rule would never have re-enabled the filter). The two close
   paths also remove the opposite phase class explicitly now.

**Verified (probe-fixes):** geometry — container fills the bar (1518/1518
rail, 1308/1308 open), right cluster **1572 in both**, title **794/794 in
both**; mid-close transition durations **[150, 150]**; phase classes empty at
settle; `backdrop-filter: none` while the phase classes are on and
`blur(14px) saturate(1.6)` at rest.

### Change 36 — The theme toggle kept its focus paint after a mouse click

**Symptom (user report).** "When I turn from light to dark, then hovered out
of the icon the whole icon become white… I need to click out other random
space for it to not clicked / hovered even though we are not hovering at it."

**Root cause.** 17 paint rules in `navbar.css` keyed on plain `:focus` with
`!important` backgrounds — `#343a40` in light, `#ffffff` in dark — across the
universal nav-link override, its icon twin, the bell/dropdown variants and
their dark-mode mirrors. `:focus` survives a mouse click until the element
blurs, and the toggle is `<a href="#">` with `preventDefault()`, so it *keeps*
focus after being clicked. In dark mode the stuck rule painted the icon's own
white background under the white glyph — a white square — and only clicking
somewhere else blurred it away. `:focus-visible` is the right predicate: it
arms for keyboard focus (the accessibility signal stays) and not for mouse.

**Fix.** Every `:focus` in `navbar.css` → `:focus-visible` — 17 occurrences in
four shapes (`:focus,` ×6, `:focus i,` ×4, `:focus {` ×5, `:focus i {` ×2).
Zero plain `:focus` remain in the file. Reviewed and deliberately left alone:
the sidebar's `.nav-link.active:focus` (it sits in a list with `:hover` and
the always-on `.active` paint — clicking it shows exactly what the link's
state says), `app.css`'s `body.no-animations` mega-menu item rule (mirrors
`:hover`, and those items navigate away), input/`superadmin` focus rings, and
`dark.css` (which nothing loads).

**Verified (probe-fixes):** click the toggle, hover away — background
transparent (`rgba(52,58,64,0.002)` light), icon colour distinct from any
paint, focus still on the element, `:focus-visible` **false** after the mouse
click; then Tab + Shift+Tab back onto it — `:focus-visible` **true**
(keyboard path intact). Built CSS: 18 `:focus-visible`, **0** plain `:focus`.

### Change 37 — The two red notification dots became twins again

**Symptom (user report).** "The red dot animation is not the same as the
sidebar one, make sure its consistent."

**Root cause — three drifts between the bar's dot and the sidebar's:**

1. The navbar dot carried a second animation the sidebar twin never had:
   `.nav-item.dropdown:hover #notification-dot { animation: pulse 1s }` plus
   its `@keyframes pulse`. Hovering the bell swapped the bar's dot from wave
   to pulse while the sidebar's kept waving.
2. `.sidebar-actions { overflow: hidden }` clipped the sidebar dot's `::after`
   ripple (the wave scales it to 3× the dot), so the sidebar's ripple looked
   cut short beside the navbar's free-running one. The clip only ever mattered
   while the row itself collapses to `width: 0`.
3. Markup: navbar dot `border border-light` vs sidebar dot `border
   border-secondary` — two different ring colours on the same red disc.

**Fix.** Remove the navbar-only hover rule *and* its keyframes (the wave is
now the one animation everywhere); move `overflow: hidden` from base
`.sidebar-actions` onto `.sidebar.collapsed .sidebar-actions`; change the
sidebar dot's markup to `border-light`. The keyframes removal was audited
first: `pos-receipt.css` defines its own *different* `pulse` (0.95↔1 vs
navbar's 1↔1.2 — on the receipt page both shipped and collided), and
`purchase-order.css`'s `animation: pulse` consumer is an orphan stylesheet
that is not a Vite input and is linked from no view, so nothing live depended
on the deleted definition.

**Verified (probe-fixes):** both dots — `1px solid rgb(252, 253, 254)` on
`rgb(214, 57, 57)`, 10×10, `animation: none`, `::after` running
`wave/1.8s` on both, `.sidebar-actions` `overflow: visible` when the sidebar
is expanded; zoomed crops of both bells taken for visual comparison.

### Change 38 — The swish came back, as a deliberate animation

**Request (same day, follow-up to Change 35).** "We want the swishing right
animation to be still used, not removed — when we open the sidebar all the
content in the navbar swished to the right and gone, it's like disappear, we
want that animation, not removing it entirely." Removing the clamp glide had
left the bar perfectly static on every toggle: right geometry, no life. The
behaviour chosen in follow-up: **swish right, vanish, return.**

**What it does now.** Opening the sidebar swishes the bar's whole content row —
icon cluster, centred title, nav links — right and off the edge, and it STAYS
gone for as long as the sidebar is open: an empty frosted strip is the effect,
not a fault. Closing is what brings it back — the content slides in from the
right and fades up while the rail collapses. The bar itself (glass, seam,
border) never moves or hides. Resting geometry is untouched: the fluid
container keeps content at 1572/794 in both sidebar states, which is what
makes the animation legible — it leaves a fixed spot and returns to it.

**Fix — CSS only, keyed to the sidebar's state, not to a timer**
(`navbar.css`, beside the fluid container). The first cut keyed on the
`main-content-fast-close / -smooth-open` phase classes; the shipped rule keys
on the class that actually drives the rail's width:

| state | rule | timing |
|---|---|---|
| rest (rail collapsed) | `.nav-container > .container-xl, .container-fluid` carries `transition: transform, opacity` | `--anim-slow` 0.3s + house `cubic-bezier(.25,.8,.25,1)` — this is the return |
| open (`body:not(.no-animations):has(.sidebar:not(.collapsed))`) | `translateX(180px)` + `opacity: 0` + `pointer-events: none` | `--anim-fast` 0.15s `ease-out` — the rail's own fast-close values |

A *state* cannot strand where a *timer* can: `.collapsed` is on or off, so
there is no cleanup pass, no retry bound, no frame in which the classes outlive
the slide — and a fresh page load lands in the right shape with no JS at all,
because the sidebar defaults to collapsed when nothing is stored
(`menu-sidebar.js`: `savedState === null ? true`). Transitions still take their
timing from the *destination* state, so expanding runs the 0.15s exit and
collapsing runs the 0.3s return: one selector drives both halves, no JS hook,
no class to leak. The values mirror the sidebar's own `.nav-text-fade-out` /
`.nav-text-slide-in` pair, so the bar swishes with the same grammar as the
menu labels. Three details that took a pass each:

1. **`pointer-events: none` while gone.** An invisible bell you can still
   click is worse than no bell; un-matching the selector restores pointer
   events with everything else.
2. **Both containers.** `fluid_layout` swaps the bar's inner container to
   `.container-fluid` (Change 35's clamp fix already covered it) — every swish
   rule, rest and open, now carries `.nav-container > .container-xl,
   .container-fluid`, so the animation cannot silently vanish in fluid mode.
3. **Skip, don't null.** The block sits inside
   `@media (prefers-reduced-motion: no-preference)` and both selectors carry
   `body:not(.no-animations)`. With the kill switch nulling every transition,
   an unguarded open rule would drop the content to opacity 0 *without moving
   it*; letting the selector never match skips the effect outright.

**Hardening — the phase classes still time the glass, so coming off is still
a guarantee** (`menu-sidebar.js`): the swish no longer depends on
`fast-close/smooth-open`, but the glass rule below does — they are the only
frames where the GPU layer is being rebuilt. `schedulePhaseCleanup()` still
defers to a newer toggle's fresh slide, retries up to four passes (~1.6s) and
removes the classes anyway after them; without the bound, any unrelated
animation running on `.main-content` at the 400ms check left the classes on
forever and the bar kept painting with `backdrop-filter: none`. The probe hits
this for real: the dashboard's layout work pushes the open slide past 400ms,
and the retry is what clears the classes at ~800ms. `initializeSidebar()`
strips both class pairs on load — a Turbo snapshot cached mid-toggle restores
with them on, and a plain load schedules no cleanup for them.

**Verified (probe, both layout modes):** in-page sampling during both toggle
directions — opacity interpolates through mid-values (a fade, not a snap),
reaches 0 while the rail is open, shift peaks at **+180px right**; after close:
opacity 1, `transform: none`, resting spot **1572 / 794**, a hit-test from the
bell's centre reaches the nav content with `pointer-events: auto`, phase
classes clear. Re-run under `container-fluid`: **21/21** swish checks. The full
`light brand nav cards frosted shortcuts alerts notif contrast light contrast
dark` sections re-ran green after this change.

### Verification for this change

**Static/build:** `npm run build` green after every edit round; built
`navbar-*.css` carries 18 `:focus-visible` / 0 plain `:focus`, the `:has()`
glass rule, the fluid container, 1 `@keyframes wave` and 0 `@keyframes pulse`.

**Probes (headless Firefox):** `probe-fixes` **22/22** (geometry, fast path,
glass, focus, dots). Full suite per section — `light brand nav cards frosted
shortcuts alerts notif contrast light contrast dark` — **all pass**. Two
environmental flakes recorded: `light` fails whenever an earlier probe left
the *server* theme on dark (restore with `restore-theme.js`, it is a
`PUT /admin/settings/update-theme-mode`), and `shortcuts` failed once with
`idx=0` in the real-vs-synthetic Alt-key fallback and passed on two
consecutive re-runs — `selectable-table.js` is untouched.

**Suites:** vitest **172 files / 209 tests** passed. PHP **1,246 passed
(3,974 assertions)**, run after the final edit round (`php artisan test`,
345s, seed 1792994004).

---

## 2026-10-26 — Frosted Dark Elevation, Visible Light Edges, Window-Centred Brand, Server-Wins Theme, Leftover Clicks & Motion, Contrast Gate at Zero, Alt+E/Alt+D Identity

Scope: the consistency pass after Changes 16–20. Dark mode stopped being a flat
sheet and gained real elevation; theme initialization stopped overriding the
server's answer; three leftovers that made the UI feel broken — a click-eating
loader, a dropdown that ignored the theme setting, a submenu that lagged on
collapse — were cleared; the contrast gate was defined strictly enough to
report **0 failures in both themes** without lying about it; and the Alt+E /
Alt+D row shortcuts finally look and announce edit vs delete. The second half
of the day turned to the light theme and to the title: light mode gained the
edge-and-lift system dark mode got in Change 21 (its borders were not weak —
in two files they were *absent*, because the stylesheet spent tokens that do
not exist), the navbar title became window-centred in **every** navigation
mode, and every page that ships its own stylesheet was audited against the
same ladder.

### Change 21 — Dark mode gained an elevation ladder (frosted cool-gray)

**Symptom.** After the palette was neutralized (Tabler's blue cast removed),
dark mode read as one flat sheet: cards melted into the body, every container
looked like the same piece of paper.

**Root cause.** Two properties of neutralizing:

1. The cast had been doing the separation job — Tabler's own dark surfaces
   (`#151f2c` body, `#182433` card, `#1b293a` active) differ by *hue*. Removing
   it left body `#1c1e20` and card `#212325` five luminance points apart with
   nothing else telling them apart.
2. The light-theme card shadow (`rgba(0,0,0,.075)`) is invisible on a dark
   page, so the depth cue light mode leans on does not exist in dark at all.

**Fix — elevation by height**, declared in the new `tokens.css` at
`:root[data-bs-theme="dark"]` (specificity 0,2,0 beats Tabler's
`[data-bs-theme=dark]` at 0,1,0):

| token | value | role |
|---|---|---|
| `--frz-edge` | `rgba(255,255,255,.06)` | hairline on cards — how frosted glass catches light |
| `--frz-edge-strong` | `rgba(255,255,255,.11)` | hairline on things that float |
| `--frz-surface-1/2/3` | `#212325` / `#26282a` / `#2b2d2f` | card / dropdown / modal |
| `--frz-shadow-1/2/3` | black lifts, alpha `.45 → .5` | a dark page's shadow grows as the layer rises |

The ladder: sidebar `#121416` (18,20,22) → body `#1c1e20` (28,30,32) → card
(33,35,37) → dropdown (38,40,42) → modal (43,45,47). Every rung keeps the
palette's neutral cast (rgb within 4 of each other, the `new = (x, x+2, x+4)`
rule above it in the file), so the ladder reads as **depth, not as a second
colour**. `app.css` spends the tokens; the global loader's dark panel moved off
the retired dark-blue `rgba(24,36,51,.9)` to the frosted body
`rgba(28,30,32,.92)`, blur kept.

**Verified (probe `frosted` / `hover`):** card = 1px `rgba(255,255,255,.06)`
edge over the body rung with a `rgba(0,0,0,.45)` lift, header split by a
hairline, dropdown paints `rgb(38,40,42)`, sidebar `rgb(18,20,22)` with a white
8% edge, submenu rail 1px, loader still frosted; POS hover paints the dropdown
rung while row text stays `rgb(252,253,254)`.

### Change 22 — Theme initialization: the server wins

**Symptom.** `theme_mode` could read "dark" on the settings page while the app
itself came up light. Any browser with empty localStorage — new device, cleared
storage, headless run — silently discarded the saved setting.

**Root cause.** `theme-initializer.js` *unconditionally* overwrote
`data-bs-theme` with localStorage → OS preference → `light`. But every
interactive layout already renders the attribute server-side
(`data-bs-theme="{{ $themeMode }}"`), and `theme-toggle.js` persists every
change to `/admin/settings/update-theme-mode` — so the server always holds the
user's current choice, and the initializer was replacing a fact with a guess.

**Fix.** If `documentElement` already carries `data-bs-theme`, return: the
server value is authoritative. Only fall back to localStorage, then the OS
preference, then `light` when no layout provided one. The contract is recorded
in the diff as a comment above the guard.

**Verified:** with an attribute present the initializer is a no-op; both probe
themes (which set `data-bs-theme` themselves) keep passing, and a cleared-storage
visit now keeps whatever the server rendered.

### Change 23 — Two stylesheets nothing loads, and a class the app never sets

**Symptom.** Palette edits to `dark.css` / `alerts.css` never appeared on
screen. POS stayed half light-themed in dark mode: its row hover forced
`color: #000 !important` onto every descendant while the quantity/price inputs
kept their dark background — exactly where the digits vanished.

**Root cause — two different failures:**

1. **`dark.css` and `alerts.css` are dead files.** Nothing imports them:
   `grep -rn 'alerts\.css\|dark\.css' resources/ vite.config.js package.json`
   matches only `docs/ACTUAL_TREE.txt`. The one-line recolor in `alerts.css`
   (`#1e293b` → `#26282a`) and the 152-line recolor in `dark.css` are inert —
   no `@import`, no Vite entry, no `<link>`.
2. **`pos.css` / `pos-receipt.css` shipped but keyed on `.theme-dark`** — a
   class no Blade view sets and no JS adds. The dark branches never matched;
   the *base* branches applied in both themes, which is why one pale hover with
   forced-black text painted everywhere.

**Fix.** Re-scope `pos.css` / `pos-receipt.css` to the attribute the app
actually uses: base rules under `html:not([data-bs-theme="dark"])`, dark rules
under `[data-bs-theme="dark"]` — dark hover now takes the frosted rung
(`--frz-surface-2` + `--frz-edge`) and forces no colour at all. The hardcoded
white `#totalPriceContainer` became a dark rung + edge. The dead pair was left
in place after proving it dead; deleting them is a repo decision, not a fix.

**Verified (probe `hover`, dark):** POS row hover = `rgb(38,40,42)` + white
edge, `strong` and the quantity input both still `rgb(252,253,254)`.

### Change 24 — Motion leftovers: one card lift, a settings collision, a leaked delay

**a) Card hover — one lift for every card.** The "something is interactive
here" cue existed only on hand-picked cards. Now every `.card` rises 2px and
deepens one rung (`--frz-shadow-2` in dark, `0 8px 24px rgba(0,0,0,.12)` in
light; `!important` because most cards carry Bootstrap's `!important`
`.shadow-sm`/`.shadow-0`). The lift sits inside `@media (hover: hover)` so touch
devices never get a sticky hover, and is guarded by `body.no-animations`
(transform off, shadow kept — a colour change, not motion) and by
`prefers-reduced-motion` — **the first use of that media query in the repo**.
Headless Firefox reports `hover: none`, so the probe verifies the shipped rules
inside the media block instead of a live lift.

**b) The no-animations dropdown collision.** With `enable_animations=false`,
one rule painted the navbar menu `rgba(20,20,20)` in **both** themes — turning
animations off in a light workspace produced near-black dropdowns, a settings×
theme collision rather than a style choice. Now split: dark
`rgba(28,30,32,.98)` + frosted edge, light `rgba(255,255,255,.98)` + border.

**c) The submenu's leaked `transitionDelay`.** The staggered reveal sets an
inline `style.transitionDelay` per open-submenu item and nothing ever cleared
it, so the delay rode along on *every* later transition the same `<li>`
performed — collapsing the sidebar made those items sit still for up to 320ms
before moving. The delay now clears itself once the reveal window
(`REVEAL_WINDOW_MS` + 50) has passed; the per-item step is computed as
`REVEAL_WINDOW_MS / (n − 1)` so the parent and child sweeps land in the same
320ms window instead of stacking a second ramp.

**Verified:** probe `cards` passes (transition + all three shipped hover rules
inside `@media (hover: hover)`); probe `frosted` confirms the sidebar rail.

### Change 25 — The navbar is glass at every scroll position, and the scrim covers the page again

**Symptom.** Two: (1) the bar changed *material* while scrolling — flat opaque
at the top, translucent + blur past 50px — and its dark `.scrolled` shadow was
light-theme `rgba(0,0,0,.1)`, invisible on the dark page; (2) hovering the logo
blurred only the navbar strip — the rest of the page stayed crisp, as if the
overlay were bar-height tall.

**Root cause of (2)** — the gotcha that cost the most time: **a
`backdrop-filter` makes an element the containing block for its
`position:fixed` descendants.** `#nav-overlay` lived *inside* `<header>`, so
the moment the bar carried glass, the viewport-sized fixed overlay resolved
against the bar and collapsed to a bar-height strip.

**Fix.**

- Glass is always on now; `.scrolled` only adds elevation (denser tint, deeper
  lift) — one material, two heights. The filter lives on `.nav-container::before`
  at `z:-1`, never on the container itself: the pseudo-element still samples the
  page through the bar's translucent tint (frosted look unchanged) while
  sitting below the bar's text.
- `#nav-overlay` moved out of `<header>` to body level (the Blade comment
  records why), reached by `body:has(.nav-container.active) .nav-overlay` since
  the old descendant selector cannot cross the header boundary. At body level it
  also stacks *below* the bar, so the bar and its open menu stay crisp while
  everything behind them blurs — `blur(8px) saturate(140%)`. Bar/menu z-index
  `99999 !important`.

**Verified (probe `nav`, dark):** overlay = `1588 × 1000` with
`blur(8px) saturate(1.4)` at scroll 0 **and** at scroll 400, the sidebar is
covered (`elementFromPoint` returns the overlay), bar glass reads
`blur(14px) saturate(1.6)`.

### Change 26 — The loader finished looking done and kept swallowing clicks

**Symptom.** After a page finished, clicks on the notification bell, nav links
and table rows hit nothing — a reload cleared it.

**Root cause.** `page-loader.js` sets `display` / `opacity` / `pointer-events`
**inline** on `#global-loader`, and inline styles outlive the
`.is-loading-page` `!important` rules. `hideLoader()` removed the class and
waited for a timeout to set `display:none` — in that window the overlay was
still `opacity: 1`, `pointer-events: all`: a full-screen, hit-testable layer
over a page that already looked finished.

**Fix.** `hideLoader()` fades and disarms *immediately* (`pointer-events: none`,
`opacity: 0`) — the transition still plays because `display` stays `flex` for
the 400 ms timeout — and `showLoader()` re-arms opacity/pointer-events so the
next navigation can't inherit a dead overlay.

**Verified:** after any navigation the loader's inline style reads
`display: none; opacity: 0; pointer-events: none` (computed `pointer-events:
none`) — measured on the live login→dashboard flow.

### Change 27 — Brand centring: window-centred in every navigation mode

**Symptom (user report).** "The title is centred when we are only using the
navbar, but not when we enable 2 navigation types — it is placed a little bit
to the right."

**Why the previous verification said "no code change".** It measured the
title against the **bar's** centre, where the error was 0 in every state —
and the reading is true: the title is perfectly centred in the container. But
the container is `.main-content`, which the sidebar pushes right by
`--main-ml` (280px open / 70px rail / 0 navbar-only), so the bar's centre is
half the sidebar width right of the *window's*. Bar-centred therefore means
**+140px** right of the window centre with the sidebar open, +35px on the
rail, and 0 with navbar-only: exactly the report — invisible in one mode,
"a little to the right" in the other. The reference point, not the title, was
the bug.

**Fix — three parts:**

1. `#navbar-brand-title { left: calc(50% - var(--main-ml, 0px) / 2) }`
   (`navbar.css`): half the offset *is* the distance from the bar's centre to
   the window's, so subtracting it lands the title on the window centreline in
   all three states. `start-50` came off the markup — its `!important` beats
   any normal `left` rule, so the value now lives in exactly one place.
2. `--main-ml` is `@property`-registered (`<length>`, inherits) in
   `menu-sidebar.css` and set next to every `.main-content` margin-left —
   including the fast-close and smooth-open transition lists — so the title
   tracks the bar *while* the sidebar slides instead of snapping to a stale
   offset mid-flight.
3. `min-width: 0` on `.main-content`: the dashboard chart canvas has a
   min-content width of 1320px and flex children default to
   `min-width: auto`, so the bar stretched to `[280, 1600]` — 12px past the
   visible edge. That alone put the title at 800 against a centre of 794 even
   with the formula in place; content-sized minimum, not layout.

**Verified (probe `brand`):** title centre == window centre (**delta 0**)
with the sidebar open, on the rail, and navbar-only — `794/794` in all three
while the bar's own centre moves 934 → 829 → 794 (`deltaBar −140 / −35 / 0`,
which is now the expected shape). 20 frames polled across the slide: **2
mid-flight samples, worst delta 0**. Title inside the bar and clear of the
icon clusters in both sidebar states.

### Change 28 — `.min-width-0`: the utility the markup used but CSS never defined

**Symptom.** In the dashboard's System Alerts list a long product name pushed
its row wide and shoved the quantity and date out of the card.

**Root cause.** The markup already carried `class="min-width-0"` — **no
stylesheet ever defined it**. Without it, flex children keep
`min-width: auto` (content-sized), so `.text-truncate` never engages: the name
cell grows to its full width instead of shrinking.

**Fix.** Define the utility (`.min-width-0 { min-width: 0 }`), which makes
every existing usage — including the name cells in `system-alerts.blade.php` —
actually shrink.

**Verified (probe `alerts`):** all 7 rows: name ellipsizes *inside* the row
(`scrollWidth > clientWidth`), the quantity stays inside the card, computed
`min-width: 0px`.

### Change 29 — Notifications de-blued

The notification chrome in `navbar.css` still carried the retired slate/navy
palette — `#1e293b`, `#0f172a`, `#94a3b8`, the `#1e293b → #0f172a` header
gradient, `rgba(30,41,59,.8)` overlays and `var(--tblr-bg-surface-dark,
#1e293b)` fallbacks — the same blue cast the rest of dark mode had already
shed. All repainted to the neutral rungs.

**Verified (probe `notif`, dark):** notification rows and the pill strip both
`rgb(28,30,32)`, and a full-page scan for the nine retired tuples finds **zero**
occurrences in any opaque background or text colour.

### Change 30 — Two stale tokens were behind 32 dark-mode contrast failures

**Symptom.** The dark contrast sweep failed 32 elements on the dashboard alone:
`th` text, badge counts, nav-link labels, breadcrumb, pagination, toast and
alert text — all at `rgb(102,115,130)` (3.06–3.46:1).

**Root cause.** Tabler re-declares only `--tblr-secondary-fg` for dark and
leaves the base pair alone, so `--tblr-secondary` / `--tblr-secondary-rgb`
stayed at the **light-theme** `#667382` everywhere they're read as *text*.
Component-level `!important` patches (`.text-secondary` etc.) fixed some
consumers but not the ones reading the token directly.

**Diagnosis method — canary repaint.** Repainting just those two tokens
magenta turned **all 32** magenta: the tokens, not 32 selectors, were the
source. (Recorded in a comment in `tokens.css` beside the fix.)

**Fix.** All three flip together at `:root[data-bs-theme="dark"]`:
`--tblr-secondary: #a8b0b8`, `--tblr-secondary-rgb: 168,176,184`,
`--tblr-secondary-fg: #1c1e20`. Consumers split cleanly — text/nav usages need
the lift (`#a8b0b8` clears **6.8–7.6:1** on every dark rung), while `.btn`
fills pair with `-fg`, so a fill gets light background + dark text: an injected
`.btn-secondary` measures **7.6:1** (was 4.84).

### Change 31 — The contrast gate, made honest — and light theme to AA

**The gate's rule** (encoded in the probe so it cannot drift): the semantic
accent palette — `success/info/warning/danger/primary` plus Tabler's yellow and
stock blue, **seven exact values** — is Tabler's own brand decision, checked on
**fg *or* bg** (it covers accent text on neutral *and* neutral text on an accent
fill, e.g. white on `.btn-warning` `rgb(247,103,7)` = 3.04:1, Tabler's own
pairing) and reported as a baseline, never as a failure. Everything else —
every neutral, muted, heading, badge, link, placeholder, every colour this
project chose — must pass AA. The transient `#tutorial-modal` onboarding
overlay is dismissed before auditing (it is not page content). Result: **dark
`failing (gate): 0, accent baseline: 4`**; **light `0 / 16`**.

**Live light-theme measurement (1600px dashboard)** — status text that missed
AA, fixed on its own merit even where the rule above reports such a hue as a
baseline: badges paint the vibrant status colour on a
light tint of itself — `#2fb344` on `#eaf7ec` = 2.5:1, `#f76707` on `#fef0e6`
= 2.7:1 — and the trial banner's count inherits `.alert-warning`'s `#f76707`
straight onto the page (2.9:1); all 10–14px normal text needing 4.5:1. Fixed
**text-only**: same hue, pulled down the lightness ramp until each clears 4.6:1
(`#217e30`, `#b74c05`, `#cc2a2a`, `#616e7c`, `#0b62f1`). Fills, borders, icons
and dark mode untouched. The dark-theme counterparts — `.bg-*-lt` badges paint
their own colour with `!important` (3.1–3.3:1) and `.btn-ghost-secondary` sat
at 3.26:1 — got the same treatment: `#f06565`, `#a8b0b8`.

**Verified:** probe `contrast light` and `contrast dark` both gate at 0.

### Change 32 — Alt+E / Alt+D: edit and delete finally look like what they do

**Reported.** *"For the Alt+E it's good to have the blue colored line on the
left, but in Alt+D let's make it delete, and make the toast show that we are
deleting, not editing — or maybe a separator for both the editing and delete
toast showups."*

Three real defects hid under that:

1. **Alt+D could not take over.** The handlers were shaped
   `if (!selectionModeActive) {…} else if (lastActionType === "delete") {…}` —
   while an *edit* selection was active, Alt+D matched neither branch and did
   nothing. The session stayed blue-line edit, and every toast after it kept
   talking about editing.
2. **The toasts did not separate the modes.** Both modes fired the same generic
   `Selected / Item: X` on every arrow step; only the one-shot activation text
   differed.
3. **Delete often had nothing to click.** `performAction`'s delete branch knew
   exactly two ids — `#deleteUserModal`, `#deleteModal` — and only on
   `<button>`. Real pages use `#deleteTaxModal`, `#deleteEmployeeModal`,
   `#deletePeriodModal{id}`, `#deleteReconciliationModal`, an `<a>` item
   (quotations), and rows with *no dropdown at all* (users and categories are
   plain buttons) — those all died with "Could not find action dropdown".

**Fix** (`selectable-table.js` + one rule in `app.css`):

- **A colour separator, as asked.** Alt+E keeps the blue `--tblr-primary` line;
  Alt+D paints the same 3px inset line in `--tblr-danger`
  (`tr.selected-row.selected-delete > td:first-child`) — identical geometry, so
  colour alone tells the modes apart and nothing moves a pixel.
- **Toasts follow the mode.** Arrow steps toast `Editing / Item: X` (info) vs
  `Deleting / Item: X` (danger); activation announces `Edit mode` / `Delete
  mode` with the same split; exit says `Edit/Delete selection mode
  deactivated`. Nothing inside a delete session reads "editing".
- **Either key switches the mode.** Pressing the other shortcut during an
  active selection flips `lastActionType`, keeps the highlighted row, repaints
  the line and re-announces — instead of being ignored. The same key with a row
  selected still confirms (Alt+D, Alt+D → delete; or Alt+D, Enter).
- **Delete finds its target.** Dropdown path: any `.dropdown-item` whose
  `data-bs-target`, `href` or label contains "delete" — any modal id, `<a>` or
  `<button>`. No-dropdown rows fall back to the row's own buttons/links, matched
  target → class/title → text, so `.delete-user-btn` and categories' `#deleteModal`
  button work too; edit got the same fallback. The dead `selector` variable that
  restated the old hard-coded ids went with it.

**Verified — probe `shortcuts`, 24 checks, all real keystrokes, three page
shapes:** categories (direct buttons), users (`.delete-user-btn`), product
(action dropdown): blue line + `Editing` + `Edit mode` on Alt+E; ArrowDown walks
the rows; Alt+D from edit mode switches in place (row kept) to red line +
`Deleting` + `Delete mode` with **no** edit-flavoured toast in the stack;
Enter reaching `#deleteModal`, `#deleteUserModal` and the edit page
(`/admin/product/edit/647`). Full suites after the change: vitest **172 files
/ 209 tests**, PHP **1,246 passed (3,974 assertions)**, `npm run build` green,
and the probe regression suite (`nav frosted hover alerts notif cards brand`)
all pass.

### Change 33 — Light theme gained a visible edge (the seam and its shadow)

**Symptom (user report).** "For the light mode, I still can't see the border
line — can you make sure it's a little interesting too? Make sure the border
line shadow can properly be seen, the same for the overall Invent-MAG."

**Root cause — light mode had the mirror image of Change 21's problem.**
Dark mode's depth cue vanished because shadows die on a black page; light
mode's never existed in the first place:

1. Tabler ships cards with `.border-0` — **no edge at all** — and
   `.shadow-sm` = `0 2px 4px rgba(0,0,0,.075)`, a shadow that never escapes
   the card's own footprint on a `#f6f8fb` body. Cards and page read as one
   sheet.
2. The navbar's seam was `1px solid #dadfe5` against white content — about
   3 luminance units — and the only shadow under it was Tabler's inset twin
   of that same colour. A line that reads as no line, under a bar that does
   not float.

**Fix — the light twin of the frosted ladder**, declared in `tokens.css` at
`:root` beside the dark one:

- `--lt-edge: rgba(4,32,69,.16)` and `--lt-edge-soft: rgba(4,32,69,.1)` —
  the navy this light theme already draws its translucent borders with;
  .16 for a container edge, .10 for dividers inside a surface.
- `--lt-shadow-surface` / `-surface-hover` / `-bar` / `-bar-scrolled` — on
  white a shadow reads by **reach**, not by darkness: wide, low-alpha, two
  rungs per surface (`0 6px 16px rgba(16,24,40,.1)` resting,
  `0 10px 26px … .15` hovered; the bar pair is light's twin of
  `frz-shadow-1/2`).
- `app.css`: `html:not([data-bs-theme="dark"]) .card` forces
  `1px solid var(--lt-edge)` + `--lt-shadow-surface` with `!important` —
  same reasoning as the dark rule: `.border-0`/`.shadow-sm` are
  `!important` utilities and most cards carry one. Hover takes
  `--lt-shadow-surface-hover`.
- `navbar.css`: the bar's border keeps its 1px of layout but is painted
  **transparent**, and a `::after` hairline draws it instead —
  `linear-gradient(90deg, rgba(16,24,40,.2), rgba(0,84,166,.34)
  calc(50% - var(--main-ml,0px)/2), rgba(16,24,40,.2))`. Cool navy at the
  edges rising to the brand blue **exactly on the window centreline** — the
  same `calc` the title uses (Change 27), so the blue kiss sits under
  "Invent-MAG" in every navigation mode. The hairline casts its own tight
  `0 2px 6px rgba(16,24,40,.12)` shadow so the line reads as the top edge
  of the page; the bar itself lifts with `--lt-shadow-bar`, deepening to
  `--lt-shadow-bar-scrolled` on scroll (hairline deepens with it).
- Selectors are `html:not([data-bs-theme="dark"]) …` at (0,3,1) — mutually
  exclusive with the dark rules, so the two themes can never fight over a
  declaration.

**Verified — probe `light`, 16 checks:** seam transparent with the gradient
hairline (1px, carrying `rgba(0,84,166,…)`, with its own shadow); bar lifted
at rest and deeper scrolled; card `1px solid rgba(4,32,69,.16)` with the
two-layer shadow; token hygiene across every bundle; plus `contrast light`
and `contrast dark` both still at **gate 0**. Screenshots confirm the seam
and the card edges read on the dashboard.

### Change 34 — Pages with their own stylesheet: two tokens that never existed

**Symptom.** The receipt and settings pages kept the invisible-border
problem even after the system above landed.

**Root cause — `var()` on a token nothing defines.** An undefined custom
property in a declaration makes it *invalid at computed-value time*: the
shorthand resets (`border-style: none`) and **no border is drawn at all** —
not a faint one, none. Two files spent such tokens:

- `settings.css`, 4× `--tblr-border-color-rgb` (2 shorthands, 2 dark
  overrides) — the token appears nowhere in Tabler, this repo's CSS, or any
  build output.
- `pos-receipt.css`, 3× `--tblr-border-color-light` — likewise undefined,
  so the customer-section, every table row and the dashed payment separator
  printed **no line** (the declarations fell back to `currentColor`'s
  nothing).

**Fix, per file:**

- `settings.css` → `--lt-edge-soft` (section separators), `--lt-edge`
  (content box), `--frz-edge` for both dark overrides.
- `pos-receipt.css` → `var(--tblr-border-color)` for all three (the file's
  other seams already used it: `#dadfe5` light / `#2a2c2e` dark).
- The receipt page loads **only** `tabler.min.css` + `pos-receipt.css`
  (`receiptbase.blade.php` never pulls `app.css`), so the global card rules
  can never reach the paper. `.receipt-card` therefore got its own per-theme
  edge + lift with `!important` (the markup carries `.border-0 .shadow-sm`
  utilities), and the `@media print` selectors were raised to
  `html:not([data-bs-theme="dark"]) .receipt-card,
  html[data-bs-theme="dark"] .receipt-card` at (0,2,1) — a bare
  `.receipt-card` (0,1,0) would have lost the specificity war and the
  customer would have received a printed drop shadow.
- `pos.css`: `#productList` was `1px solid gainsboro` — an off-palette warm
  grey with no dark counterpart — → `--lt-edge` + `[data-bs-theme="dark"]
  #productList { border-color: var(--frz-edge) }`.

**Checked and deliberately left alone:** `tutorial-modal.css` (its
`.topic-card` *is* a `.card`, so the global edge/lift rules win on their own;
its `#e2e8f0` list slabs are already visible), `auth.css` `.page-center`,
`error.css` `.empty`, `menu-sidebar.css`'s sidebar edge, `.sticky-col`
(every live usage carries `bg-body`), and `#totalPriceContainer` (no markup
or JS consumer found anywhere in the repo).

**Verified:** probe `light` asserts the hygiene statically (no
`border-color-rgb`, no `tblr-border-color-light`, no `gainsboro` left in
`public/build/assets`, settings + receipt spending the real tokens, print
selectors shipped), and both pages were screenshotted with live computed
values — settings section `1px solid rgba(4,32,69,.1)`, content box
`1px solid rgba(4,32,69,.16)`, receipt card `1px solid rgba(4,32,69,.16)`
with its lift, rows `1px solid #dadfe5`. Full regression after the whole
pass: `npm run build` green, probes `light cards brand nav frosted shortcuts
alerts notif` + `contrast light` + `contrast dark` all pass, vitest **172
files / 209 tests**, PHP **1,246 passed (3,974 assertions)**.

---

## 2026-10-25 — Motion Consistency, Backup 500, Low-Stock Cell, Visible Keyboard Selection

Scope: four defects reported together — a 500 on the backup settings page, an
unreadable Alt+E selection, a low-stock modal that buried the number under a
badge, and motion that felt uneven across hover, loaders, tables, login,
sidebar and navbar.

### Change 16 — Backup settings page returned 500

**Symptom.** `/admin/settings/backup` threw 500.

**Root cause** — a permissions mismatch between two users, not application
code. `storage/logs/laravel.log` showed Flysystem's real error:

```
local.ERROR: Unable to list contents for 'backups', shallow listing
```

`getBackupFiles()` calls `Storage::disk('local')->files('backups')`, i.e. reads
`storage/app/backups`. That directory existed and was empty, but:

| | owner | mode |
|---|---|---|
| `storage`, `storage/app`, `backup-temp` | `emberfall:daemon` | `drwxrwxrwx` |
| **`storage/app/backups`** | `emberfall:emberfall` | **`drwx------`** |

PHP-FPM/Apache runs as `daemon` (confirmed by probing `posix_geteuid()` through
the web server: `uid=2 user=daemon`), while the shell that ran `backup:tenant`
is `emberfall`. The directory was `0700` against a web server that is neither
its owner nor its group, so `readdir` failed and Flysystem wrapped the
`EACCES` as `UnableToListContents`.

**Fix.** Two layers:

- Immediate — `chmod 777` on `backups` and `backup-restore`, matching the mode
  `storage`, `storage/app` and `backup-temp` already use throughout this project.
  (`chgrp daemon` was not an option: `emberfall` is not a member of `daemon`.)
- Durable — `BackupTenant` created the directory with `mkdir(..., 0755, true)`,
  whose result is still at the mercy of the creating process's umask. It now
  `@chmod`s to `0777` on every run, so running a backup self-heals the
  directory instead of re-introducing the failure.

**Verified:** the page returns **200** and renders backup/restore controls.

### Change 17 — Low-stock modal put the number inside a badge

The CURRENT STOCK column rendered `<span class="${badgeClass}">3 <small>(Low
Stock)</small></span>` — a coloured badge plus a redundant suffix, directly
beside a THRESHOLD column that showed a bare number. The badge's only signal
was "this is low", which the modal's own title and red header already say, and
the modal is by definition a list of low-stock rows.

Now renders the bare quantity, matching the THRESHOLD cell next to it.
`getStockClassAndText()` is untouched — it still drives the product index and
bulk-stock views, which do need to distinguish low/normal at a glance.

**Verified:** 668 rows, **0 badges**, every stock cell a bare integer.

### Change 18 — Alt+E / Alt+D selection was invisible

**Symptom.** Arrow keys moved a selection you could not see; only the toast
said where you were.

**Root cause.** `selectable-table.js` was correct — it adds `.selected-row` at
line 62 and removes it at 90. But `grep -r selected-row resources/css/` returned
**only the JS file**: the class had never been given a style.

Two things made the obvious fix silently do nothing:

1. The product table is `table card-table table-vcenter` — **no `table-hover`**,
   so a `.table-hover tbody tr.selected-row` selector matches nothing.
2. Tabler paints every cell as
   `.table>:not(caption)>*>* { background-color: var(--tblr-table-bg); box-shadow: inset 0 0 0 9999px ... }`.
   A `background-color` on the `<tr>` sits *underneath* that opaque cell paint
   and is never visible. (First attempt did exactly this and measured
   `rgba(0,0,0,0)`.)

**Fix** — route the tint through Tabler's own mechanism:

```css
tr.selected-row { --tblr-table-bg-state: var(--tblr-active-bg); }
tr.selected-row > td:first-child {
    box-shadow: inset 3px 0 0 0 var(--tblr-primary),
                inset 0 0 0 9999px var(--tblr-active-bg);
}
```

Custom properties inherit, so setting the variable on the row is what actually
tints each cell. `--tblr-active-bg` is defined per theme, so light and dark are
one rule with no duplicate — **theme-aware by construction rather than by a
second selector**. The 3px primary bar on the first cell is the "this row"
marker.

No transition, deliberately: with one keypress per row, an instant jump reads as
direct manipulation where a fade reads as lag.

**Verified, both themes:** light `rgba(0,84,166,0.04)` tint, dark `#1b293a`
tint, plus `rgb(0,84,166) 3px` marker on the first cell.

### Change 19 — One motion scale, and a reveal that had never lined up

**Symptom.** Hover, loaders, tables, login, sidebar and navbar each felt
slightly different — and the sidebar's submenu items visibly arrived after
their parents.

**Inventory.** 98 transitions/animations across 7 stylesheets used **13
durations**: `0.1 0.15 0.2 0.25 0.3 0.35 0.4 0.5 0.6 0.8 1 1.5 1.8` seconds.
`menu-sidebar.css` alone used five. A search for `transition-delay` /
`animation-delay` found **none**, confirming every "late" animation was a JS
stagger (Change 12) rather than CSS.

**Fix.** New `resources/css/tokens.css` holding one three-step scale:

| token | value | used for |
|---|---|---|
| `--anim-fast` | 0.15s | feedback: hover, press, focus |
| `--anim-base` | 0.2s | default transitions, dropdowns, badges |
| `--anim-slow` | 0.3s | entrances and reveals |

98 durations remapped to those three steps. `0.1/0.15 → fast`,
`0.2/0.25 → base`, `0.3/0.35/0.4/0.5/0.6 → slow`. Infinite decorative loops
(`wave 1.8s`, `pulse 1s`) were left alone — they are not part of a
responsiveness scale. `transition-delay` and `animation-delay` were untouched
(there are none).

**Why a shared file instead of a `:root` block.** `auth.css` is a *standalone
Vite entry* — the login page never loads `app.css`. Defining the variables only
in `app.css` would have made every login transition resolve an undefined
`var()` and silently disappear. `tokens.css` is `@import`-ed by both, which is
verified by the probe reading `.3s` from `--anim-slow` on the login page itself.

**The actual mismatch.** Aligning durations exposed a pre-existing bug that the
uniform numbers had been hiding:

```css
.sidebar .navbar-nav .nav-link { transition: all var(--anim-base) }  /* (0,3,0) wins */
.nav-item-animate              { transition: all var(--anim-slow) }  /* (0,1,0) loses */
.sidebar .navbar-nav .nav-item { transition: all var(--anim-slow) }  /* (0,3,0) wins */
```

The parent link revealed at **0.2s** while its child item revealed at
**0.3s**, because the two *higher-specificity* rules disagreed. `.nav-item-animate`'s
declared 0.3s never took effect at all. Both governing rules now read
`--anim-base`, so declared and effective values are the same number whichever
rule the cascade picks — no specificity games to keep it true.

**Result:** sidebar parent and child both **0.2s** (probe-verified), and
cold-load reveal dropped to **195ms** for all 43 links — from 3,266ms before
Change 12.

**Also fixed:** `resources/css/app.css` contained byte `0x97` (a Windows-1252
em-dash) inside a comment, which made `file` report "Non-ISO extended-ASCII"
and made grep treat the whole stylesheet as binary. Replaced with a proper
UTF-8 em-dash; it is now valid UTF-8 and searchable.

### Change 20 — The table loading overlay was hardcoded white

**Symptom.** "Table loading animation after search" — every search in dark mode
flashed a white panel over the table.

**Root cause.** The animation is `initServerSearch()`'s overlay in
`partials/sorting/serverSearch.js`: a 300 ms debounce, an `AbortController`, and
a dimming wash plus `spinner-border` covering `#invoiceTableContainer` while the
same index URL re-fetches with `?search=`. The wash was literal:

```js
overlay.style.cssText = "position:absolute;inset:0;display:none;...;background:rgba(255,255,255,.55);...";
```

Because the node is built in JavaScript, **no stylesheet rule can reach it** —
there is no `[data-bs-theme="dark"]` selector to add, which is exactly why
`.page-loader` *has* one (app.css:47) and this does not. `data-server-search` is
on 8+ index pages (product, category, supplier, warehouse, purchase/sales orders
and returns), so it flashed on all of them.

**Fix** — `rgba(var(--tblr-body-bg-rgb), .55)`. Tabler publishes the surface as
an RGB triplet rather than a hex precisely so `rgba()` can accept a `var()`: the
same values `.page-loader` spells out by hand, now resolved at runtime.

**Verified while actually on screen:** `rgba(246,248,251,0.55)` in light,
`rgba(21,31,44,0.55)` in dark, appearing at 302 ms (the debounce) and clearing
when the response lands. The spinner itself was already correct — it is the
same Bootstrap `spinner-border` used everywhere else in the app, so nothing
about the motion changed, only what it is painted on.

---

## 2026-10-25 — Create-Page Latency, Dead Notification Bell, Sidebar Reveal, Deploy Gate

Scope: everything that was still slow, dead, or ungated after Changes 1–9.
The headline is that the create pages were **3–6× slower than their own index
pages** for a reason that had nothing to do with lazy-loading or Turbo, and the
notification bell had been dead on every navigation for a reason that looked
like Turbo but wasn't.

### Change 10 — Create pages ran one query per product

**Symptom.** `po/create` took **2.93s** and `sales/create` **5.58s** while their
index pages sat at 0.6s. Every non-modal create redirect page felt heavy, and
the gap grew with catalog size.

**Root cause.** Two accessors in `Product` used the query-builder form of the
relation, which **ignores an eager load entirely**:

```php
// app/Models/Product.php (before)
public function getTotalStockAttribute()
{
    return (float) $this->productWarehouses()->sum('quantity');   // ->sum() = new query
}

public static function getMainWarehouseId()
{
    $mainWarehouse = Warehouse::where('is_main', true)->first();   // never memoized
    return $mainWarehouse ? $mainWarehouse->id : null;
}
```

`productWarehouses()` returns a *builder*; `productWarehouses` returns the
loaded collection. Calling a method on the builder issues SQL no matter what was
eager-loaded, so `with('productWarehouses')` was doing nothing. The create
blades read one accessor per row — `po/create` reads `stock_quantity`
(1,169 queries), `sales/create` reads `main_warehouse_stock` (1,169 × 2) — and
`getPurchaseCreateData()` never eager-loaded the relation at all.

Measured on tenant 3 (1,169 products), one pass over four accessors:

| | queries | time |
|---|---|---|
| before | 5,847 | 4,822 ms |
| `po/create` loop after | 0 | 43 ms |
| `sales/create` loop after | 0 | 16 ms |

**Fix.**

- All three accessors go through `stockRecordFor()`, which prefers the loaded
  collection and only falls back to a query when the relation isn't there.
- `getPurchaseCreateData()` now eager-loads `productWarehouses` — **unfiltered**,
  because the blade reads a total across every warehouse.
- `getMainWarehouseId()` is memoized **on the container**, not in a static.

**Why the container and not a static cache.** A static survives between tests;
`ProductStockTest` has three tests that each expect a *different* main-warehouse
id from a fresh database, and `phpunit.xml` runs them in random order. The
container is rebuilt per test (and per request under FPM), so the memo is
naturally scoped. It also carries an invalidation hook: `Warehouse::booted()`
forgets it on `saved`/`deleted`, because `WarehouseService::setMainWarehouse()`
switches the main warehouse mid-request.

**Why the memo stores `0`, not `null`.** `Container::bound()` is `isset()`-based.
Binding a bare `null` for "no main warehouse" reads as *unbound*, so
`app('invent-mag.main-warehouse-id')` tried to build a class from the string and
threw `BindingResolutionException`. That is not hypothetical — it failed 7 tests
on the first run. The memo now holds `0` and returns `?: null`.

**Why `getStockForWarehouse()` was left querying.** It is called by
`deductStock()` and `addStock()`, which mutate the row they just read. Serving
that from a possibly-stale or filtered in-memory relation would let stock be
validated against one row and written against another. Read paths use the
relation; mutation paths hit the database.

**Why the three filtered eager loads are safe.** `SalesService`,
`ProductService` (search, limit 50) and `ProductController::modalView` narrow
`productWarehouses` to the main warehouse. All three also call
`setAppends(['main_warehouse_stock'])`, so `total_stock` is never serialized on
those paths — and `firstWhere('warehouse_id', $mainId)` on a main-warehouse-only
relation still returns the right row. `getTotalStockAttribute()` documents the
invariant: don't narrow the relation on a page that reads totals.

**Verification.**

- 0 differences across **4,676 values** (1,169 products × 4 accessors) against a
  baseline captured before touching anything.
- Rendered HTML vs. database: **1,169 options × 2 pages, 0 mismatches** —
  `data-stock` is the cross-warehouse total on `po/create` and the main-warehouse
  stock on `sales/create`.
- `po/create` **2.93s → 0.76s**, `sales/create` **5.58s → 0.80s**, `pos` 1.15s →
  0.66s. Every create page now returns 200 and sits at index-page parity
  (0.4–0.8s, stable over 4 runs).

### Change 11 — The notification bell was dead after every navigation

**Symptom.** Load a page, click the notification bell, nothing happens. F5 and
it works.

**Not Turbo.** The obvious suspect — Turbo swapping the body and orphaning a
listener — was disproved rather than assumed: a probe marking the bell node
showed Turbo *does* replace it and `turbo:load` *does* fire, and the re-bound
handler (`dataset.bound="true")` was present and correct every time.

**Root cause.** The global loader stayed hit-testable after it stopped being
visible:

```html
<div id="global-loader" style="display: flex; opacity: 1; pointer-events: all; …z-index: 10000">
```

The CSS has two states — `.is-loading-page .page-loader { display:flex !important;
opacity:1 !important; pointer-events: all !important }` while loading, and
`.page-loader { display:none; opacity:0; pointer-events:none }` when idle. But
`hideLoader()` only *removed the class*, and **inline styles beat the base rule**.
So for the 400ms until its `setTimeout` set `display:none`, the loader was an
invisible full-screen layer at `z-index: 10000` over a page that already looked
finished. Every click landed on it.

**Fix.** `hideLoader()` now disarms immediately (`pointer-events: none`,
`opacity: 0`) so the fade still plays and clicks pass through; `showLoader()`
re-arms both.

**Evidence (A/B, raw coordinate clicks):**

| | control | fixed |
|---|---|---|
| loader | `display:flex opacity:1 pointer-events:all` | `display:flex opacity:0 pointer-events:none` |
| hit-tested at bell centre | `#global-loader` | `i.ti.ti-bell` |
| click target (capture + bubble) | `#global-loader` | `i.ti.ti-bell` |
| result | **DOES NOTHING** | **OPENS** |

6/6 destinations open the dropdown on a click made the instant `turbo:load`
fires. Turbo is kept — it is load-bearing across ~15 modules and is what makes
sidebar navigation seamless.

*(Harness detail worth knowing: `page.click(selector)` can dispatch at the
element origin and slip straight past an overlay, which reports a false pass.
Only `page.mouse.click(x, y)` at recorded coordinates exercises the real
hit-test.)*

### Change 12 — The sidebar revealed itself at 80ms per link

**Symptom.** "Sometimes only purchase order, sales order and product show up
first, then the rest is late — like it's late to line up after the animation."

**Root cause.** Every reveal delay scaled with **position in the menu** rather
than with a budget:

```js
navItems.forEach((item, index) => setTimeout(() => item.classList.add("visible"), index * 80));
```

43 `.nav-link`s → the last one waits **3,360ms** (measured 3,266ms from a cold
load), with `.nav-item-animate { opacity: 0 }` holding it invisible. Two more
ramps stacked on top: `idx * 100ms` for submenu children on every
`shown.bs.collapse`, and `index * 30ms` for the text labels on sidebar expand
(another 1.26s). `initSidebar()` runs on every `turbo:load`, so the whole
cascade re-ran on each navigation.

**Fix.** One shared `REVEAL_WINDOW_MS = 320` for all four sites; the per-item
step becomes `WINDOW / (n - 1)`, so the menu is always up within the same
window no matter how many entries it has — it *scales down* instead of growing.

**Result:** cold-load reveal **3,266ms → ~470ms** (320ms cascade plus the shared
0.3s CSS fade). The tail no longer lags, and short menus don't crawl either.

### Change 13 — Releases could deploy on a red suite

**Symptom.** Nothing stopped a tag push from building and shipping without a
single test having run.

**Fix.** `ci.yml` gained a `workflow_call:` trigger and `deploy.yml` gained a
`test` job that calls it — same file, same commands, so a push and a release can
never be judged differently. `build-and-push-image` needs `test`, and
`deploy-to-server` needs both.

The extension list also gained `pdo_sqlite, sqlite3` and `pcntl`, which is not
optional polish: `phpunit.xml` runs the whole suite on `:memory: sqlite`, and
`enforceTimeLimit="true"` needs `pcntl`. Local PHP has all four, so CI now
matches the environment that was verified.

### Change 14 — Three routes to controller methods that never existed

`admin.customer.create`, `admin.supplier.create` and `admin.warehouse.create`
each mapped to a `create()` method that was never written, so all three returned
**500**.

Before deleting anything, all three were checked for references — route names
and URL paths, across views, `resources/js`, the built bundle, `app/` and
`tests/`. Zero hits. The create UX for all three is a modal
(`data-bs-toggle="modal"` on each index page), so these were leftovers from
before the modal flow. Removed: **500 → 404**, and the index pages that host the
modals still return 200.

### Change 15 — OPcache

**Production (`Dockerfile`).** Added a guarded `docker-php-ext-install opcache`
(some `php:*-fpm` tags already ship it) plus a tuning ini:
`memory_consumption=192`, `interned_strings_buffer=16`,
`max_accelerated_files=20000`, and `validate_timestamps=0`.

`validate_timestamps=0` never re-checks a file's mtime, which is only safe
because every deploy runs `docker-compose pull` + `up -d` — that replaces the
container and therefore the cache; application code is baked into the image, and
the deploy script re-runs `view:cache`/`route:cache` into an empty cache. The
Dockerfile says so, and says to set it back to `1` if deploys ever stop
recreating the container.

**When does it start being useful in production?** From the first request the
container serves — it is not a one-off optimisation but a per-request saving on
every request, forever. Laravel boots ~1,000 files (app + `vendor/`); without
opcache each request re-reads, re-tokenizes and re-compiles all of them, then
throws the result away. With opcache the bytecode stays in shared memory, so
that whole stage disappears. Typical gain on a Laravel app is 20–50% of
server-side time; the cache needs one pass to warm, then holds it. It is the
single largest remaining lever for repeated-request latency.

**Local (XAMPP).** `/opt/lampp/etc/php.ini` is root-owned and PHP is loaded as
mod_php, so there is no root-free injection point (`PHP_INI_SCAN_DIR` is read at
module init, not per request). A ready script is at
`/tmp/opencode/enable-opcache.sh` — verified against a copy of the real `php.ini`:
run 1 appends, run 2 is a no-op, and `php -c` on the result reports
`with Zend OPcache v8.2.12` with no double-load error. Run it with:

```bash
sudo bash /tmp/opencode/enable-opcache.sh && /opt/lampp/lampp restart
```

It deliberately uses `validate_timestamps=1` + `revalidate_freq=0` locally, so
files are re-checked every request and edits still show up immediately.

---

## 2026-10-24 → 2026-10-25 — Global Admin Performance + Whole-Catalog Server-Side Search

Scope: every admin paginated list. Two distinct production-grade bugs were
found, fixed, and generalized. Both are written up as interview Q&A in
[`INTERVIEW_QA.md`](./INTERVIEW_QA.md) §54, and the general class of problem
they belong to is covered in [`FILTERING_PITFALLS.md`](./FILTERING_PITFALLS.md).

Changes 1–5 landed on 10-24. Changes 6–8 (10-25) are the follow-through: the
Alt+E/Alt+D fix that was left outstanding, six pre-existing defects surfaced by
verifying the search work, and the two test-infrastructure fixes without which
neither suite could run.

### Change 1 — Notification view composer ran once per rendered view

**Symptom.** Every admin page took **5–6 seconds** to render. Query count per
request was enormous. The product index page rendered 61 views and executed
61 identical notification queries.

**Root cause.**

```php
// app/Providers/ViewServiceProvider.php (before)
View::composer(['admin.layouts.*', 'admin.*'], function ($view) {
    $view->with('dueNotifications', $this->getDueNotifications());
});
```

A view composer registered against a wildcard fires **once per view matched**,
not once per request. The admin layout tree renders ~61 views per page, so the
notification query — which touches `subscription_orders`, joins `plans`, and
does a date-range scan — ran 61 times, identically, for one page load.

This is the classic *"N+1, but the N is the view tree"* mistake. It is invisible
in code review because the composer body looks like a single logical call site.

**Fix.** Request-level memoization — compute once, reuse for the life of the
request:

```php
View::composer(['admin.layouts.*', 'admin.*'], function ($view) {
    $view->with('dueNotifications', $this->dueNotifications());
});

private function dueNotifications(): array
{
    if (app()->bound('admin.notification_snapshot')) {
        return app('admin.notification_snapshot');
    }

    return tap($this->getDueNotifications(), function ($data) {
        app()->instance('admin.notification_snapshot', $data);
    });
}
```

**Why a container binding rather than a `static` variable or a cache entry.**

| Alternative | Why rejected |
|---|---|
| `static $cache` | Survives across requests under PHP-FPM worker reuse and in queue workers → stale notifications for minutes/hours. |
| `Cache::remember('due_notifications', 60)` | A cache store round-trip is ~1–3 ms; the whole point was to remove 61 queries, not replace them with 61 cache reads. Also introduces a cache-invalidation bug where a resolved payment doesn't clear the badge. |
| Register the composer on the layout only | Fragile — any nested `@include` outside the exact path silently loses the data. |
| **Container instance (chosen)** | Scoped to exactly one request. No cross-request leakage under FPM/queue. No I/O. Unresolvable by accident. |

**Result.** All admin pages now render in **0.30–0.50 s** (dashboard 0.46 s,
product 0.43 s, every list 0.31–0.36 s).

**Generalization note.** The same class of bug applies to `@include` inside
loops, to `View::share()` with an expensive closure, and to Blade `@php` blocks
calling services. Any "compute this once for the layout" logic must be
memoized per request explicitly — PHP will not do it for you.

---

### Change 2 — Search filtered only the rows currently in the DOM

**Symptom.** On the product list, searching `elco` returned **56 catalog
matches** but displayed only the handful that happened to be on page 1 of the
current page size. No error. No warning. The user simply believed the catalog
had 4 matches.

**Root cause.** The shared search helper filtered the live DOM:

```js
// resources/js/admin/partials/sorting/search.js (before)
export function initSearch() {
    const searchInput = document.getElementById("searchInput");
    const tableRows = document.querySelectorAll("#invoiceTableBody tr");

    searchInput.addEventListener("keyup", function () {
        const searchTerm = searchInput.value.toLowerCase();
        tableRows.forEach((row) => {
            row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? "" : "none";
        });
    });
}
```

`tableRows` is a snapshot of the **current page only**. With 56 matches spread
across 6 pages, page 1 holds ~10 of them; the other ~46 are not in the DOM, so
they cannot possibly be shown. Client-side filtering over a paginated table is
structurally incapable of whole-catalog search — it is a filter, not a query.

This is a *silent wrong answer*, which is the worst failure class: it looks
like it works.

**Fix.** Query the server for the whole catalog and swap only the table region:

- New shared module `resources/js/admin/partials/sorting/serverSearch.js`.
- Opt-in via `data-server-search` on `#searchInput`. The existing guard in
  `search.js` returns early when that attribute is present, so a page keeps
  client-side filtering until it is explicitly migrated. **No page can ever
  have both behaviors fighting over the same rows.**
- 300 ms debounce + `AbortController` so a fast typist never triggers more than
  one in-flight query, and out-of-order responses cannot land.
- Fetches the **same index URL** with `?search=...` — no separate endpoint, no
  duplicated query logic, no chance of the search endpoint drifting from the
  list endpoint.
- Parses the response, swaps `#invoiceTableContainer .table-responsive` and the
  `[data-pagination-footer]` footer in place. No full page reload.
- `history.replaceState` keeps the URL in sync, so refresh, bookmark, back
  button and "share this filtered view with a colleague" all work.
- LIKE wildcards in the term are escaped with `addcslashes($search, '%_\\')` so
  a user typing `100%` searches for `100%` rather than matching everything.

**Why partial-swap instead of a full page reload.** A full reload is correct
and was the pre-existing behavior on the Quotation list. It was rejected
because it re-runs the entire render pipeline (~0.35 s of server work plus a
full asset/layout repaint) on every keystroke burst, and it loses scroll
position and in-flight modal state. The partial swap costs one XHR and repaints
~1% of the page. The full-reload pages were converted for consistency.

**Why re-initializing List.js after every swap.** List.js binds its sort
handlers to the `<button class="table-sort">` elements inside `<thead>`, which
lives *inside* the swapped region. After a swap those buttons are new nodes with
no handlers — sorting would silently stop working. The shared module therefore
re-runs `initListJs()` after each swap. This was a latent bug on the product
page from Change 2's first iteration and is fixed by the generalization.

**Where server search landed (this change):** Product (reference
implementation, already live), Sales, Purchase Order, Sales Return, Purchase
Return, Customer, Supplier, Warehouse, Quotation, Category, Unit.

Categories and Units previously had **no search input at all** — Categories had
35 rows across 4 pages with no way to narrow them. The search UI was added as
part of this change, not just the backend filter.

Tax is deliberately **out of scope** (6 rows, single page).

**Superadmin is deliberately untouched** — it runs on a separate auth guard
(`super_admin`) with its own controllers and views. No shared code path was
modified in a way that affects it.

---

### Change 3 — Entries selector silently dropped the active search

**Symptom.** Search for something, then change "entries per page" from 10 to
50 → the search term vanished and the full unfiltered list came back.

**Root cause.** The page-size selector navigated by rewriting the URL from
scratch:

```html
<select data-onchange="window.location.href='?entries=' + this.value;">
```

`'?entries=' + value` replaces the entire query string. `?search=elco` is
gone. On the product page this was doubly broken: a JS handler
(`initializeEntriesSelector()`) correctly preserved the params, but the
`data-onchange` attribute fired *after* it and overwrote the URL.

**Fix.** Build the URL with the URL API so existing params survive:

```html
<select
    data-onchange="(() => { const u = new URL(window.location.href); u.searchParams.set('entries', this.value); u.searchParams.delete('page'); window.location.href = u.toString(); })();">
```

The Sales list already did this correctly via a form post whose controller
calls `request()->except(['entries', 'page'])` — it is the pattern the others
were aligned to.

**Why this bug matters beyond the annoyance.** It is the same *failure shape*
as Change 2: filter state that lives in one place (the URL) is rebuilt from
somewhere else (a hardcoded param list), and any param not on the list is
dropped. Every "rebuild the query string" call site is a latent version of this
bug.

---

### Change 4 — Duplicate row-filtering handlers removed

**Symptom (latent).** Sales Return, Purchase Return and Warehouse each carried
their **own** copy of the client-side row filter, all of them iterating
`#invoiceTableBody tr` and toggling `style.display`. With server search enabled
these would have fought the server for control of the same rows — the server
would swap in correct results and the stale local handler would then hide most
of them again.

**Fix.** Guarded with the same `data-server-search` early-return that
`search.js` uses, so each page has exactly one source of truth for row
visibility.

**Why not just delete them outright.** They are the fallback path for any page
that has not been migrated. Guarding keeps the client-side path working and
guarantees the two paths are mutually exclusive — the property that actually
prevents the collision.

---

### Change 5 — Selectable-table keyboard navigation made swap-safe

**Symptom (latent).** `layouts/selectable-table.js` (the Alt+E / Alt+D row
navigation) captured `tbody` and its `rows` array **once, at init**. After a
search swapped the table contents, those references pointed at detached nodes —
arrow-key navigation would walk rows that are no longer in the document.

It also attached a `document`-level `keydown` listener. Naively re-running the
initializer after a swap would stack a second listener, and destructive
shortcuts (`performAction`) would fire **twice** — a double-delete.

**Fix.** `tbody` and `rows` became lazy getters that re-resolve from
`#invoiceTableContainer` on every access, instead of being closed over at init.

**Why lazy getters rather than "call init again".** Re-initializing is the
obvious move and the wrong one: the double-`keydown`-fires-twice failure is
silent, catastrophic, and only reproduces under a destructive action. Making
the references lazy keeps exactly one listener and one set of closures alive,
which is the property that makes double-firing impossible rather than merely
unlikely.

---

### Change 6 — Alt+E / Alt+D targeted the wrong table

**Symptom.** On the Product, Sales and Purchase Order indexes, Alt+E (select
row) and Alt+D (delete selected) appeared to do nothing. On single-table pages
(Customer, Supplier, Warehouse, Categories) they worked correctly.

**Root cause.** `initSelectableTable()` runs `tables.forEach(...)` and
registers the shortcuts with `shortcutManager` inside each iteration.
`register()` **unregisters the previous binding first**, so with several
`.table-vcenter` tables on a page the *last* one in DOM order wins. Product,
Sales and PO all carry hidden modal tables (`lowStockModal`,
`expiringSoonModal`, …) after the list table — and those have zero rows, so the
shortcut activated against an empty set.

Proof that this predates this work: the diff for this change contains **0**
lines touching `forEach`, `register()` or `unregister()`. Only the ownership
decision was added.

**Fix.** Decide ownership once, before the loop:

```js
const ownerTable =
    Array.from(tables).find((t) => t.closest("#invoiceTableContainer")) ||
    Array.from(tables).find((t) => t.querySelector("tbody tr")) ||
    tables[0] || null;

tables.forEach((table) => {
    if (table !== ownerTable) return;
    // ...
});
```

Preference order: the list table → any table that actually has rows → the first
table → none. The `tbody`/`rows` references stay lazy (Change 5), so the owner
survives a search swap.

**Verified** — headless Firefox, 14 checks: exactly 1 row selected on all three
previously-broken pages, `modal=0`, ArrowDown stays at 1, Escape clears, Alt+D
works, still works **after** a search swap, single-table pages unaffected.

---

### Change 7 — Five pre-existing defects fixed (plus one reported false positive)

Found while verifying Changes 1–6. Each is traced below to pre-existing code;
none was introduced by them. Items 7.1–7.5 are fixes; 7.6 is a defect report
that turned out to be wrong and is recorded as such rather than "fixed".

#### 7.1 — Quotation column sorting was completely non-functional

Three independent causes, all of which had to be fixed:

| # | Defect | Fix |
|---|---|---|
| a | `<tbody class="text-center">` lacked the `table-tbody` class that `listjs.js` requires — `invoiceTableContainer.querySelector(".table-tbody")` returned `null` and it returned early, so **List.js never initialized at all**. | Added `table-tbody`. |
| b | `data-sort` sat on the `<th>` carrying **DB column names** (`quotation_number`, `customer_id`). List.js reads `data-sort` from the element it binds to — the `<button class="table-sort">` — which had none, so `valueName` came back `null` and every row compared `undefined` against `undefined`. | Moved `data-sort` onto each button with a real valueName. |
| c | No `<td>` carried a `sort-*` class, so there was nothing to index even had List.js started. | Added `sort-*` classes, plus `data-date` (ISO) on both date columns and `data-amount` (numeric) on the total so they sort as dates/numbers rather than as the string `"05 Sep 2026"`. |

`listjs.js` also indexed fewer valueNames than headers referenced:
`sort-customer`, `sort-warehouse`, `sort-tax` and `sort-ppn` were declared on
Sales headers but missing from `valueNames`, so those columns silently did
nothing. Added, along with `sort-pph` and the two new date valueNames.

#### 7.2 — List.js double-bind: sort toggling worked on only *some* pages

Clicking a header twice should flip asc → desc. On Product, PO and Supplier it
always landed on `desc` and the second click did nothing.

`sorting.js` calls `onPageLoad(cb)` **without an `id`**, and `onPageLoad` skips
its guard entirely when `id` is null — so `cb` runs at module execution *and*
again on `turbo:load`. `initListJs()` had no re-entry guard, so both calls
built a List, each binding its own click handler to the same buttons. One click
then ran `sort()` twice: handler 1 reads "no order" → sorts `asc`, handler 2
reads `asc` → sorts `desc`. Net result: always `desc`, never a toggle.

Because it depends on whether the module executes before or after `turbo:load`,
it was a **race** — which is exactly why Supplier was broken while Customer
worked on identical code.

**Fix.** `initListJs()` sets `data-listjs-bound` on `#invoiceTableContainer`
and returns early if already set; `serverSearch.js` deletes that flag in
`afterSwap()`, because a swap replaces the buttons (and their handlers) while
the container itself survives. Now deterministic regardless of the race.

`onPageLoad` is called by ~20 modules and **none** pass an `id`. The shared
utility was deliberately left alone: changing its semantics for every caller is
not worth it when the single non-idempotent function can be made idempotent.

#### 7.3 — Dead partial deleted

`layouts/partials/warehouse/index/search.blade.php` was included by nothing and
defined a second `#entriesSelect` (without the `data-onchange` the live one
has). Deleted. The live control is `.../warehouse/index/info-section.blade.php`.

#### 7.4 — Duplicate entries-per-page handlers (Product, Warehouse)

Each carried a JS `change` listener *and* the blade `data-onchange`, both
performing the identical navigation. The JS listeners were removed:
`data-onchange` is the convention on every other list and is evaluated by
`global-event-delegator.js` from a document-level listener, so it survives
table swaps. Verified that `?search=elco&entries=25` and
`?search=Gudang&entries=25` both retain the search term.

#### 7.5 — PHPUnit doc-comment metadata (3 warnings)

`tests/Feature/MultiTenancyTest.php` used `/** @test */`, deprecated by
PHPUnit 11. Converted to `#[Test]` attributes.

#### 7.6 — Reported "jQuery SRI mismatch" — not a bug (false positive)

Earlier browser verification logged a CDN failure against
`resources/views/admin/layouts/script.blade.php:2`. Re-checked by downloading
the asset and computing the digest:

```
computed : sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=
in blade : sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=
```

They match, `window.jQuery.fn.jquery === "3.7.1"` on a live page, and a full
page load produces **zero** console errors. Recorded here rather than "fixed"
because no change was warranted — the original observation was wrong.

---

### Change 8 — Test infrastructure (both suites now runnable)

**8.1 — Every PHP test was being killed at 1 second.**

`phpunit.xml` sets `timeoutForSmallTests="30"`, but that value only applies to
tests whose `size()` reports small/medium/large — and **no test in the repo
declares a size group**. `TestRunner` therefore fell through to
`defaultTimeLimit()`, whose default is **1**. Result: 1 failure + 2 risky
aborts (`POSControllerTest` died inside `Product::factory()->count(65)->create()`
— in fixture seeding, not the endpoint) and a run that crashed with `signal 11`
before printing any summary.

*Fix:* added `defaultTimeLimit="30"` to `phpunit.xml`.

**8.2 — `localStorage` was `undefined` in 9 vitest tests.**

jsdom exposes `localStorage` as a getter on the **Window prototype**, while
vitest copies only the window's *own* keys onto `globalThis` — and in this
environment `window` *is* `globalThis`, so it arrived `undefined`. Not an
opaque-origin problem: `window.location.href` was `http://localhost:3000/`, and
every JSDOM option combination yielded a working `localStorage` when
instantiated directly.

*Fix:* `tests/js/setup.js` installs a real `Storage` implementation when absent.

---

### Change 9 — Sort audit: 14 unregistered valueNames, and 4 pages that never bound List.js

A whole-app audit of sortable headers, done by diffing the `data-sort` each
header `<button>` declares against the `valueNames` array `listjs.js` builds.
List.js indexes **only** the listed valueNames: a header referencing an absent
one yields an inert column where the caret still toggles but the rows never
move, so a stale list and a working one look identical on screen.

#### 9.1 — Thirteen headers referenced valueNames that were never registered

`sort-closed`, `sort-credits`, `sort-dept`, `sort-email`, `sort-is-main`,
`sort-paid-date`, `sort-period`, `sort-permissions`, `sort-position`,
`sort-rate`, `sort-roles`, `sort-run-date`, `sort-type`.

Each has a matching `sort-*` class on its `<td>`, so all thirteen were fully
fixable by registering the name — Customer, Supplier, Warehouse, Users, Tax and
the three HR lists gain working sorting that previously did nothing.

Two details worth recording:

- The first pass used a `sort-[a-z]+` regex and found only 11; the three
  hyphenated names (`sort-is-main`, `sort-paid-date`, `sort-run-date`) were
  invisible to it. The audit was re-run with `sort-[a-z-]+`.
- `sort-image` (Customer, Supplier) is **deliberately left unregistered**. That
  cell contains only an `<img>`/`<i>`, so `innerText` is empty on every row —
  there is nothing to sort by. Registering it would add an inert caret, and
  removing the header's sort affordance is a UI change nobody asked for.
  41 distinct `data-sort` values exist app-wide; 40 are registered.

#### 9.2 — Money and period columns needed typed attributes, not text

`sort-credits` (Customer) renders `Rp 1.500.000` or `-`. Natural-sorting that
string ranks 1.5M **below** 999K, because it compares the digit run `1` against
`999`. The `<td>` gained a raw `data-amount` and is registered as
`{ name: "sort-credits", attr: "data-amount" }`.

`sort-period` was declared by two lists that render it differently: Attendance
emits `Y-m` (`2026-10`, which sorts correctly as plain text) while Payroll emits
`October 2026` (which alphabetises by month name). One shared valueName cannot
serve both, so Payroll's button and cell were renamed to `sort-runperiod` with
an ISO `data-date`, registered as its own entry. Attendance keeps `sort-period`.

#### 9.3 — Four pages never initialized List.js at all

The valueNames fix alone would have changed nothing on four pages. `initListJs()`
returns immediately when `#invoiceTableContainer` is missing, and **users,
HR/employee, HR/attendance and HR/payroll had none** — they carried
`<tbody class="table-tbody">` and four to seven `data-sort` buttons each, but
without the container List.js never started. Every sort header on those pages
was dead independently of which valueNames were registered.

*Fix:* wrapped each page's `.table-responsive` in
`<div id="invoiceTableContainer">`.

A rendered-HTML sweep across all 16 list pages confirmed exactly those four were
`container=0` **and** `buttons>0`, and that the count is now zero.

The container is an **opt-in switch**, which sets a hard limit on where it may be
added: `reports/recentts.blade.php` sorts server-side through
`<a href="?sort=...">` links that also carry `class="table-sort"`. It has no
container and no `table-tbody`, so List.js stays out and does not fight those
links. Adding the container there would have produced two competing sorters.
The wrapper was therefore added only where headers declare `data-sort`.

#### 9.4 — `sort-amount` was registered twice, once without its attribute

`valueNames` contained both `{ name: "sort-amount", attr: "data-amount" }` and
the bare string `"sort-amount"`. All three `sort-amount` cells — Sales, PO and
Quotations — already carry a numeric `data-amount`, so the bare entry could only
weaken the typed one. Removed.

Verified against data where the two paths **disagree**: ascending yields
`["Rp 45.000", "Rp 999.000", "Rp 1.500.000"]`, which is numeric order. The
string path would have produced
`["Rp 1.500.000", "Rp 45.000", "Rp 999.000"]`.

#### 9.5 — list.js now served from disk instead of cdnjs

`script.blade.php` loaded `list.min.js` from cdnjs, while
`public/tabler/dist/libs/list.js/dist/list.min.js` sat unreferenced. Both files
are byte-identical:

```
cdnjs 2.3.1 : 34456f18d306a85bece9a7462d98e6e6c2e072e5fdac64e0d70946b40d5feccf
local copy  : 34456f18d306a85bece9a7462d98e6e6c2e072e5fdac64e0d70946b40d5feccf
```

so the swap is behaviour-neutral: it removes an external dependency on a page
whose sorting now depends on the library loading at all, and it becomes
same-origin. Confirmed on a real load — no request reaches
`cdnjs.cloudflare.com/ajax/libs/list.js`, and `window.List` is a function.

#### Recorded, not fixed

- **Sales Returns and Purchase Returns render no sort buttons at all**
  (`table-sort` count 0 on both). A missing feature rather than a broken one,
  and outside the scope approved for this pass.
- Four valueNames are registered but referenced by no header — `sort-date`,
  `sort-expiry`, `sort-ppn`, `sort-total`. Harmless; left in place.

---

### Verification for this change

**Suites (both green, exit 0), re-run after Change 9:**

- `php artisan test` → **1,246 passed (3,974 assertions)**, 350.84 s, 116 test
  classes, 0 failures / 0 risky / 0 aborted / 0 deprecations.
- `npm run test:js -- --run` → **172 files / 209 tests passed**, exit 0.
- `npm run build` after every JS/Blade edit — clean; the shipped sorting chunk
  contains all 14 new valueNames and exactly one `sort-amount` entry.

**Search correctness (17 checks):** HTTP result counts matched direct DB
queries across 11 lists — product `elco`=56, sales `Paid`=36, po `PO-0000`=9,
categories `F`=7 (name **and** description), customer `a`=7, supplier `PT`=2,
units `a`=0, sales-returns `Completed`=8 and `Toko Maju Jaya`=2 (relation
`sale→customer`), por `Completed`=7 and `PT. Elektronika`=4 (relation
`purchase→supplier`), warehouses `Gudang`=3 / `Bandung`=1, quotations
`QTN-202610`=3.

**Structure (11/11):** the first `.table-responsive` inside
`#invoiceTableContainer` really is the list table, and the pagination footer
sits *outside* the swapped region.

**Real browser (headless Firefox):**

- 17/17 search checks — 1169 → 56 while typing, URL synced, rows swapped, bulk
  bar rebinds post-swap, sorting works post-swap and on page 2, pagination →
  `?search=elco&page=2` → *"Showing 11 to 20 of 56"* with **0 non-matching
  rows**, search survives reload, `?search=elco&entries=25` no longer drops the
  query.
- 14/14 Alt+E / Alt+D checks (Change 6).
- 16/16 post-fix checks (Change 7): all 6 quotation columns sort **and**
  toggle, sales customer/warehouse columns now sort, entries preserves
  `?search` on Product and Warehouse with the JS handlers removed, control page
  unaffected, **zero console errors**.

**Change 9 (sort audit) — headless Firefox + HTTP/DB:**

- **Audit:** 41 distinct `data-sort` values app-wide, 40 registered,
  `sort-image` the only intentional exception.
- **Dead-sort sweep over all 16 list pages:** 0 pages with sort buttons but no
  container — was 4 (users, HR employee, HR attendance, HR payroll), each with
  3–7 dead headers.
- **25/25 header checks across 7 pages:** every newly-registered column
  reorders where its values differ (`sort-email` distinct=7, `sort-dept`=7,
  `sort-position`=8, `sort-type`=3, `sort-is-main`=2) and engages correctly on
  single-row or all-ties tables (`sort-credits` — all 7 customers hold `0.00`,
  so no reorder is possible by construction).
- **32/32 regression checks:** attr-driven sorts (quotation dates, valid-until,
  totals, status, sales customer/warehouse, PO supplier/order-date), container
  present on all 14 sortable pages, `?search` survives the entries selector on
  Product **and** Sales, sorting a filtered list keeps the filter, zero
  console/page errors.
- **Money correctness:** ascending yields
  `["Rp 45.000","Rp 999.000","Rp 1.500.000"]` — numeric. The string path would
  yield `["Rp 1.500.000","Rp 45.000","Rp 999.000"]`.
- **Local `list.js`:** no request to `cdnjs.cloudflare.com/ajax/libs/list.js`;
  `window.List` is a function.
- **Search re-verified after the blade edits:** 14/14 HTTP counts still match
  the DB-validated baselines.

**Documentation (targeted pass over the three docs this work touched):**

- `README.md` stated `1099 tests / 3317 assertions` in three current-state
  places plus a `42 + 53 + 173 = 268 test files` breakdown. All of it became
  wrong when Change 8 stopped the suite dying at one second. Corrected to
  **1,455 tests / 3,974 assertions** across **288 test files** (50 Feature +
  66 Unit PHP + 172 JS), with the two suite subheadings set to PHPUnit's own
  per-suite counts — `phpunit --list-tests` reports 1,246 PHP tests as
  Feature 619 / Unit 627.
- Deliberately **left alone**: README's release-history bullet *"Test Suite
  Hardening — … 1099 passing, 0 failures"* and `ARCHITECTURE_ASSESSMENT`'s
  *"Full suite: 1186 passing (3715 assertions)"* rev notes. These are dated
  milestones about past releases; rewriting them would falsify the record
  rather than correct a claim.
- `ARCHITECTURE_ASSESSMENT` makes no claim about list search or sorting, and
  `STRUCTURE.md` documents neither `resources/js` nor `app/Services` while no
  test file was added — so neither needed changing.

**Two console lines correctly attributed to the harness, not the app:**

- `downloadable font ... status=2152398850` (`NS_BINDING_ABORTED`, rsms.me /
  tabler-icons) only appears when a navigation interrupts a font fetch.
- `Failed to load forecast` only appears when navigating away interrupts the
  dashboard's deferred AJAX fetch. Left alone, the dashboard resolves it:
  `deferred:false`, 9 labels, chart present, **0 errors**.

**Correction to an earlier claim:** `listjs.js` is no longer untouched —
Changes 7.1 and 7.2 modify it. The jQuery SRI reported as broken is **not**
(see 7.6).

- All changes intentionally left **uncommitted** — version control is handled
  manually.

### Environment notes carried along

- DB `invent_mag_be`, `root`, no password, `127.0.0.1:3306`.
- Tenant 3 = `enterprise.127.0.0.1.nip.io`; superadmin lives on
  `super.127.0.0.1.nip.io` and is never touched.
- `CACHE_STORE=file` under Apache, `array` for CLI tests. `QUEUE_CONNECTION=database`.
- Reseeding nulls `email_verified_at`; run
  `UPDATE users SET email_verified_at = NOW();` after any `migrate:fresh --seed`.
- opcache is **enabled for production** in the `Dockerfile` (Change 15). Locally
  it is still off: `/opt/lampp/etc/php.ini` is root-owned and PHP is mod_php, so
  there is no root-free injection point. `sudo bash /tmp/opencode/enable-opcache.sh`
  then `/opt/lampp/lampp restart` turns it on — see Change 15.
