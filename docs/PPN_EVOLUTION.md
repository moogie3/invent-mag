# PPN & e-Faktur Feature Evolution

> **Last updated:** 2026-08-05
> **Status:** PPh 23 + PPh Final 0.5% Implemented — 1167 PHP tests / 3586 assertions + 169 JS Vitest files (2026-08-05). PPh 21/25 scoped to UMKM-appropriate form (2026-07-31).

---

## Table of Contents

1. [What is PPN & e-Faktur?](#what-is-ppn--e-faktur)
2. [Why We Built It](#why-we-built-it)
3. [Original Implementation (v1)](#original-implementation-v1)
4. [Problems We Found](#problems-we-found)
5. [The Universal Tax Refactor (v2)](#the-universal-tax-refactor-v2)
6. [Phase 1B: Tax Synchronization & Navigation Refactor (2026-07-25)](#phase-1b-tax-synchronization--navigation-refactor-2026-07-25)
7. [Phase 1E: VAT Split at Sale Creation + PPh Final 0.5% (2026-08-05)](#phase-1e-vat-split-at-sale-creation--pph-final-05-2026-08-05)
8. [Architecture Decisions](#architecture-decisions)
9. [Data Model](#data-model)
10. [Feature Layering](#feature-layering)
11. [Journal Entry Accounting](#journal-entry-accounting)
12. [Faktur Pajak Lifecycle](#faktur-pajak-lifecycle)
13. [What Was Changed (Complete File List)](#what-was-changed-complete-file-list)
14. [Key Files Reference](#key-files-reference)
15. [Phase 2: PJAP API (Planned)](#phase-2-pjap-api-planned)
16. [PPh (Withholding Tax) Implementation](#pph-withholding-tax-implementation)
17. [Remaining Backlog](#remaining-backlog)

---

## What is PPN & e-Faktur?

**PPN (Pajak Pertambahan Nilai)** is Indonesia's Value Added Tax (VAT). Every company registered as a **PKP (Pengusaha Kena Pajak)** — a Taxable Entrepreneur — is legally required to:

1. Charge PPN on their sales (output VAT / PPN Keluaran)
2. Report PPN to the DJP (Direktorat Jenderal Pajak — Indonesia's tax authority)
3. Issue a **Faktur Pajak** (tax invoice) for each taxable sale

**e-Faktur** is DJP's electronic system for submitting Faktur Pajak. It requires:

- A specific CSV format for bulk upload to the DJP desktop application
- **NSFP (Nomor Seri Faktur Pajak)** — unique serial numbers allocated by DJP for each Faktur Pajak
- Company NPWP, PKP status, and seller/buyer data snapshot at issuance time

Indonesia's current PPN rate is **11%** (effective April 1, 2022).

---

## Why We Built It

Invent-MAG is a multi-tenant SaaS ERP targeting Indonesian SMEs. PPN compliance is **not optional** for our users — it's a legal requirement. Without e-Faktur support:

- PKP-registered tenants would need to use separate desktop software (DJP e-Faktur) to generate tax invoices
- No link between sales orders and their corresponding Faktur Pajak
- Manual double-entry of data between systems
- Risk of data mismatch between the ERP and DJP submissions

We built e-Faktur support to provide **end-to-end tax compliance** within the ERP itself.

---

## Original Implementation (v1)

### Initial Design

The original PPN implementation used a **separate toggle** independent of the universal tax system:

```
Sales Form:
  ├── Tax dropdown (universal tax system)
  └── PPN toggle (separate on/off switch)
       ├── ppn_enabled (boolean on Sales)
       ├── ppn_rate (snapshot from tenant settings)
       └── ppn_amount (recalculated client-side)
```

The PPN toggle appeared on sales create/edit forms when:

- Tenant had `hasFeature('ppn')` (Professional+ plan)
- Tenant had `pkp_status = true`

When toggled on, JavaScript calculated PPN separately from the universal tax system. The PPN amount was sent as a form field (`ppn_amount`) and stored directly on the Sales record.

### What Worked

- Basic PPN calculation and display
- Faktur Pajak generation from sales with PPN enabled
- NSFP pool management (import/allocate/cancel)
- CSV export for DJP desktop application
- PPN Keluaran journal entries (Dr. Accounts Receivable / Cr. PPN Keluaran liability)
- PPN reversal on sales returns
- Faktur Pajak cancellation (returns NSFP to pool)
- PDF generation for individual Faktur Pajak

---

## Problems We Found

After the initial implementation was live, we identified **5 critical issues** that created data integrity risks and accounting inaccuracies:

### Issue 1: PPN Calculated on Post-General-Tax Amount (Double Counting)

The PPN toggle was independent of the tax system. If a sale had:

- Subtotal: Rp 1,000,000
- General tax (10% exclusive): Rp 100,000
- Grand total: Rp 1,100,000

Then PPN (11%) was calculated on `grandTotal` = Rp 1,100,000 × 11% = **Rp 121,000**

But PPN should only be on the **taxable base** (DPP), not on a general tax that was already applied. This is double-counting — the general tax inflates the PPN amount.

### Issue 2: Faktur Pajak PPN Diverges from Stored ppn_amount

The EFakturService recalculated PPN from `ppn_rate × DPP`, but the Sales record stored a separately calculated `ppn_amount` from the form. These two values could diverge due to:

- Rounding differences
- Client-side vs server-side calculation
- Manual overrides

### Issue 3: Line-Item DPP Inconsistency

The original FakturPajakItem DPP was simply `item->total` (the line total), without accounting for order-level discounts. DJP requires DPP to reflect the actual taxable base after all discounts.

### Issue 4: grand_total Accessor Ignores PPN

The Sales model's `grandTotal` accessor returned `$total` directly. Since PPN was calculated **on top of** the total, the accessor didn't reflect what the customer actually pays (total + PPN). This created confusion in reports and views.

### Issue 5: No Mutual Exclusion Between PPN and General Tax

A user could theoretically:

1. Select a general tax (e.g., 10% service tax)
2. Also toggle PPN on (11% VAT)
3. End up with **two different taxes** on the same invoice

Indonesian tax law does not allow PPN and general taxes to coexist on the same Faktur Pajak. PPN replaces any other tax when it's a BKP (Barang Kena Pajak / Taxable Good or Service).

---

## The Universal Tax Refactor (v2)

### Core Insight

PPN is fundamentally a tax. Instead of maintaining it as a separate system, we integrated it into the **universal tax system** that already existed in Invent-MAG. The key change:

> **PPN is no longer a toggle. It's a property of the selected tax.**

```
Sales Form (v2):
  └── Tax dropdown (single source of truth)
       ├── "PPN 11%" (tax.is_ppn = true)  → enables PPN compliance features
       ├── "PPN 12%" (tax.is_ppn = true)  → future rate change ready
       ├── "General Tax 10%" (is_ppn = false) → no PPN features
       └── "No Tax" (rate = 0) → no PPN features
```

### How It Works

1. **Admin creates taxes** in Tax Settings (e.g., "PPN 11%", "Service Tax 10%")
2. **PPN taxes are flagged** with `is_ppn = true` on the Tax model
3. **When creating a sale**, the user selects a tax from the dropdown
4. **If the selected tax has `is_ppn = true`**, the system automatically:
    - Sets `ppn_enabled = true` on the Sale (denormalized snapshot)
    - Stores `ppn_rate` and `ppn_amount` as snapshot fields
    - Enables Faktur Pajak generation on the sale view
    - Shows PPN status in the sales index
5. **If the selected tax has `is_ppn = false`**, PPN features are hidden
6. **PPN and general tax are mutually exclusive** — selecting a PPN tax disables the general tax rate (and vice versa), because `tax_type = 'none'` for PPN taxes

### The 5 Issues — Fixed

| Issue                   | Fix                                                                                                               |
| ----------------------- | ----------------------------------------------------------------------------------------------------------------- |
| Double-counting         | DPP = `sale.total - sale.total_tax` (pre-tax amount). PPN amount = `sale.total_tax` directly. No recalculation.   |
| FP/Sale divergence      | Faktur Pajak uses `$sale->total_tax` as PPN — same value stored on the Sale. No independent recalculation.        |
| Line-item DPP           | Order-level discount is proportionalized across line items before computing item DPP.                             |
| grand_total ignores PPN | PPN is now part of `total_tax` when the selected tax has `is_ppn = true`. The `total` field already includes tax. |
| Mutual exclusion        | Single dropdown — user picks one tax. PPN tax (`type = 'none'`) or general tax, never both.                       |

---

## Phase 1B: Tax Synchronization & Navigation Refactor (2026-07-25)

Phase 1B focused on synchronizing tax usage across all transaction types (POS, Quotation, Purchase Orders, Sales Returns) and cleaning up navigation to respect jurisdiction boundaries.

### Navigation Jurisdiction Filtering

The e-Faktur menu item in the sidebar is now hidden for non-Indonesian tenants. This is implemented via `NavigationServiceProvider::tenantMatchesJurisdiction()`, which checks the `'jurisdiction' => 'ID'` property on navigation menu items against the current tenant's `tax_jurisdiction`. The filtering is **cosmetic only** (sidebar visibility) — routes remain accessible via direct URL (see Decision 12).

### Tax Region in General Settings

`tax_jurisdiction` can now be configured from the main Settings page (`admin/settings`). Previously, users had to navigate directly to the e-Faktur settings page to change their tax jurisdiction. The Settings page now includes a **Tax Region** card where any Professional+ tenant can set their jurisdiction, making it a first-class configuration alongside company name, timezone, and currency.

### POS Tax Model Integration

The POS (Point of Sale) create form previously used a **manual tax rate input** (user types a percentage). This has been replaced with a **Tax model dropdown** (`tax_id`), consistent with the Sales form approach. The selected tax's `rate` and `type` are used for calculation, and if the tax has `is_ppn = true`, PPN compliance features activate automatically.

### Quotation Tax Model Integration

The Quotation create/edit form previously used **manual `tax_type` / `tax_rate` fields**. These have been replaced with a **Tax model dropdown** (`tax_id`), bringing Quotations in line with the universal tax system. The selected Tax model drives all calculations and PPN feature activation.

### PO Create: No Tax

The Purchase Order create page **no longer shows tax fields**. This follows the Accurate Online / Indonesian standard approach: PPN (input VAT / PPN Masukan) is only tracked at the **invoice stage**, not at the purchase order stage. Tax is applied when a Purchase Invoice is created from the PO.

### Index Tax Columns

The following index pages now display a **Tax column** showing the applied tax name/type:

- **Purchase Orders** — shows which tax (if any) is associated with the PO
- **Sales Returns** — shows the tax from the original sale being returned
- **Quotations** — shows the tax selected on the quotation

### Tutorial Message Cleanup

Removed redundant `"only visible when Indonesia"` qualifiers from tutorial/onboarding messages throughout the UI. The system now handles jurisdiction gating internally via `NavigationServiceProvider` and `isIndonesian()` checks, so explicit caveats in user-facing text are unnecessary and create noise.

### Test Updates

The following test files were updated to use `tax_id` payloads instead of manual rate/type inputs:

- **`POSControllerTest`** — updated store/update test assertions for `tax_id` field
- **`PosServiceTest`** — updated service-level tests for tax model references
- **`SalesReturnServiceTest`** — updated return creation tests for `tax_id` payloads

---

## Phase 1C: Tax Jurisdiction Auto-Seed & Tutorial Clarity (2026-08-02)

Phase 1C removes the last confusing onboarding step for Indonesian users: when a tenant switches their Tax Jurisdiction to Indonesia, the system now **auto-creates the standard tax records** instead of making the user configure them manually.

### Auto-Seed PPN/PPh Defaults

`TaxService::ensureIndonesianDefaults(Tenant $tenant)` creates the standard Indonesian tax records automatically whenever a tenant switches to `tax_jurisdiction = 'ID'` (from either the **Settings page** Tax Region card or the **e-Faktur settings** page):

- **PPN 11%** — `rate = 11`, `type = exclusive`, `is_ppn = true`. Becomes the default tax **only if no default already exists** (an existing default is never overridden).
- **PPH 23** — `rate = 2`, `type = exclusive`, `is_pph = true`. Never default.

The method is **idempotent**: it checks for an existing `is_ppn = true` / `is_pph = true` record per tenant and skips creation if one already exists, so re-toggling jurisdiction (or switching away and back) never duplicates records. Created record names are returned and logged.

This matters because the sales/POS/quotation/Purchase-service layers only apply PPN/PPh when a Tax record carries the `is_ppn`/`is_pph` flag (see Decision 1). Without auto-seed, a tenant who switched to Indonesia and then opened a Sales form would see **no PPN-compliant tax available** and be forced to create it manually — an extra step the auto-seed eliminates.

### Jurisdiction vs Tax Configuration Tutorial Clarity

Two new tutorial steps (EN/ID) explain the difference that users keep confusing:

- **`tax_jurisdiction_vs_config`** (System Settings page) — the Tax Jurisdiction switch (`None / Indonesia`) selects the country's *compliance framework* (turns on PPN, e-Faktur, NSFP, NPWP features); it does **not** by itself add a rate to transactions. Actual rates live on the Tax page. Mentions that switching to Indonesia auto-creates PPN 11% + PPh 23 2%.
- **`tax_config_vs_jurisdiction`** (Tax page) — the reverse perspective: Tax records (name/rate/type) only take effect once assigned to a product or order, and PPN additionally requires the jurisdiction to be Indonesia.

The stale tax tutorial steps were also refreshed to match current behavior:

- `define_tax_rates` — removed the false "only one tax rate can be configured at a time"; now documents multiple records, `is_default`, and the PPN/PPh flags.
- `sales_application` — removed the false "tax is not applied to purchase orders"; now documents sales, invoices, purchases, and POS.
- `tax_configuration_desc` — broadened from "sales orders and invoices" to sales and purchases.

### Bug Fix: `canDisableIndonesianJurisdiction()`

The jurisdiction-disable safeguard queried `Sales::where('faktur_pajak_id', null)` — but **the `sales` table has no `faktur_pajak_id` column** (the relationship is `FakturPajak.sales_id`, i.e. `Sales::hasOne(FakturPajak::class, 'sales_id')`). Switching away from Indonesia therefore threw a SQL error → 500 → the Settings page JS fell back to a generic "An error occurred" alert.

Fixed by replacing the invalid query with `whereDoesntHave('fakturPajak')`, which counts unissued PPN sales that have no Faktur Pajak yet (see Decision 17). The Settings page JS was also hardened to check `response.ok`, reload on 419/401 (expired session/CSRF), and surface the real `data.message`.

### Related Fix: Fortify Error Bag in ValidationException Renderer

The Turbo-compatible custom `ValidationException` renderer in `bootstrap/app.php` flashed errors with `->withErrors($e->errors())` — the **default** bag — dropping Fortify's named `updatePassword` bag and breaking the password-update test. It now passes the bag through: `->withErrors($e->errors(), $e->errorBag)`. No views consume named bags, so this is safe.

### Bug Fix: e-Faktur Settings Page "Content missing" (500 on `pkp_date`)

`resources/views/admin/efaktur/settings.blade.php` rendered the PKP date input with `$tenant->pkp_date?->format('Y-m-d')`. The nullsafe operator only guards against `null` — but the `Tenant` model had **no date cast** for `pkp_date`, so for any tenant with a stored PKP date the raw `'YYYY-MM-DD'` string was returned and `->format()` threw `Call to a member function format() on string` → HTTP 500 → Turbo surfaced "Content missing" on `GET /admin/efaktur/settings`.

Fixed by adding `'pkp_date' => 'date'` to `Tenant::$casts` (and to `Customer::$casts`, which had the same latent bug at `customer/index.blade.php` and `CustomerService` — they would crash identically once any customer has a PKP date). The stored `date` column values (`NULL` or `'YYYY-MM-DD'`) parse cleanly; no data migration needed. Verified live: tenant 3's `pkp_date = '2026-08-01'` now renders as a Carbon date.

### Tests

- `tests/Unit/Services/TaxServiceEnsureIndonesianDefaultsTest.php` — creates both records, idempotency, skips existing PPN/PPh, preserves existing default.
- `tests/Feature/Admin/SettingsTaxJurisdictionTest.php` — switch-on creates records (web + AJAX), idempotent via endpoint, switch-away leaves records intact.
- `tests/Feature/Admin/EfakturSettingsTest.php` — settings page renders with a PKP date set / null, save-then-render, and `pkp_date` date-cast on both Tenant and Customer.
- Full suite: **1151 passing (3480 assertions)**.

---

## Phase 1D: PPN Masukan (Input VAT on Purchases) — 2026-08-03

### Problem

The system only tracked **PPN Keluaran** (output VAT) on sales. PKP businesses also need **PPN Masukan** (input VAT) — the VAT they paid on purchases — to offset against their monthly PPN liability when filing SPT Masa PPN. Without it, the SPT summary overstates the amount payable to DJP.

### Design

PPN Masukan **does not reduce the payable to the supplier** (unlike PPh which withholds part of the payment). Instead it is a **debit to asset account `1160 - Input VAT`**, while the purchase itself is recorded at the net amount:

- **Before:** `Dr Inventory (gross incl. tax) / Cr AP / Cr PPh Payable`
- **With PPN Masukan:** `Dr Inventory (net after-discount) + Dr Input VAT (1160) / Cr AP / Cr PPh Payable`

Mirrors the existing PPh pattern on purchases: snapshot fields (`ppn_enabled`, `ppn_rate`, `ppn_amount`) denormalized onto `po`, auto-derived from the selected `Tax.is_ppn` flag, only applied when the tenant `isIndonesian()`.

### What Changed

- **Migration** `2026_08_03_000001_add_ppn_fields_to_po.php` — `ppn_enabled` (bool), `ppn_rate` (nullable), `ppn_amount` (default 0) on `po`.
- **Backfill** `2026_08_03_000002_backfill_ppn_masukan_account_setting.php` — adds `ppn_masukan_account_id` (code `1160-{tenantId}`) to every tenant's `accounting_settings` (idempotent).
- **`app/Models/Purchase.php`** — `ppn_*` fillable + casts (`ppn_enabled` boolean, `ppn_rate`/`ppn_amount` float), `isPpn()`. Also fixed pre-existing cast gaps: `pph_enabled` → boolean, `pph_rate` → float.
- **`app/Services/PurchaseService.php`** — `createPurchase()`/`updatePurchase()` detect PPN Masukan and persist the snapshot; `createPurchase()` journal now splits inventory (`Dr Inventory net`) + `Dr Input VAT` when a `ppn_masukan_account_id` is configured. Graceful fallback: if the Input VAT account is not configured, inventory is still debited at gross (old behavior, no crash).
- **`database/seeders/AccountSeeder.php`** + **`app/Actions/Fortify/CreateNewUser.php`** — wire `ppn_masukan_account_id` into `accounting_settings`.
- **`app/Services/ChartOfAccountsService.php`** — `ppn_masukan_account_id => 'nullable|exists:accounts,id'`.
- **`accounting-setting.blade.php`** — new "Input VAT (PPN Masukan) Account" select (from `asset` accounts) next to PPN Keluaran.
- **`app/Services/EFakturService.php`** — `getSptMasaSummary()` now sums `po.ppn_amount` where `ppn_enabled = true` for the period and returns `total_ppn_masukan`, `net_ppn_payable` (Keluaran − Masukan, floored at 0) and `ppn_credit` (Masukan − Keluaran when Masukan exceeds).
- **`efaktur/index.blade.php`** — new summary cards: Total PPN Masukan + Net PPN Payable / PPN Lebih Bayar.
- **PO partials** — PPN Masukan display row (info, blue, positive) + hidden inputs on create/edit/view; create/edit JS auto-shows/hides it from the selected tax's `data-is-ppn` flag.
- **Lang/tutorial** — `ppn_masukan*` keys (EN + ID) and 2 new tutorial steps (`ppn_masukan_po`, `ppn_masukan_po_view`).

### Journal Entry Example

Purchase 200 + PPN 11% (exclusive) = 22:

| Debit | Credit |
| --- | --- |
| Inventory (1140) — 200 | |
| Input VAT / PPN Masukan (1160) — 22 | |
| | Accounts Payable (2110) — 222 |

### Tests

- `tests/Unit/Services/PurchaseServicePpnMasukanTest.php` — PPN tax records `ppn_*` fields; journal splits Inventory/Input VAT (3 legs); non-PPN tax capitalizes tax into inventory (2 legs); missing Input VAT account falls back to gross (no crash).
- `tests/Unit/Services/EFakturServiceSptMasukanTest.php` — SPT summary includes `total_ppn_masukan` from purchases (period + `ppn_enabled` filtered, cancelled fakturs excluded); net payable and credit (Lebih Bayar) scenarios; empty period returns zeros.
- `tests/Feature/Admin/PurchasePpnMasukanViewTest.php` — create/edit pages render the PPN Masukan block (`ppnMasukanRow`, hidden `ppn_*` inputs, `data-is-ppn` on tax options); show page renders the PPN Masukan row only for PPN purchases (asserted via `#ppn-masukan-row`).

---

## Phase 1E: VAT Split at Sale Creation + PPh Final 0.5% (2026-08-05)

### Problem

An accounting audit found two correctness issues:

1. **PPN Keluaran was recognized at Faktur Pajak issuance, not at sale creation.** `SalesService::createSale()` posted `Dr AR / Cr Revenue` (gross), and later `EFakturService::generateFakturPajak()` posted a second `Dr AR / Cr PPN Keluaran`. The AR leg was therefore **double-posted** for PPN sales (phantom AR until the payment cleared it).
2. **Revenue was booked gross of VAT.** For a PPN sale the Revenue credit included the PPN amount, so P&L overstated revenue.

### Fix 1: VAT split at sale creation (Keluaran)

PPN recognition moved from Faktur Pajak issuance to **sale creation**. When the tenant `isIndonesian()`, the selected tax has `is_ppn = true`, `taxAmount > 0`, **and** `ppn_keluaran_account_id` is configured, `createSale()` posts the **5-leg VAT-split journal**:

```
Dr. Accounts Receivable     grand_total          (customer owes the VAT-inclusive total)
Cr. Sales Revenue           grand_total − ppn    (net of VAT)
Cr. PPN Keluaran            ppn                  (liability to DJP)
Dr. COGS / Cr. Inventory    (as before)
```

- **Fallback:** if `ppn_keluaran_account_id` is missing, the previous 4-leg gross-revenue journal is posted instead — no crash.
- The **same split was applied to POS** (`PosService`) for Indonesian PPN sales.
- `EFakturService::generateFakturPajak()` no longer posts a journal — Faktur Pajak issuance is now **compliance-only** (NSFP, snapshot, CSV/PDF). `postPpnKeluaranJournalEntry()` was deleted.
- `SalesReturnService` PPN reversal no longer requires a Faktur Pajak: it reverses proportionally whenever the original sale `isPpn() && ppn_amount > 0`. `cancelFakturPajak()` still reverses any *legacy* FakturPajak-sourced PPN journals (backward compatibility only).

### Fix 2: Seed PPh 22 → PPh Final 0.5%

`PPH 22` (1.5% goods-withholding) is rarely the income tax an SME books. It was replaced by **`PPh Final 0.5%`** (`rate 0.5`, `is_pph`, desc "Pajak Penghasilan Final 0,5% berdasarkan PP 23/2018 (UMKM, omzet ≤ Rp4,8 miliar)"):

- `TaxService::ensureIndonesianDefaults()` and `TaxSeeder` now create `PPh Final 0.5%` instead of `PPH 22`.
- **Migration `2026_08_05_000002_backfill_pph_final_tax_record.php`** converts existing `PPH 22` (1.5, `is_pph`) rows **in place** (renames + rate change) so `pph_tax_id` references stay valid and historical `pph_rate`/`pph_amount` snapshots are untouched. If no `PPH 22` exists, it inserts `PPh Final 0.5%` instead. Idempotent.

Full tax-law rationale: `docs/TAXATION_SYSTEM.md`.

### Tests

- `tests/Unit/Services/SalesServiceTest.php` — **+2:** VAT split (5-transaction journal: Dr AR 222 / Cr Revenue 200 / Cr PPN Keluaran 22) and graceful fallback when the PPN Keluaran account is missing (4-leg gross).
- `tests/Unit/Services/PosServiceTest.php` — **+1:** POS PPN sale splits Dr AR 222 / Cr Revenue 200 / Cr PPN Keluaran 22.
- `tests/Unit/Services/TaxServiceEnsureIndonesianDefaultsTest.php` + `tests/Feature/Admin/SettingsTaxJurisdictionTest.php` — reworked for `PPh Final 0.5%` (still exactly 2 `is_pph` records).
- `tests/Unit/Services/SalesReturnServiceTest.php` + `tests/Unit/Services/EFakturServiceSptMasukanTest.php` — pass unchanged (guard removal did not regress them).

---

## Architecture Decisions

These are the key architectural decisions made during the refactor. **Do not violate these without understanding the full context.**

### 1. Universal Tax System Is the Source of Truth

`Tax.is_ppn` determines whether a tax is PPN. `Tax.is_pph` determines whether it is PPh (withholding tax). There are no separate `ppn_enabled` or `pph_enabled` form fields. The Sales form has one tax dropdown.

### 2. Denormalized Snapshots on Sales

`ppn_enabled`, `ppn_rate`, `ppn_amount` on Sales are **denormalized snapshots** — derived from the selected tax at creation time, just like `tax_rate` and `total_tax`. They exist for:

- Historical accuracy (if the Tax record is later edited/deleted)
- Performance (no need to join Tax table for every PPN check)
- Backward compatibility with existing FakturPajak records

**Critical:** `Sales::isPpn()` reads from the snapshot only — never from the live `Tax.is_ppn` relationship. This ensures that toggling a Tax record's `is_ppn` flag or switching `tax_jurisdiction` never retroactively changes past sales.

### 3. `tax_jurisdiction` Is the Master Switch

| Value  | Effect                                                  |
| ------ | ------------------------------------------------------- |
| `null` | Universal mode — no Indonesian-specific features shown  |
| `'ID'` | Indonesian mode — e-Faktur, NPWP, PPN features activate |

This replaces `hasFeature('ppn')` as the **primary UI gate**. `hasFeature('ppn')` remains as a **billing gate** (plan tier check).

**Auto-seed on switch:** Because the master switch alone does not create any tax rate, switching to `'ID'` now also calls `TaxService::ensureIndonesianDefaults()` (see Phase 1C) to auto-create PPN 11% and PPh 23 2% records — so the toggle and the available tax rates stay consistent for the user.

### 4. Layering: jurisdiction → PKP → plan → tax

```
tax_jurisdiction = 'ID'    ← Master switch (Tenant model)
    ↓
pkp_status = true          ← Company registration status (Tenant model)
    ↓
hasFeature('ppn')          ← Plan tier (Professional+)
    ↓
tax.is_ppn = true          ← Per-tax flag (Tax model)
    ↓
sale.isPpn()               ← Derived helper on Sale model
```

All four layers must be true for PPN features to fully function. But **UI visibility** is controlled by `isIndonesian() && hasFeature('ppn')`, while **Faktur Pajak generation** requires `pkp_status = true` (checked in EFakturService).

### 5. PPN and General Tax Are Mutually Exclusive

A single invoice can only have one tax. If that tax has `is_ppn = true`, PPN compliance features activate. If not, they don't. There is no scenario where both PPN and a general tax appear on the same invoice. PPh is **independent** — it uses its own `is_pph` flag and its own accounts, and does not conflict with the PPN/general tax exclusivity rule.

### 6. DPP = Subtotal − Order Discount

**DPP (Dasar Pengenaan Pajak)** is the taxable base:

```
DPP = sale.total - sale.total_tax
```

This works for both exclusive and inclusive tax types because `total_tax` is always the extracted tax amount. The PPN charged on the Faktur Pajak equals `total_tax` — no recalculation needed.

### 7. Journal Entries Are Permanent

The toggle controls **future behavior**, not past records. Reversing journal entries happens only when:

- A sale is deleted (`deleteSale()`)
- A sales return is created (`SalesReturnService`)
- A Faktur Pajak is cancelled (`cancelFakturPajak()`)

### 8. `isPpn()` on Sales Model

```php
public function isPpn(): bool
{
    if ($this->relationLoaded('tax') && $this->tax) {
        return (bool) $this->tax->is_ppn;
    }
    // Fallback to snapshot
    return (bool) $this->ppn_enabled;
}
```

Always use `$sale->isPpn()` instead of `$sale->ppn_enabled` directly. This ensures the latest tax data is used when available, with snapshot fallback for historical records.

`$sale->isPph()` follows the same pattern — reads from the live `Tax.is_pph` with `pph_enabled` snapshot fallback.

### 9. Safeguards on Tax Jurisdiction Toggle

When a user tries to switch from `tax_jurisdiction = 'ID'` to `null`:

- **BLOCK** if draft Faktur Pajak exist (must cancel/submit first)
- **BLOCK** if sales with PPN enabled have no Faktur Pajak (must generate or clear PPN first)

### 12. Navigation Filtering Is Cosmetic, Not Route Protection

The `NavigationServiceProvider` jurisdiction filtering controls **sidebar menu visibility only**. It does not protect routes. An e-Faktur URL (e.g., `/admin/efaktur`) remains accessible via direct URL even for non-Indonesian tenants. This is intentional — it avoids breaking deep links, bookmarks, and API integrations while still keeping the sidebar clean for the target audience. Route-level protection is a separate concern handled by middleware and service-level checks (see Decision 4 layering).

### 13. Tax Region Is Configurable from General Settings

`tax_jurisdiction` is exposed on the main **Settings** page (`admin/settings`) as a first-class tenant configuration. This means any Professional+ tenant can set their tax jurisdiction without navigating to e-Faktur settings directly. The Settings page includes a Tax Region card with the jurisdiction dropdown. This was a UX decision — tax jurisdiction affects the entire application (which menus appear, which tax features activate), so it belongs alongside other global tenant settings, not buried in a feature-specific page.

### 14. Purchase Orders Do Not Show Tax on Create

The Purchase Order create page deliberately **omits tax fields**. This follows the Accurate Online approach and Indonesian accounting standards: PPN Masukan (input VAT) is only recognized at the **invoice stage** (Purchase Invoice), not at the purchase order stage. Showing tax on POs would misrepresent the tax treatment — a PO is a commitment to purchase, not a taxable transaction. Tax liability is triggered when the vendor issues an invoice.

### 15. `isPpn()` Always Reads the Snapshot, Never the Live Tax Relationship

`Sales::isPpn()` reads from the stored `ppn_enabled` column only — never from the live `Tax.is_ppn` relationship. This is critical: if a Tax record's `is_ppn` flag is later toggled, it must NOT retroactively affect existing sales. The snapshot is the source of truth for historical records.

### 16. PPN Is Only Applied When Tenant Is Indonesian

`SalesService::createSale()` and `updateSale()` check `app('currentTenant')->isIndonesian()` before applying PPN fields. Even if a tax with `is_ppn = true` is selected, PPN fields are only written when the tenant's jurisdiction is Indonesian. This prevents accidental PPN on non-Indonesian tenants via API or direct DB manipulation.

### 17. Tax Jurisdiction Safeguards Are Consolidated

The ID→null safeguard (blocking the switch when draft Faktur Pajak or unissued PPN sales exist) is implemented once in `Tenant::canDisableIndonesianJurisdiction()` and called from both `SettingsController::updateTaxJurisdiction()` and `EFakturController::saveSettings()`. This prevents drift between the two entry points.

---

## Data Model

### Tables Involved

| Table                 | Key Fields                                                                                              | Purpose                                                             |
| --------------------- | ------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------- |
| `tenants`             | `tax_jurisdiction`, `pkp_status`, `npwp`, `pkp_date`, `ppn_rate`                                        | Tenant-level Indonesian tax config                                  |
| `taxes`               | `is_ppn`, `is_pph`, `rate`, `type`, `name`                                                              | Tax definitions. `is_ppn` flags PPN taxes, `is_pph` flags PPh taxes |
| `sales`               | `tax_id`, `ppn_enabled`, `ppn_rate`, `ppn_amount`, `pph_enabled`, `pph_rate`, `pph_amount`, `total_tax` | PPN + PPh snapshot fields on sales                                  |
| `purchase_orders`     | `tax_id`, `pph_enabled`, `pph_rate`, `pph_amount`                                                       | PPh snapshot fields on POs                                          |
| `quotations`          | `tax_id`, `pph_enabled`, `pph_rate`, `pph_amount`                                                       | PPh snapshot fields on quotations                                   |
| `faktur_pajaks`       | `sales_id`, `nsfp`, `dpp`, `ppn`, `status`, `npwp_penjual`, `npwp_pembeli`                              | Generated Faktur Pajak                                              |
| `faktur_pajak_items`  | `faktur_pajak_id`, `nama_barang`, `dpp`, `ppn`                                                          | Line items on Faktur Pajak                                          |
| `nsfp_pools`          | `nsfp`, `status`, `used_at`, `faktur_pajak_id`                                                          | NSFP serial number pool                                             |
| `journal_entries`     | `sourceable_type`, `sourceable_id`                                                                      | PPN + PPh journal entries                                           |
| `accounting_settings` | `ppn_keluaran_account_id`, `pph_receivable_account_id`, `pph_payable_account_id`                     | PPN liability + PPh account references                              |

### Models

| Model         | Key Relationships                                                           |
| ------------- | --------------------------------------------------------------------------- |
| `Sale`        | `belongsTo(Tax)`, `hasOne(FakturPajak)`, `isPpn()` helper, `isPph()` helper |
| `Tax`         | `is_ppn` boolean flag, `is_pph` boolean flag                                |
| `FakturPajak` | `belongsTo(Sales)`, `hasMany(FakturPajakItem)`, `belongsTo(NsfpPool)`       |
| `Tenant`      | `isIndonesian()` helper                                                     |

### Migrations

| Migration                                                                   | Purpose                                                                                                                                          |
| --------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| `2026_07_25_000001_backfill_ppn_keluaran_account_setting.php`               | Backfills `ppn_keluaran_account_id` in accounting_settings for existing tenants                                                                  |
| `2026_07_25_000002_add_tax_jurisdiction_to_tenants_and_is_ppn_to_taxes.php` | Adds `tax_jurisdiction` to tenants, `is_ppn` to taxes. Backfills existing PKP tenants to `tax_jurisdiction = 'ID'`. Flags PPN taxes in TaxSeeder |

---

## Feature Layering

### UI Visibility Gate

```php
@php
    $currentTenant = app('currentTenant');
    $showPpn = $currentTenant instanceof \App\Models\Tenant
             && $currentTenant->isIndonesian()
             && $currentTenant->hasFeature('ppn');
@endendphp
```

This pattern is used in:

- Sales index PPN Status column
- Sales view header (Faktur Pajak button)
- Sales view amount summary (PPN line)
- Customer modals (NPWP/PKP fields)

### Route-Level Gate

```php
// config/navigation.php
'plan_feature' => 'ppn',  // Professional+ only
'permission' => 'view-efaktur',
```

### Service-Level Gate

```php
// EFakturService::generateFakturPajak()
if (! $tenant->pkp_status) {
    throw new \DomainException('Tenant is not registered as PKP.');
}
if (! $sale->isPpn()) {
    throw new \DomainException('PPN is not enabled on this sale.');
}
```

---

## Journal Entry Accounting

### Sale with PPN (Keluaran) — VAT Split at Sale Creation (Phase 1E)

Since Phase 1E (2026-08-05), PPN is recognized **at sale creation**, not at Faktur Pajak issuance. Sale of 200 + PPN 11% = 22, grand total 222:

```
Dr. Accounts Receivable     222        (customer owes the VAT-inclusive total)
Cr. Sales Revenue           200        (net of VAT)
Cr. PPN Keluaran             22        (company owes DJP the PPN)
Dr. Cost of Goods Sold       X
Cr. Inventory                X
```

Source document: `Sales` model (`sourceable_type = Sales`). Posted when the tenant `isIndonesian()`, the selected tax has `is_ppn = true`, and `ppn_keluaran_account_id` is configured. **Fallback:** if the PPN Keluaran account is missing, the legacy 4-leg gross journal (`Dr AR gross / Cr Revenue gross`) is posted instead.

**Faktur Pajak issuance no longer posts a journal** — it is compliance-only (NSFP allocation, DJP snapshot, PDF/CSV).

### Sales Return with PPN

```
Dr. PPN Keluaran            proportional_ppn    (reduce VAT liability)
Cr. Accounts Receivable     proportional_ppn    (reduce what customer owes)
```

Proportional PPN = `sale.ppn_amount × (return_amount / sale.total)`

Since Phase 1E this reversal triggers whenever the original sale `isPpn() && ppn_amount > 0` — a Faktur Pajak is **no longer required** (PPN is now posted at sale creation, so returns reverse it directly).

### Sale with PPh 23 (Withholding Tax)

```
Dr. PPh Receivable (1170-{tid})     pph_amount    (company receives withheld tax from customer)
Cr. PPh 23 Payable  (2150-{tid})   pph_amount    (company remits withheld tax to DJP)
```

Source document: `Sales` model (`sourceable_type = Sales`) — posted at invoice time (Mekari pattern).

PPh is **independent of PPN**. An invoice can have both PPN and PPh journal entries, or just one, or neither.

### Purchase Invoice with PPh 23

```
Dr. PPh 23 Payable  (2150-{tid})   pph_amount    (reduce withholding liability)
Cr. PPh Receivable  (1170-{tid})   pph_amount    (reduce receivable from vendor)
```

### Sale Deleted

1. Reverse all `JournalEntry` where `sourceable_type = Sales`
2. Reverse all `JournalEntry` where `sourceable_type = FakturPajak`
3. Mark NSFP as `cancelled`
4. Delete FakturPajakItem records
5. Delete FakturPajak record
6. Delete Sales record

### Faktur Pajak Cancelled

1. Reverse the PPN Keluaran journal entry **only for legacy FakturPajak-sourced journals** (Phase 1E: PPN is now posted at sale creation, so the FP itself carries no journal; the reversal here exists for backward compatibility with fakturs issued before the fix)
2. Mark NSFP as `cancelled`
3. Update FakturPajak status to `cancelled`

---

## Faktur Pajak Lifecycle

```
Sale created (ppn_enabled = true via is_ppn tax)
    ↓   ← PPN Keluaran journal posted HERE (Phase 1E: Dr AR gross / Cr Revenue net / Cr PPN Keluaran)
"Buat Faktur Pajak" button clicked on sale view
    ↓
EFakturService::generateFakturPajak()   ← compliance-only, no journal
    ├── Validates: pkp_status, isPpn(), no existing FP
    ├── Allocates NSFP (pessimistic lock)
    ├── Calculates DPP and PPN
    └── Creates FakturPajak + FakturPajakItem records
    ↓
FakturPajak status = 'draft'
    ↓
Admin reviews on e-Faktur index (by tax period)
    ↓
Export CSV for DJP desktop app
    ↓
Submit via DJP desktop → status updates to 'submitted' (manual or Phase 2 API)
    ↓
Optionally: Cancel (if draft only) → NSFP marked cancelled, legacy PPN journal reversed
```

### DPP Calculation (Final)

```php
// DPP = grand_total minus total_tax (pre-tax amount)
$dpp = round($sale->total - $sale->total_tax, 2);

// PPN = total_tax directly (no recalculation)
$ppn = round($sale->total_tax, 2);
```

### Line-Item DPP

Each line item's DPP is proportional to its share of the subtotal:

```php
$itemDpp = round((float) $item->total, 2);
$itemPpn = round($itemDpp * ($ppnRate / 100), 2);
```

Order-level discounts are reflected in the parent DPP; line-item discounts are reflected in `$item->total`.

---

## What Was Changed (Complete File List)

### New Files Created

| File                                                                                            | Purpose                                                   |
| ----------------------------------------------------------------------------------------------- | --------------------------------------------------------- |
| `app/Models/FakturPajak.php`                                                                    | Faktur Pajak model                                        |
| `app/Models/FakturPajakItem.php`                                                                | Faktur Pajak line item model                              |
| `app/Models/NsfpPool.php`                                                                       | NSFP serial number pool model                             |
| `app/Services/EFakturService.php`                                                               | Core e-Faktur service (generate, cancel, export, journal) |
| `app/Http/Controllers/Admin/EFakturController.php`                                              | e-Faktur web controller                                   |
| `app/Http/Middleware/CaptureImpersonation.php`                                                  | Impersonation capture middleware                          |
| `routes/admin/efaktur.php`                                                                      | e-Faktur route definitions                                |
| `resources/views/admin/efaktur/index.blade.php`                                                 | e-Faktur list page                                        |
| `resources/views/admin/efaktur/show.blade.php`                                                  | Faktur Pajak detail page                                  |
| `resources/views/admin/efaktur/settings.blade.php`                                              | e-Faktur settings page (tax_jurisdiction, NPWP, NSFP)     |
| `resources/views/admin/efaktur/faktur-pdf.blade.php`                                            | Faktur Pajak PDF template                                 |
| `database/migrations/2026_07_25_000001_backfill_ppn_keluaran_account_setting.php`               | PPN account backfill migration                            |
| `database/migrations/2026_07_25_000002_add_tax_jurisdiction_to_tenants_and_is_ppn_to_taxes.php` | tax_jurisdiction + is_ppn migration                       |

### Modified Files

| File                                                                           | Changes                                                                                                                                       |
| ------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------- |
| `app/Models/Tenant.php`                                                        | Added `isIndonesian()` helper method                                                                                                          |
| `app/Models/Tax.php`                                                           | Added `is_ppn` to `$fillable` and `$casts`                                                                                                    |
| `app/Models/Sales.php`                                                         | Added `isPpn()` helper method                                                                                                                 |
| `app/Services/SalesService.php`                                                | PPN derived from `tax->is_ppn` in createSale/updateSale. `deleteSale()` now reverses FakturPajak entries. Added `fakturPajak` to eager loads. |
| `app/Services/SalesReturnService.php`                                          | Uses `$sale->isPpn()` instead of `$sale->ppn_enabled` for PPN reversal                                                                        |
| `app/Services/EFakturService.php`                                              | Uses `$sale->isPpn()`. Fixed DPP = `total - total_tax`. Fixed `cancelFakturPajak()` to reverse journal entries.                               |
| `app/Services/ChartOfAccountsService.php`                                      | Added `ppn_keluaran_account_id` validation rule                                                                                               |
| `app/Services/TaxService.php`                                                 | Added `ensureIndonesianDefaults()` — auto-creates PPN 11% + PPh 23 2% on switch to Indonesia (idempotent)                                      |
| `app/Services/CustomerService.php`                                             | Added npwp/pkp validation and CSV export columns                                                                                              |
| `app/Http/Controllers/Admin/SalesController.php`                               | Added PPN validation to store/update                                                                                                          |
| `app/Http/Controllers/Admin/CustomerController.php`                            | Added npwp/pkp validation                                                                                                                     |
| `app/Http/Controllers/Admin/EFakturController.php`                             | Added `tax_jurisdiction` handling, toggle safeguards; calls `ensureIndonesianDefaults()` when switching to ID                               |
| `app/Http/Controllers/Admin/SettingsController.php`                            | `updateTaxJurisdiction()` calls `ensureIndonesianDefaults()` when switching to ID                                                            |
| `app/Models/Tenant.php`                                                        | Added `isIndonesian()` helper; `canDisableIndonesianJurisdiction()` fixed to `whereDoesntHave('fakturPajak')`                                  |
| `app/Actions/Fortify/CreateNewUser.php`                                        | Added output VAT account lookup for new tenants                                                                                               |
| `database/seeders/AccountSeeder.php`                                           | Added `ppn_keluaran_account_id` seed                                                                                                          |
| `resources/views/admin/layouts/partials/sales/create/summary-totals.blade.php` | Replaced static tax label with dropdown. Removed PPN toggle.                                                                                  |
| `resources/views/admin/layouts/partials/sales/edit/amount-summary.blade.php`   | Removed PPN toggle section. Kept tax dropdown.                                                                                                |
| `resources/views/admin/layouts/partials/sales/view/header.blade.php`           | Gated by `isIndonesian() && hasFeature('ppn')`                                                                                                |
| `resources/views/admin/layouts/partials/sales/view/amount-summary.blade.php`   | Gated by `isIndonesian() && hasFeature('ppn')`                                                                                                |
| `resources/views/admin/layouts/partials/sales/index/table-header.blade.php`    | PPN Status column gated by `isIndonesian() && hasFeature('ppn')`                                                                              |
| `resources/views/admin/layouts/partials/sales/index/table-row.blade.php`       | PPN status cell gated by `isIndonesian() && hasFeature('ppn')`                                                                                |
| `resources/views/admin/layouts/partials/sales/index/table-body.blade.php`      | Dynamic colspan based on PPN column visibility                                                                                                |
| `resources/views/admin/layouts/modals/customer/custmodals.blade.php`           | NPWP/PKP fields gated by `isIndonesian() && hasFeature('ppn')`                                                                                |
| `resources/views/admin/customer/index.blade.php`                               | Added data attributes for NPWP/PKP edit modal                                                                                                 |
| `resources/views/admin/accounting/accounting-setting.blade.php`                | Added PPN Keluaran account dropdown                                                                                                           |
| `resources/js/admin/partials/sales-order/create/SalesOrderCreate.js`           | Removed PPN listener and calculation block                                                                                                    |
| `resources/js/admin/partials/sales-order/edit/SalesOrderEdit.js`               | Removed PPN listener and calculation block                                                                                                    |
| `resources/js/admin/partials/customer/editModal/form.js`                       | Added NPWP/PKP field population                                                                                                               |
| `app/Providers/NavigationServiceProvider.php`                                  | Added `tenantMatchesJurisdiction()` — filters navigation items by `tax_jurisdiction`                                                          |
| `config/navigation.php`                                                        | Added `'jurisdiction' => 'ID'` to e-Faktur menu item for jurisdiction filtering                                                               |
| `resources/views/admin/settings/settings.blade.php`                            | Added Tax Region card for `tax_jurisdiction` configuration                                                                                    |
| `app/Http/Controllers/Admin/SettingsController.php`                            | Added `updateTaxJurisdiction()` AJAX endpoint                                                                                                 |
| `app/Http/Controllers/Admin/PosController.php`                                 | Replaced manual tax rate input with Tax model dropdown (`tax_id`)                                                                             |
| `app/Http/Controllers/Admin/QuotationController.php`                           | Replaced manual `tax_type`/`tax_rate` with Tax model dropdown (`tax_id`)                                                                      |
| `app/Http/Controllers/Admin/PurchaseOrderController.php`                       | Removed tax fields from PO create page                                                                                                        |
| `lang/en/messages.php`                                                         | 75+ new translation keys                                                                                                                      |
| `lang/id/messages.php`                                                         | 75+ new translation keys                                                                                                                      |

### Phase 1E Files (2026-08-05)

| File                                                                                                            | Change                                                                                                  |
| --------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------- |
| `app/Services/SalesService.php`                                                                                 | VAT split at sale creation: `Dr AR gross / Cr Revenue net / Cr PPN Keluaran` (5-leg) when Indonesian PPN tax + `ppn_keluaran_account_id` configured; 4-leg gross fallback otherwise. |
| `app/Services/PosService.php`                                                                                   | Same VAT split in the POS journal (5-leg); `$tax` added to the closure `use(...)` for the `is_ppn` check. |
| `app/Services/EFakturService.php`                                                                               | Removed `postPpnKeluaranJournalEntry()` + its call in `generateFakturPajak()` — Faktur issuance is now compliance-only. `cancelFakturPajak()` still reverses legacy FP-sourced PPN journals. |
| `app/Services/SalesReturnService.php`                                                                           | Both PPN reversal guards changed — drop the `fakturPajak` requirement; reverse whenever the sale `isPpn() && ppn_amount > 0`. |
| `app/Services/TaxService.php`                                                                                   | `ensureIndonesianDefaults()` now creates `PPh Final 0.5%` (rate 0.5, `is_pph`) instead of `PPH 22` (1.5). |
| `database/seeders/TaxSeeder.php`                                                                                | `PPH 22` block replaced with `PPh Final 0.5%`.                                                            |
| `database/migrations/2026_08_05_000002_backfill_pph_final_tax_record.php`                                       | **NEW** — converts existing `PPH 22` (1.5, `is_pph`) rows in place to `PPh Final 0.5%`, or inserts the record if none exists (idempotent, Indonesian tenants only). |
| `tests/Unit/Services/TaxServiceEnsureIndonesianDefaultsTest.php`                                                | Reworked for `PPh Final 0.5%` (still exactly 2 `is_pph`).                                                 |
| `tests/Feature/Admin/SettingsTaxJurisdictionTest.php`                                                           | Assertions updated for `PPh Final 0.5%`.                                                                 |
| `tests/Unit/Services/SalesServiceTest.php`                                                                      | +2 tests: VAT split (5-transaction journal) and missing-account fallback (4-leg gross).                   |
| `tests/Unit/Services/PosServiceTest.php`                                                                        | +1 test: POS PPN sale journal split.                                                                     |

---

## Key Files Reference

Quick-reference for files most relevant to PPN/e-Faktur implementation and the Phase 1B changes.

| File                                                                         | Purpose                                                                                               |
| ---------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------- |
| `app/Providers/NavigationServiceProvider.php`                                | Jurisdiction filtering in `tenantMatchesJurisdiction()` — hides menu items for non-matching tenants   |
| `config/navigation.php`                                                      | Navigation menu definitions. e-Faktur menu item has `'jurisdiction' => 'ID'` property                 |
| `resources/views/admin/settings/settings.blade.php`                          | Tax Region card — allows `tax_jurisdiction` to be set from the main Settings page                     |
| `app/Http/Controllers/Admin/SettingsController.php::updateTaxJurisdiction()` | AJAX endpoint for updating `tax_jurisdiction` from the Settings page                                  |
| `app/Services/EFakturService.php`                                            | Core e-Faktur service — Faktur Pajak generation, cancellation, CSV export, journal entries            |
| `app/Services/SalesService.php`                                              | Sales creation/update — derives PPN from `tax->is_ppn`, handles `fakturPajak` eager loads             |
| `app/Services/TaxService.php::ensureIndonesianDefaults()`                     | Auto-creates PPN 11% + PPh 23 2% when jurisdiction switches to Indonesia (idempotent)                 |
| `app/Services/SalesReturnService.php`                                        | Sales return creation — uses `$sale->isPpn()` for PPN reversal, `tax_id` payloads                     |
| `app/Services/PosService.php`                                                | POS service — tax model integration via `tax_id`                                                      |
| `app/Models/Tenant.php`                                                      | `isIndonesian()` helper, `canDisableIndonesianJurisdiction()` safeguard, `tax_jurisdiction` attribute |
| `app/Models/Tax.php`                                                         | `is_ppn` flag, `rate`, `type` — source of truth for tax behavior                                      |
| `app/Models/Sales.php`                                                       | `isPpn()` helper — reads from stored snapshot `ppn_enabled` only (never from live Tax relationship)   |
| `app/Http/Controllers/Admin/EFakturController.php`                           | e-Faktur web controller — Faktur Pajak CRUD, toggle safeguards                                        |
| `routes/admin/efaktur.php`                                                   | e-Faktur route definitions                                                                            |

---

## Phase 2: PJAP API (Planned)

Phase 2 will integrate directly with DJP via **Klikpajak PJAP** (Application Service Provider), eliminating the need for CSV export and the desktop e-Faktur application.

### Planned Deliverables

| #   | Deliverable             | Description                                          |
| --- | ----------------------- | ---------------------------------------------------- |
| 1   | PJAP API client service | HTTP client for Klikpajak REST API                   |
| 2   | OAuth2 token management | Auto-refresh DJP authentication tokens               |
| 3   | Faktur Pajak submission | One-click submit from web UI                         |
| 4   | Status polling          | Auto-update FP status from DJP                       |
| 5   | Faktur Pengganti        | Replacement invoice flow                             |
| 6   | Pembatalan via API      | Cancel FP through API instead of manually            |
| 7   | NSFP auto-allocation    | Fetch NSFP numbers from DJP instead of manual import |
| 8   | Batch submission        | Submit multiple FP in one API call                   |

### API Flow

```
Click "Submit to DJP"
    ↓
EFakturService → KlikpajakClient::submitFaktur()
    ↓
OAuth2 token refresh (if needed)
    ↓
POST /api/faktur-pajak
    ↓
DJP processes → returns status + reference
    ↓
Update FakturPajak.status = 'submitted'
    ↓
Store DJP reference number
```

---

## PPh (Withholding Tax) Implementation

**PPh (Pajak Penghasilan)** is Indonesia's Income Tax. PPh 23 covers withholding tax on services, royalties, interest, and other service-related income. It is an **optional second tax**, independent of PPN — an invoice can have PPN, PPh, both, or neither.

### PPh Architecture

PPh follows the same universal tax system pattern as PPN: it is a **property of the selected tax**, not a separate toggle. A tax record is flagged with `Tax.is_pph = true`.

```
Tax Model:
  ├── is_ppn  (boolean)  → PPN compliance features
  ├── is_pph  (boolean)  → PPh compliance features
  └── rate, type, name   → shared fields
```

Unlike PPN (which adds to the total), **PPh subtracts from the total** — the customer pays less because the withholding is deducted at source. This is the fundamental difference:

|                | PPN                   | PPh                                           |
| -------------- | --------------------- | --------------------------------------------- |
| Direction      | Adds to invoice total | Subtracts from invoice total                  |
| Journal source | Invoice/FP time       | Invoice time (Mekari pattern)                 |
| Flag           | `tax.is_ppn`          | `tax.is_pph`                                  |
| Accounts       | PPN Keluaran (`accounting_settings.ppn_keluaran_account_id`) | PPh Receivable (1170) / PPh Payable (2150) |

### Journal Entries (Mekari Pattern)

PPh journal entries are posted **at invoice time** (not at payment time), following the Mekari accounting pattern:

**Sale with PPh 23:**

```
Dr. PPh Receivable (1170-{tid})     pph_amount    (company receives the withheld tax)
Cr. PPh 23 Payable  (2150-{tid})   pph_amount    (company owes withheld tax to DJP)
```

This is a **standalone journal entry** — it does not interact with PPN journal entries. If an invoice has both PPN and PPh, both sets of journal entries are created independently.

**PPh on Purchase Invoices (PPh 23 input):**

```
Dr. PPh 23 Payable  (2150-{tid})   pph_amount    (reduce withholding liability)
Cr. PPh Receivable  (1170-{tid})   pph_amount    (reduce receivable)
```

### PPh Scope

Currently implemented:

- **PPh 23** — withholding on services, royalties, interest (implemented 2026-07-28)
- **PPh Final 0.5%** — UMKM final income tax (PP 23/2018, PP 20/2026). Seeded `Tax` record (`PPh Final 0.5%`, rate `0.5`, `is_pph = true`) in `TaxSeeder` + `TaxService::ensureIndonesianDefaults()` + backfill migration `2026_08_05_000002_backfill_pph_final_tax_record.php` for existing tenants (implemented 2026-08-05). Replaces the previous `PPH 22` seed (1.5%): existing `PPH 22` rows are converted **in place** (same row, new name/rate) so `pph_tax_id` references stay valid and historical `pph_rate`/`pph_amount` snapshots are untouched. No new models or journal code — it flows through every existing PPh dropdown and the shared PPh Payable (2150) path unchanged. `PPH 22` remains creatable from the Tax page for the minority who need it, but is no longer seeded.

Future (planned, UMKM-appropriate scope — decided 2026-07-31):

- **PPh 21** — flat **"Payroll Tax" picker** on the payroll screen (a withholding line in the payment journal). Explicitly **not** a full TER/PTKP/1721-II calculation engine — the UMKM target doesn't need it.
- **PPh 25** — **deferred.** A monthly installment via manual journal entry is sufficient for the UMKM target; a dedicated calculation engine is out of scope.

All three reuse the existing universal `is_pph` mechanism. No separate engines or new journal patterns are planned. Deliberately **not** adding more PPh tax types into the seeded set today: without SPT reporting to distinguish them, additional types would post indistinguishably to the same PPh Payable (2150) and *decrease* accounting accuracy.

---

## Remaining Backlog

### HIGH Priority

| Feature                    | Tier          | Status                   |
| -------------------------- | ------------- | ------------------------ |
| ~~Bank Reconciliation~~        | Professional+ | ✅ DONE Aug 2026 (CSV-based; variance gate, entry locking, reconciled badges) |
| Advanced Multi-Currency    | Professional+ | Planned                  |
| e-Faktur Phase 2: PJAP API | Enterprise    | Planned (8 deliverables) |

### MEDIUM Priority

| Feature                                   | Tier          |
| ----------------------------------------- | ------------- |
| SPT Report Templates                      | Professional+ |
| Bank Integrations (BCA, BNI, Mandiri API) | Professional+ |
| WhatsApp Invoice Delivery                 | All           |
| Midtrans Payment Links on Invoices        | All           |

### LOW Priority

| Feature                                | Tier          |
| -------------------------------------- | ------------- |
| PPh 21 payroll picker (UMKM scope) | Professional+ |
| Mobile App (React Native / Flutter)    | All           |
| Advanced BI Dashboards                 | Professional+ |

---

## Lessons Learned

1. **Don't bolt on compliance features separately.** PPN should have been part of the tax system from day one. The separate toggle created a parallel system that diverged from the source of truth.

2. **Denormalize for historical accuracy, not for input.** The `ppn_enabled` snapshot on Sales is correct — it preserves what was true at creation time. But it should never be user input; it should always be derived from the Tax model.

3. **Tax jurisdiction is a business config, not a feature flag.** Using `hasFeature('ppn')` as the primary gate conflated billing (plan tier) with compliance (country law). Separating them into `tax_jurisdiction` (compliance) and `hasFeature('ppn')` (billing) was the right call.

4. **Safeguards matter.** Allowing users to disable tax_jurisdiction while draft Faktur Pajak exist would create orphaned records. Block early, block hard.

5. **PPN accounting is permanent.** Journal entries for PPN Keluaran should only be reversed through explicit actions (sale delete, return, cancellation). The toggle controls future behavior, not past records. Switching `tax_jurisdiction` or editing `Tax.is_ppn` never retroactively changes existing sales, journal entries, or Faktur Pajak — this is enforced by the snapshot pattern (`Sales::isPpn()` reads only from the stored `ppn_enabled` column) and by backend guards (`SalesService` only applies PPN when `isIndonesian()` is true).
