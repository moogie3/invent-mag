# PPN & e-Faktur Feature Evolution

> **Last updated:** 2026-07-31
> **Status:** PPh 23 Implemented — 552 PHP tests / 2541 assertions + 169 JS Vitest files (2026-07-28). PPh 21/22/25 scoped to UMKM-appropriate form (2026-07-31).

---

## Table of Contents

1. [What is PPN & e-Faktur?](#what-is-ppn--e-faktur)
2. [Why We Built It](#why-we-built-it)
3. [Original Implementation (v1)](#original-implementation-v1)
4. [Problems We Found](#problems-we-found)
5. [The Universal Tax Refactor (v2)](#the-universal-tax-refactor-v2)
6. [Phase 1B: Tax Synchronization & Navigation Refactor (2026-07-25)](#phase-1b-tax-synchronization--navigation-refactor-2026-07-25)
7. [Architecture Decisions](#architecture-decisions)
8. [Data Model](#data-model)
9. [Feature Layering](#feature-layering)
10. [Journal Entry Accounting](#journal-entry-accounting)
11. [Faktur Pajak Lifecycle](#faktur-pajak-lifecycle)
12. [What Was Changed (Complete File List)](#what-was-changed-complete-file-list)
13. [Key Files Reference](#key-files-reference)
14. [Phase 2: PJAP API (Planned)](#phase-2-pjap-api-planned)
15. [PPh (Withholding Tax) Implementation](#pph-withholding-tax-implementation)
16. [Remaining Backlog](#remaining-backlog)

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

### Sale with PPN → Faktur Pajak Generated

```
Dr. Accounts Receivable     ppn_amount    (customer owes the PPN)
Cr. PPN Keluaran            ppn_amount    (company owes DJP the PPN)
```

Source document: `FakturPajak` model (`sourceable_type = FakturPajak`)

### Sales Return with PPN

```
Dr. PPN Keluaran            proportional_ppn    (reduce VAT liability)
Cr. Accounts Receivable     proportional_ppn    (reduce what customer owes)
```

Proportional PPN = `sale.ppn_amount × (return_amount / sale.total)`

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

1. Reverse the PPN Keluaran journal entry
2. Mark NSFP as `cancelled`
3. Update FakturPajak status to `cancelled`

---

## Faktur Pajak Lifecycle

```
Sale created (ppn_enabled = true via is_ppn tax)
    ↓
"Buat Faktur Pajak" button clicked on sale view
    ↓
EFakturService::generateFakturPajak()
    ├── Validates: pkp_status, isPpn(), no existing FP
    ├── Allocates NSFP (pessimistic lock)
    ├── Calculates DPP and PPN
    ├── Creates FakturPajak + FakturPajakItem records
    ├── Posts PPN journal entry
    └── Returns FakturPajak model
    ↓
FakturPajak status = 'draft'
    ↓
Admin reviews on e-Faktur index (by tax period)
    ↓
Export CSV for DJP desktop app
    ↓
Submit via DJP desktop → status updates to 'submitted' (manual or Phase 2 API)
    ↓
Optionally: Cancel (if draft only) → NSFP marked cancelled, journal reversed
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
| `app/Services/CustomerService.php`                                             | Added npwp/pkp validation and CSV export columns                                                                                              |
| `app/Http/Controllers/Admin/SalesController.php`                               | Added PPN validation to store/update                                                                                                          |
| `app/Http/Controllers/Admin/CustomerController.php`                            | Added npwp/pkp validation                                                                                                                     |
| `app/Http/Controllers/Admin/EFakturController.php`                             | Added `tax_jurisdiction` handling, toggle safeguards                                                                                          |
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

Future (planned, UMKM-appropriate scope — decided 2026-07-31):

- **PPh 21** — flat **"Payroll Tax" picker** on the payroll screen (a withholding line in the payment journal). Explicitly **not** a full TER/PTKP/1721-II calculation engine — the UMKM target doesn't need it.
- **PPh 22** — a seeded Tax record (`PPH 22`, 1.5%, `is_pph = true`). No new models or journal code — it flows through every existing PPh dropdown and the shared PPh Payable (2150) path unchanged.
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
| PPh 21 payroll picker + PPh 22 seed (UMKM scope) | Professional+ |
| Mobile App (React Native / Flutter)    | All           |
| Advanced BI Dashboards                 | Professional+ |

---

## Lessons Learned

1. **Don't bolt on compliance features separately.** PPN should have been part of the tax system from day one. The separate toggle created a parallel system that diverged from the source of truth.

2. **Denormalize for historical accuracy, not for input.** The `ppn_enabled` snapshot on Sales is correct — it preserves what was true at creation time. But it should never be user input; it should always be derived from the Tax model.

3. **Tax jurisdiction is a business config, not a feature flag.** Using `hasFeature('ppn')` as the primary gate conflated billing (plan tier) with compliance (country law). Separating them into `tax_jurisdiction` (compliance) and `hasFeature('ppn')` (billing) was the right call.

4. **Safeguards matter.** Allowing users to disable tax_jurisdiction while draft Faktur Pajak exist would create orphaned records. Block early, block hard.

5. **PPN accounting is permanent.** Journal entries for PPN Keluaran should only be reversed through explicit actions (sale delete, return, cancellation). The toggle controls future behavior, not past records. Switching `tax_jurisdiction` or editing `Tax.is_ppn` never retroactively changes existing sales, journal entries, or Faktur Pajak — this is enforced by the snapshot pattern (`Sales::isPpn()` reads only from the stored `ppn_enabled` column) and by backend guards (`SalesService` only applies PPN when `isIndonesian()` is true).
