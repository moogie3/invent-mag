# Tax System Improvement — Design & Implementation Guide

**Author:** Senior Engineer Review  
**Date:** June 2026  
**Version:** 2.5  
**Status:** Implemented

> **July 2026 Update (v2.1):** All 9 remaining gaps from Section 12 (items A through I) have been fixed and verified with passing tests. Items J and K (dead imports/methods) were already fixed in v2.0.
>
> **August 2026 Update (v2.5):** PPh sections 13–14 reconciled against the shipped implementation. PPh base is the pre-PPN taxable amount; the sale-side journal is `Dr PPh Receivable / Cr Accounts Receivable` (account configurable via `pph_receivable_account_id`); gating is via the tenant's Indonesian jurisdiction (no `SettingService`); PPh migrations are per-table (`2026_07_28_000002` sales, `000004` po, `000005` quotations — no `sales_returns` columns); and the auto-seed creates **three** records (PPN 11%, PPH 23, PPh Final 0.5%).

---

## 1. Problem Statement

### 1.1 Current State (Before This Change)

The tax system was a global single-record setting — one tax record per tenant, stored in a `taxes` table with only `name`, `rate`, and `is_active`. Every document (sale, quotation, pipeline conversion) picked up this single active tax via:

```php
$tax = Tax::where('is_active', 1)->first();
$taxRate = $tax ? $tax->rate : 0;
```

**Architectural problems:**

| Problem                                                                                                                               | Impact                                                                                               |
| ------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------- |
| Single global tax — can't apply different taxes to different sales                                                                    | Cannot differentiate exempt sales, zero-rated sales, or multi-tax scenarios                          |
| No tax type — only "exclusive" (additive) tax was possible                                                                            | Inclusive tax (tax already in price, like Indonesian PPN Inclusive) was not supported                |
| Tax selection was implicit — no FK on `sales`, `quotations`, `po`, `sales_returns`                                                    | Reports cannot show "which tax was on this document" — historical accuracy broken on tax rate change |
| `SalesService` and `SalesPipelineService` called `Tax::where('is_active', 1)->first()` — a raw query bypassing tenant scope awareness | Subtle bug: if tenant scope is not active at call time, returns wrong tenant's tax                   |
| Quotation tax was duplicated inline logic (not using the Tax model FK at all)                                                         | `QuotationController::store()` and `update()` re-computed tax without ever linking `tax_id`          |
| Purchase orders had **no tax support** — `Purchase` model had no `tax_rate`, `total_tax`, or `tax_id` fields                          | Inconsistency between sales and purchasing — AP journal entries ignored tax                          |
| `SalesPipelineService::convertToSalesOrder()` called `Tax::where('is_active', 1)->first()`                                            | Active-tax lookup in pipeline conversions — same problem                                             |
| `TaxService` had `Tax::firstOrCreate([])` and `Tax::updateOrCreate([], [...])` — **no tenant_id scoping in the condition**            | `firstOrCreate` with empty array condition would match any tenant's tax record                       |

### 1.2 Target State (After This Change)

- **Multiple taxes per tenant** — each with a name, rate, type (exclusive/inclusive/none), description, and optional `is_default` flag
- **Tax linked by FK** on every financial document (`sales.tax_id`, `quotations.tax_id`, `po.tax_id`, `sales_returns.tax_id`)
- **Tax type enforced in calculation** — `SalesHelper::calculateTaxAmount()` is extended to accept `tax_type`
- **TaxService** is a full CRUD service — same pattern as `CategoryController`/`UnitController`
- **TaxController** is a full CRUD controller with index, store, update, destroy, setDefault
- **All service-layer tax lookups** (`SalesService`, `PurchaseService`, `SalesPipelineService`, `QuotationController`) are updated to receive `tax_id` from the request/caller — no more implicit active-tax global lookup
- **Tax settings UI** becomes a list-based CRUD page (like the Category settings page) — not a single-record form

---

## 2. Database Changes

### 2.1 `taxes` Table — New Columns

Migration: `2026_06_16_000001_add_columns_to_taxes_table.php`

```sql
ALTER TABLE taxes
  ADD COLUMN type          VARCHAR(20)  NOT NULL DEFAULT 'exclusive'
                           COMMENT 'exclusive = tax added on top | inclusive = tax included in price | none = no tax',
  ADD COLUMN description   TEXT         NULL,
  ADD COLUMN is_default    BOOLEAN      NOT NULL DEFAULT FALSE
                           COMMENT 'Only one tax per tenant can be default; new documents pre-select this';
```

**`is_active` column** is retained but its semantics change: `is_active = false` means the tax is archived/hidden from new document creation, but existing FK references remain valid.

**`type` values:**

- `'exclusive'` — tax is added on top of subtotal: `total = subtotal + (subtotal × rate%)`
- `'inclusive'` — tax is already inside the price: `tax = subtotal - (subtotal / (1 + rate%))`
- `'none'` — zero-rate / exempt: tax amount always = 0 regardless of rate

### 2.2 `quotations` Table — Already Present (No Change Needed)

Migration: `2025_03_20_070700_create_quotations_table.php`

The `quotations` table already existed with `tax_id`, `tax_type`, `tax_rate`, and `tax_amount` columns in its base migration. No new migration was required for the PPN tax work.

### 2.3 FK Columns on Financial Documents

Migration: `2026_06_16_000002_add_tax_id_to_financial_documents.php`

```sql
-- sales: add tax_id FK (retain existing tax_rate and total_tax for historical reads)
ALTER TABLE sales ADD COLUMN tax_id BIGINT UNSIGNED NULL;
ALTER TABLE sales ADD FOREIGN KEY (tax_id) REFERENCES taxes(id) ON DELETE SET NULL;

-- po: add tax_id, tax_rate, total_tax (purchases had NO tax support at all)
ALTER TABLE po ADD COLUMN tax_id     BIGINT UNSIGNED NULL;
ALTER TABLE po ADD COLUMN tax_rate   DECIMAL(5,2) NOT NULL DEFAULT 0;
ALTER TABLE po ADD COLUMN total_tax  DECIMAL(15,2) NOT NULL DEFAULT 0;
ALTER TABLE po ADD FOREIGN KEY (tax_id) REFERENCES taxes(id) ON DELETE SET NULL;

-- quotations: tax_id FK (column already exists from create_quotations_table)
-- No change needed here — quotations table is created fresh with tax_id.

-- sales_returns: add tax_id, tax_rate, tax_amount
ALTER TABLE sales_returns ADD COLUMN tax_id     BIGINT UNSIGNED NULL;
ALTER TABLE sales_returns ADD COLUMN tax_rate   DECIMAL(5,2) NOT NULL DEFAULT 0;
ALTER TABLE sales_returns ADD COLUMN tax_amount DECIMAL(15,2) NOT NULL DEFAULT 0;
ALTER TABLE sales_returns ADD FOREIGN KEY (tax_id) REFERENCES taxes(id) ON DELETE SET NULL;
```

**Why `ON DELETE SET NULL` and not `RESTRICT`?**  
A tax record being deleted should not prevent deletion of historical documents. The `tax_rate` column preserves the snapshot of what rate was applied — even if the `Tax` record is later deleted, the rate is still readable from the document directly.

---

## 3. Tax Calculation — Type Semantics

### 3.1 Exclusive Tax (additive — most common)

```
subtotal_after_discount = 1,000,000
tax_rate                = 11%
tax_amount              = 1,000,000 × 0.11 = 110,000
total                   = 1,000,000 + 110,000 = 1,110,000
```

### 3.2 Inclusive Tax (tax inside price — e.g., Indonesian PPN Inclusive)

```
subtotal_after_discount = 1,100,000  (already contains tax)
tax_rate                = 11%
tax_amount              = 1,100,000 - (1,100,000 / 1.11) = 109,009.01
base_before_tax         = 1,100,000 - 109,009.01 = 990,990.99
total                   = 1,100,000  (unchanged — tax was already in the price)
```

### 3.3 No Tax / Exempt

```
tax_rate   = 0  (or any rate — type='none' overrides it)
tax_amount = 0
total      = subtotal_after_discount
```

### 3.4 `SalesHelper::calculateTaxAmount()` — Updated Signature

```php
/**
 * @param float  $amount   Taxable amount (after order discount)
 * @param float  $taxRate  Tax rate as percentage (e.g., 11 for 11%)
 * @param string $taxType  'exclusive' | 'inclusive' | 'none'
 * @return float           Tax amount (never negative)
 */
public static function calculateTaxAmount(float $amount, float $taxRate, string $taxType = 'exclusive'): float
{
    if ($taxType === 'none' || $taxRate <= 0 || $amount <= 0) {
        return 0.0;
    }

    if ($taxType === 'inclusive') {
        // Extract tax that is already contained in the amount
        return round($amount - ($amount / (1 + $taxRate / 100)), 2);
    }

    // exclusive: tax on top
    return round($amount * ($taxRate / 100), 2);
}
```

**`calculateInvoiceSummary()` and `calculateInvoiceSummaryWithReturns()`** are updated to accept `string $taxType = 'exclusive'` and pass it to `calculateTaxAmount()`. The final total calculation also changes for inclusive:

```php
// exclusive: total = taxable_amount + tax_amount
// inclusive: total = taxable_amount (tax is already in the amount, not additive)
$finalTotal = $taxType === 'inclusive'
    ? $taxableAmount
    : $taxableAmount + $taxAmount;
```

---

## 4. Service Layer Changes

### 4.1 How Tax Is Now Resolved

**Before:** Services called `Tax::where('is_active', 1)->first()` implicitly.

**After:** The `tax_id` is passed from the request (form select). The service resolves the Tax model once:

```php
$tax = $data['tax_id'] ? Tax::find($data['tax_id']) : null;
$taxRate = $tax ? (float) $tax->rate : 0.0;
$taxType = $tax ? $tax->type : 'none';
$taxAmount = SalesHelper::calculateTaxAmount($subtotalAfterDiscount, $taxRate, $taxType);
```

The `Tax` model is found via Eloquent (which applies `BelongsToTenant` global scope automatically) — the raw `Tax::where('is_active', 1)` lookup is completely eliminated.

### 4.2 `SalesService::getSalesCreateData()` and `getSalesEditData()`

Both now return the full active tax list for the select dropdown, plus the default tax:

```php
$taxes = Tax::where('is_active', true)->orderBy('name')->get();
$defaultTax = Tax::where('is_default', true)->first();
return compact('customers', 'products', 'taxes', 'defaultTax', ...);
```

### 4.3 `SalesService::createSale()` and `updateSale()`

`tax_id` is added to `$sale->fillable` and stored on the sale record. `tax_rate` and `total_tax` snapshot columns are still written — they remain the source of truth for historical reports (in case the Tax record changes later).

### 4.4 `PurchaseService::createPurchase()` and `updatePurchase()`

Tax support is added identically. `Purchase::$fillable` gains `tax_id`, `tax_rate`, `total_tax`. `getGrandTotalAttribute()` is updated to include tax. `PurchaseHelper::calculateInvoiceSummaryWithReturns()` gains the `$taxRate` and `$taxType` parameters.

### 4.5 `QuotationController`

The controller no longer re-implements tax calculation inline. It resolves the `Tax` model by `tax_id` from the request, delegates calculation to `SalesHelper::calculateTaxAmount()`, and stores `tax_id` on the quotation record.

### 4.6 `SalesPipelineService::convertToSalesOrder()` and `generateQuotation()`

Both methods now receive or use the opportunity's default tax. The implicit `Tax::where('is_active', 1)->first()` is replaced with the tenant's default tax lookup: `Tax::where('is_default', true)->first()`.

---

## 5. Tax CRUD (Settings Page)

### 5.1 Route Structure

```
GET    /settings/tax              → TaxController::index()    — list all taxes
POST   /settings/tax/store        → TaxController::store()    — create new tax
PUT    /settings/tax/{id}         → TaxController::update()   — update tax
DELETE /settings/tax/{id}         → TaxController::destroy()  — delete/archive tax
POST   /settings/tax/{id}/default → TaxController::setDefault() — set as default
```

### 5.2 Business Rules in `TaxService`

1. **Only one default per tenant** — `setDefault()` wraps in a transaction: first clears all `is_default = false`, then sets the target to `true`
2. **Cannot delete a tax that is referenced by financial documents** — `destroy()` checks FK references; if any exist, it archives instead (sets `is_active = false`)
3. **Tax name must be unique per tenant** — enforced at the DB level via unique constraint on `(tenant_id, name)` and validated in the Form Request
4. **Rate must be between 0 and 100** — validated in Form Request
5. **Type must be one of** `exclusive`, `inclusive`, `none` — validated as enum

### 5.3 UI Pattern

The tax settings page follows the same pattern as `CategoryController` / `UnitController`:

- Card list of existing taxes with edit/delete actions inline
- Inline create form at the top
- Delete protected by the same `confirmModal` used across the app
- Status toggle (active/inactive) via AJAX like the existing toggle patterns

---

## 6. Impact on Documents

### 6.1 Sales Orders

- Create/edit forms: tax dropdown (select from active taxes, pre-selected with default tax)
- `tax_id`, `tax_rate`, `total_tax`, `tax_type` (derived from selected tax) stored on sale
- Invoice PDF: shows tax name, type, rate, and amount

### 6.2 Purchase Orders

- Same as sales orders — tax dropdown added to PO create/edit forms
- First-time: Purchase had zero tax support. Now full parity with Sales.

### 6.3 Quotations

- Tax dropdown replaces the inline `tax_rate` / `tax_type` text inputs
- `tax_id` FK stored on quotation
- On `convertToSales()`, `tax_id` is carried over to the new sales order

### 6.4 Sales Pipeline → Sales Order Conversion

- Pipeline opportunities inherit the tenant's default tax on conversion
- Tax is applied to the opportunity total during conversion

### 6.5 Sales Returns

- When a sales return is created against a sale, the tax details from the original sale are copied to the return (same tax_id, tax_rate, tax_amount proportional to returned items)

---

## 7. Backward Compatibility

All existing `sales` records that have `tax_rate` and `total_tax` set but `tax_id = NULL` remain valid. The application:

- Reads `sales.tax_rate` for display/calculation (snapshot is authoritative)
- `sales.tax_id` is `NULL` for historical records — this is expected and handled
- No data migration is needed — the existing snapshot columns are already the correct historical record

---

## 8. Testing

### 8.1 Unit Tests (TaxService)

- `test_create_tax_with_valid_data`
- `test_create_tax_enforces_unique_name_per_tenant`
- `test_set_default_clears_previous_default`
- `test_destroy_archives_instead_of_deleting_when_referenced`
- `test_destroy_deletes_when_no_references`

### 8.2 Feature Tests (TaxController)

- `test_index_returns_tax_list`
- `test_store_creates_tax`
- `test_update_modifies_tax`
- `test_destroy_returns_403_for_non_superuser`
- `test_set_default_works`

### 8.3 Integration Tests

- `test_sale_creation_uses_selected_tax_id`
- `test_purchase_creation_applies_tax`
- `test_quotation_convert_to_sales_carries_tax_id`
- `test_inclusive_tax_does_not_add_to_total`
- `test_exclusive_tax_adds_to_total`
- `test_tax_type_none_results_in_zero_tax`

---

## 9. Files Changed

| File                                                                          | Change Type    |
| ----------------------------------------------------------------------------- | -------------- |
| `database/migrations/2026_06_16_000001_add_columns_to_taxes_table.php`        | New            |
| `database/migrations/2026_06_16_000002_add_tax_id_to_financial_documents.php` | New            |
| `app/Models/Tax.php`                                                          | Updated        |
| `app/Models/Sales.php`                                                        | Updated        |
| `app/Models/Purchase.php`                                                     | Updated        |
| `app/Models/Quotation.php`                                                    | Updated        |
| `app/Models/SalesReturn.php`                                                  | Updated        |
| `app/Services/TaxService.php`                                                 | Rewritten      |
| `app/Services/SalesService.php`                                               | Updated        |
| `app/Services/PurchaseService.php`                                            | Updated        |
| `app/Services/SalesPipelineService.php`                                       | Updated        |
| `app/Helpers/SalesHelper.php`                                                 | Updated        |
| `app/Helpers/PurchaseHelper.php`                                              | Updated        |
| `app/Http/Controllers/Admin/TaxController.php`                                | Rewritten      |
| `routes/admin/settings.php`                                                   | Updated        |
| `database/seeders/TaxSeeder.php`                                              | Updated        |
| `resources/views/admin/tax/tax.blade.php`                                     | Rewritten      |
| `tests/Feature/Admin/TaxControllerTest.php`                                   | New            |
| `lang/en/messages.php`                                                        | Updated        |

---

## 11. Post-Implementation Audit — June 2026 Deep Review

A full static audit of all tax-related files was conducted after the v2.0 implementation. The following bugs were found. Each entry describes the exact problem and the correct fix.

---

### 11.1 CRITICAL — `Sales.php` `grand_total` Accessor Double-Counts Tax

**File:** `app/Models/Sales.php:165–170`

**Problem:**
`createSale()` stores `'total' => $grandTotal` where `$grandTotal = $taxableAmount + $taxAmount` (for exclusive tax). The fallback path in `getGrandTotalAttribute()` — used when `salesItems` is not eager-loaded — then does `$baseTotal + $total_tax`, adding the tax a second time. A sale with subtotal 100,000 and 11% tax would report `grand_total = 122,100` instead of the correct `111,000`.

**Root cause:** The fallback assumed `total` was stored as the pre-tax subtotal. It is stored as the complete grand total.

**Fix applied:** Fallback path simplified to `return round($this->attributes['total'] ?? 0, 2)` for all tax types. The `total` column is always the authoritative grand total; the `total_tax` column exists as an informational snapshot only, not to be re-added on top.

**Status:** ✅ Fixed

---

### 11.2 CRITICAL — `Purchase.php` `grand_total` Accessor Double-Counts Tax (Same Bug)

**File:** `app/Models/Purchase.php:139–144`

**Problem:** Identical to Bug 11.1. The fallback path for `getGrandTotalAttribute()` adds `$this->total_tax` on top of `$this->attributes['total']` for exclusive tax, but `total` already contains the tax.

**Fix applied:** Same fix as 11.1 — fallback returns `$baseTotal` directly for all tax types when items are not loaded.

**Status:** ✅ Fixed

---

### 11.3 CRITICAL — `SalesReturn.php` `'Cancelled'` vs `'Canceled'` Spelling Mismatch

**File:** `app/Models/SalesReturn.php:40`, `app/Services/SalesReturnService.php` (multiple lines)

**Problem:** `SalesReturn::$statuses` array declares `'Cancelled'` (two l's). Every other location in the codebase — `SalesReturnService::bulkCancelSalesReturns()`, `getSalesReturnIndexData()` status counts, and `SalesReturnController` — uses `'Canceled'` (one l). Result: the status dropdown populated from `$statuses` shows the wrong string, and the canceled-count stat always shows 0 because no record ever has status `'Cancelled'`.

**Fix applied:** `SalesReturn::$statuses` corrected to `'Canceled'`.

**Status:** ✅ Fixed

---

### 11.4 HIGH — `SalesService::updateSale()` Discount Key Mismatch

**File:** `app/Services/SalesService.php:583–584`

**Problem:** `createSale()` reads `$data['discount_total']` and `$data['discount_total_type']` (matching what the form sends). `updateSale()` reads `$data['order_discount']` and `$data['order_discount_type']` (different keys). The `SalesController::update()` validator only validates `discount_total` / `discount_total_type`. When the form submits on update, `$data['order_discount']` is `null`, silently zeroing out the discount on every edit.

**Fix required:** Change `updateSale()` lines 583–584 to read `$data['discount_total']` and `$data['discount_total_type']` to match `createSale()` and the controller validator.

```php
// WRONG (current):
$orderDiscount = $data['order_discount'] ?? 0;
$orderDiscountType = $data['order_discount_type'] ?? 'fixed';

// CORRECT:
$orderDiscount = $data['discount_total'] ?? 0;
$orderDiscountType = $data['discount_total_type'] ?? 'fixed';
```

**Status:** ✅ Fixed (Jul 2026) — `updateSale()` now reads `discount_total` and `discount_total_type`, matching `createSale()` and the controller validator

---

### 11.5 HIGH — `Purchase.php` Missing `tax()` BelongsTo Relationship

**File:** `app/Models/Purchase.php`

**Problem:** `Purchase` has `tax_id` in `$fillable` and uses `$this->tax_rate` / `$this->tax_type` throughout `getGrandTotalAttribute()`, but has no `public function tax(): BelongsTo` relationship defined. Any view or service that tries `$purchase->tax->name` gets `null` silently (Eloquent returns null for undefined dynamic relations) rather than the `Tax` model.

**Fix required:** Add the relationship:

```php
public function tax(): BelongsTo
{
    return $this->belongsTo(Tax::class, 'tax_id');
}
```

**Status:** ✅ Fixed (Jul 2026) — `tax()` BelongsTo relationship added at `Purchase.php:99-102`

---

### 11.6 HIGH — `Purchase.getAdjustedTotal()` Ignores Tax

**File:** `app/Models/Purchase.php:173–178`

**Problem:** `getAdjustedTotal()` calls `PurchaseHelper::calculateInvoiceSummaryWithReturns()` without passing `$tax_rate` or `$tax_type`. The method signature accepts these parameters (`$taxRate = 0, $taxType = 'exclusive'`), so they default to 0/exclusive. The adjusted total used for `balance` calculation is therefore always computed without tax, making the balance wrong for any PO with tax applied.

**Fix required:**

```php
$summary = PurchaseHelper::calculateInvoiceSummaryWithReturns(
    $this->items,
    $this->discount_total ?? 0,
    $this->discount_total_type ?? 'fixed',
    $this->relationLoaded('purchaseReturns') ? $this->purchaseReturns : collect(),
    (float) ($this->tax_rate ?? 0),       // ← add this
    $this->tax_type ?? 'none'             // ← add this
);
```

**Status:** ✅ Fixed (Jul 2026) — `getAdjustedTotal()` now passes `(float) ($this->tax_rate ?? 0)` and `$this->tax_type ?? 'none'` at `Purchase.php:171-187`

---

### 11.7 MEDIUM — `SalesReturn.php` `tax_rate` and `tax_amount` Not Cast to Float

**File:** `app/Models/SalesReturn.php:88–91`

**Problem:** `$casts` only includes `return_date` and `total_amount`. `tax_rate` and `tax_amount` are in `$fillable` but not in `$casts`. They are returned as strings from MySQL, which can cause issues in strict comparisons and JSON serialization (shows `"11"` instead of `11`).

**Fix required:** Add to `$casts`:

```php
protected $casts = [
    'return_date'  => 'date',
    'total_amount' => 'float',
    'tax_rate'     => 'float',   // ← add
    'tax_amount'   => 'float',   // ← add
];
```

**Status:** ✅ Fixed (Jul 2026) — Both `tax_rate` and `tax_amount` cast to `float` in `$casts` at `SalesReturn.php:96-101`

---

### 11.8 MEDIUM — `SalesReturnController` Flash Messages Go to URL, Not Session

**File:** `app/Http/Controllers/Admin/SalesReturnController.php:51, 66, 72`

**Problem:** All three redirects use query string parameters instead of session flash:

```php
// WRONG — puts status in URL query string:
return redirect()->route('admin.sales-returns.index', ['status' => 'success', 'message' => '...']);

// CORRECT — puts message in session flash:
return redirect()->route('admin.sales-returns.index')->with('success', '...');
```

Views using `session('success')` never see these messages. Success/error feedback is silently lost.

**Fix required:** Replace all three redirects with `->with('success', '...')`.

**Status:** ✅ Fixed (Jul 2026) — All three redirects now use `->with('success', '...')` session flash pattern at `SalesReturnController.php:51,66,72`

---

### 11.9 MEDIUM — `tax_id` Not Validated in `SalesController::store()` and `update()`

**File:** `app/Http/Controllers/Admin/SalesController.php:85–93, 110–118`

**Problem:** `tax_id` is passed in the request and used by `SalesService` but is not listed in the validator. An invalid or cross-tenant `tax_id` bypasses validation and reaches `Tax::find($taxId)`, which applies the tenant scope and returns `null` — causing the service to fall back to the default tax silently. A stricter fix validates the ID properly.

**Fix required:** Add to both `store()` and `update()` validators:

```php
'tax_id' => 'nullable|exists:taxes,id',
```

**Status:** ✅ Fixed (Jul 2026) — `'tax_id' => 'nullable|exists:taxes,id'` added to both `store()` and `update()` validators at `SalesController.php:93,119`

---

### 11.10 MEDIUM — Dead `use App\Models\Tax` Import in `SalesReturnService`

**File:** `app/Services/SalesReturnService.php:8`

**Problem:** `Tax` is imported but never called in the file. All tax information is copied from the parent sale's stored columns (`$sale->tax_id`, `$sale->tax_rate`, `$sale->tax_type`). The import is dead code.

**Fix required:** Remove the `use App\Models\Tax;` line.

**Status:** ✅ Fixed (Jul 2026) — Dead `use App\Models\Tax` import removed from `SalesReturnService.php`

---

### 11.11 MEDIUM — Dead `product()` BelongsTo on `Sales` Model

**File:** `app/Models/Sales.php:127–130`

**Problem:** `Sales::product()` defines a `belongsTo(Product::class, 'product_id')` relationship, but the `sales` table has no `product_id` column — sales have multiple products via `SalesItem`. This is a vestigial relationship from an earlier data model. Calling `$sale->product` returns `null` silently but suggests to future developers that a direct product link exists.

**Fix required:** Remove the `product()` method entirely.

**Status:** ✅ Fixed (Jul 2026) — Dead `product()` BelongsTo method removed from `Sales.php`

---

### 11.12 LOW — `TaxFactory` Was Missing `type`, `description`, `is_default` Fields

**File:** `database/factories/TaxFactory.php`

**Problem:** The factory only generated `name`, `rate`, and `is_active`. Tests using `Tax::factory()->create()` produced records missing the new schema columns, causing factories to either fail or produce records with database-default values that did not match test intent.

**Fix applied:** Factory updated to include all fields:

```php
'type'        => $this->faker->randomElement(['exclusive', 'inclusive', 'none']),
'description' => $this->faker->optional()->sentence,
'is_active'   => true,
'is_default'  => false,
```

**Status:** ✅ Fixed

---

### 11.13 LOW — `QuotationSeeder` Referenced in `DatabaseSeeder` but Did Not Exist

**File:** `database/seeders/DatabaseSeeder.php:113`, `database/seeders/QuotationSeeder.php` (missing)

**Problem:** `DatabaseSeeder` called `QuotationSeeder::class` which did not exist as a file. Running `php artisan db:seed` would crash with a class-not-found error.

**Fix applied:** `QuotationSeeder.php` created. Seeds 5 sample quotations per tenant using existing customers, products, and the tenant's default tax.

**Status:** ✅ Fixed

---

### 11.14 LOW — `PurchaseService::getPurchaseViewData()` Ignored Tax in Summary

**File:** `app/Services/PurchaseService.php:153–158` (before fix)

**Problem:** `getPurchaseViewData()` called `PurchaseHelper::calculateInvoiceSummaryWithReturns()` without passing `tax_rate` or `tax_type`. The PO view page therefore always showed `$taxAmount = 0` and a `$finalTotal` equal to the pre-tax subtotal minus discount, regardless of what tax was actually applied.

**Fix applied:** The call now passes `(float) ($pos->tax_rate ?? 0)` and `$pos->tax_type ?? 'none'`. `taxAmount` is also added to the `compact()` return so the view can display it.

**Status:** ✅ Fixed

---

### 11.15 LOW — `PurchaseService::getPurchaseCreateData()` and `getPurchaseEditData()` Did Not Pass Taxes to View

**File:** `app/Services/PurchaseService.php`

**Problem:** Both methods returned data for the PO create and edit forms without including `$taxes` (active tax list) or `$defaultTax`. PO create/edit views therefore had no data to populate a tax selector, meaning the tax_id submitted by forms could not be driven by a proper dropdown.

**Fix applied:** Both methods now include:

```php
$taxes      = Tax::active()->get();
$defaultTax = Tax::default()->first() ?? Tax::active()->first();
```

**Status:** ✅ Fixed

---

### 11.16 LOW — `TaxServiceTest` Called Non-Existent Methods

**File:** `tests/Unit/Services/TaxServiceTest.php`

**Problem:** The unit test called `$this->taxService->getTaxData()` and `$this->taxService->updateTax($data)` — method names from the old single-record `TaxService`. The rewritten `TaxService` uses `getAllActive()`, `create()`, and `update(Tax $tax, array $data)`. All three tests using the old method names were failing with `Call to undefined method`.

**Fix applied:** Test rewritten to use the real API — `getAllActive()`, `create()`, and `update()`.

**Status:** ✅ Fixed

---

### 11.17 LOW — `SalesHelperTest` Expected Negative Tax Amount

**File:** `tests/Unit/Helpers/SalesHelperTest.php:100`

**Problem:** Test asserted `assertEquals(-10.0, SalesHelper::calculateTaxAmount(-100.0, 10))`. The implementation correctly guards `$amount <= 0` and returns `0` — negative tax does not exist in any tax system. The test expectation was wrong.

**Fix applied:** Assertion corrected to `assertEquals(0.0, ...)` with a comment explaining the guard.

**Status:** ✅ Fixed

---

## 12. Known Remaining Gaps — ✅ ALL FIXED (July 2026)

The following issues were found during the audit and have all been resolved. Retained for historical reference.

| #   | Severity | File                                                              | Issue                                                                                                                                                                      | Status                                                                                                                                                                                                                            |
| --- | -------- | ----------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------- |
| A   | CRITICAL | `PosService.php`                                                  | POS creates zero accounting journal entries — no AR, Revenue, or COGS recorded for POS sales                                                                               | ✅ FIXED — Added `AccountingService` injection + journal entries (Dr AR/Cr Revenue, Dr COGS/Cr Inventory, Dr Cash/Cr AR)                                                                                                          |
| B   | CRITICAL | `PosController.php:89`                                            | `printReceipt()` renders `admin.pos.print` view which does not exist — always throws `ViewNotFoundException`                                                               | ✅ FIXED — Created `resources/views/admin/pos/print.blade.php`; controller now passes receipt data via `getReceiptData()`                                                                                                         |
| C   | CRITICAL | `PurchaseReturnService.php:203–212`                               | Journal entry for purchase return is self-cancelling — debits and credits the same AP account for the same amount, net effect is zero; supplier balance is never reduced   | ✅ FIXED — Replaced with Purchase Returns account (code 5120) credit; supplier AP is properly debited                                                                                                                             |
| D   | HIGH     | `SalesReturnService.php:572–578`                                  | `bulkCancelSalesReturns()` and `bulkCompleteSalesReturns()` do raw mass `update()` with no side effects — no stock adjustment, no journal entry, no credit issued          | ✅ FIXED — Added `completeSalesReturn()`, `cancelSalesReturn()`, `createSalesReturnJournalEntry()` methods; `cancelPurchaseReturn()` added to PurchaseReturnService; all bulk ops now process individually with full side effects |
| E   | HIGH     | `PurchaseReturnController.php:37`, `SalesReturnController.php:35` | `whereDoesntHave('purchaseReturns'/'salesReturns')` on create means only one return per document is possible through the UI — subsequent partial returns cannot be created | ✅ FIXED — Changed to `whereHas('salesItems')` / `whereHas('items')` — shows all documents with items                                                                                                                             |
| F   | MEDIUM   | `SalesService.php:226`                                            | `getSalesViewData()` loads `Customer::all()` — full table scan — when only the one linked customer is needed                                                               | ✅ FIXED — Changed to `$sales->customer`                                                                                                                                                                                          |
| G   | MEDIUM   | `SalesReturnController.php:88`                                    | `edit()` loads `Sales::all()` — full table scan                                                                                                                            | ✅ FIXED — Changed to `Sales::whereHas('salesItems')->get()`                                                                                                                                                                      |
| H   | MEDIUM   | `SalesService.php:934`                                            | `printInvoice()` shop name query is not tenant-scoped — could return another tenant's shop name                                                                            | ✅ FIXED — Added explicit `tenant_id` filter to shop name queries in SalesService, SalesReturnService, and PurchaseReturnService                                                                                                  |
| I   | MEDIUM   | `Sales.php:67`                                                    | `grand_total_adjusted` is appended but `salesReturns` is not always loaded — silently returns unadjusted total when called without the relation eager-loaded               | ✅ FIXED — Changed `getAdjustedTotal()` to eagerly load `salesReturns` when not loaded                                                                                                                                            |
| J   | LOW      | `SalesReturnService.php:8`                                        | Dead `use App\Models\Tax` import                                                                                                                                           | ✅ FIXED (v2.0)                                                                                                                                                                                                                   |
| K   | LOW      | `Sales.php:127`                                                   | Dead `product()` BelongsTo — `sales` table has no `product_id` column                                                                                                      | ✅ FIXED (v2.0)                                                                                                                                                                                                                   |
| L   | HIGH     | `TaxService.php:96–106`                                           | `hasReferences()` does not check `purchase_returns` table — a tax referenced only by purchase returns can be hard-deleted, creating orphan foreign keys                    | ✅ FIXED — Added `purchase_returns` count to `hasReferences()`                                                                                                                                                                    |
| M   | HIGH     | `PurchaseReturnService.php:541`                                   | `cancelPurchaseReturn()` sets purchase status to `'Pending'` instead of `'Unpaid'` — invalid status value for Purchase model enum                                          | ✅ FIXED — Changed to `'Unpaid'`                                                                                                                                                                                                  |
| N   | MEDIUM   | `PurchaseController.php:83–118`                                   | `store()` and `update()` do not validate `tax_id` — a forged or invalid `tax_id` could be passed to PurchaseService (SalesController validates it correctly)               | ✅ FIXED — Added `'tax_id' => 'nullable                                                                                                                                                                                           | exists:taxes,id'` to both methods |
| O   | LOW      | `POSController.php:48–49`                                         | Validates `tax_rate` and `tax_amount` in request but PosService ignores them — misleading dead validation                                                                  | ✅ FIXED — Removed dead validation rules                                                                                                                                                                                          |
| P   | MEDIUM   | `po/view/amount-summary.blade.php`                                | Purchase show page has no tax line in amount summary — tax is calculated and stored but never displayed to the user                                                        | ✅ FIXED — Added tax line matching sales view pattern                                                                                                                                                                             |
| Q   | MEDIUM   | `por/show/page-body.blade.php`                                    | Purchase return show page has no tax display — tax data is stored but never shown                                                                                          | ✅ FIXED — Added conditional tax line matching sales-returns pattern                                                                                                                                                              |

**Test coverage:** All fixes verified with 1186 passing tests (0 new regressions).

---

## 10. What Was NOT Changed

- **`PosService` tax selector** — POS accepts `tax_id` from the request, falling back to the tenant default tax (`Tax::default()->first() ?? Tax::active()->first()`) when omitted. The PPN tax is snapshotted onto the sale record; PPh is applied too when an Indonesian tenant passes `pph_tax_id`.
- **`SalesHelper::calculateTotal()`** — item-level total calculation is unchanged. Tax is an order-level concept, not a line-item concept.
- **`SalesHelper::calculateDiscount()`** — unchanged.
- **Accounting journal entries for tax** — the current accounting model posts the gross total (inclusive of tax) to AR/Revenue. Splitting revenue vs. tax liability into separate accounts (VAT Output Tax account) is a future enhancement that depends on e-Faktur compliance work.
- **Tax column naming inconsistency** — Sales/Purchase use `total_tax`; Returns/Quotations use `tax_amount`. This is a cosmetic inconsistency that does not affect functionality. Standardizing column names would require a migration + data backfill across 5 tables.
- **Duplicated helper code** — `SalesHelper::calculateTaxAmount()` and `PurchaseHelper::calculateTaxAmount()` are identical implementations (same formula, same behavior). Consolidating into a shared base helper is a refactor, not a bug fix.
- **Quotation tax handling** — Quotations handle tax in the controller (no `QuotationService` exists). The form uses the Tax model dropdown (`tax_id`); when the tenant is Indonesian, a second `pph_tax_id` dropdown is shown and PPh is snapshotted onto the quotation.

### Tax Implementation Consistency Matrix (July 2026)

| Module                | Accepts tax_id         | Snapshots tax                | Tax source            | Calculation    | Show page tax display   |
| --------------------- | ---------------------- | ---------------------------- | --------------------- | -------------- | ----------------------- |
| SalesService          | Yes                    | Yes (id, rate, type, amount) | User select / default | SalesHelper    | ✅                      |
| PurchaseService       | Yes                    | Yes (id, rate, type, amount) | User select / default | PurchaseHelper | ✅ Fixed                |
| SalesReturnService    | No (copies parent)     | Yes (copies parent's data)   | Parent sale           | SalesHelper    | ✅                      |
| PurchaseReturnService | No (copies parent)     | Yes (copies parent's data)   | Parent purchase       | PurchaseHelper | ✅ Fixed                |
| QuotationController   | Yes                    | Yes (id, rate, type, amount) | User select / default | SalesHelper    | ✅                      |
| PosService            | No (hardcoded default) | Yes (snapshot of default)    | Tax model default     | SalesHelper    | ✅                      |
| SalesPipelineService  | No (default only)      | No (transient only)          | Tax model default     | SalesHelper    | N/A (converted to sale) |

---

## 13. PPh (Withholding Tax) — Second Independent Tax Dropdown

### 13.1 Concept

PPh (Pajak Penghasilan / Income Tax) is a **withholding tax** that is fundamentally different from PPN (the output VAT tax). In Indonesian accounting:

- **PPN** (Pajak Pertambahan Nilai) = output VAT, added on top of the sale amount — collected from the buyer and remitted to the tax office
- **PPh** = income tax **withheld** from the seller's revenue — the buyer deducts it at source and remits it to the tax office on the seller's behalf

PPh is **not a replacement or alternative** to PPN — both can appear on the same invoice. Therefore, PPh requires its own **independent dropdown** on every financial document, completely separate from the existing PPN tax selector.

### 13.2 Database — `is_pph` Flag on `taxes` Table

Migration: `2026_07_28_000001_add_is_pph_to_taxes.php`

```sql
ALTER TABLE taxes
  ADD COLUMN is_pph BOOLEAN NOT NULL DEFAULT FALSE
       COMMENT 'TRUE = withholding tax (PPh). FALSE = output VAT (PPN) or exempt.';
```

The existing `taxes` table now serves **two independent tax categories**:

| `is_pph` | `type`      | Meaning                                        |
| --------- | ----------- | ---------------------------------------------- |
| `FALSE`   | `exclusive` | Standard PPN exclusive — tax added on top       |
| `FALSE`   | `inclusive` | PPN inclusive — tax embedded in price           |
| `FALSE`   | `none`      | Tax exempt / zero-rated                        |
| `TRUE`    | `exclusive` | PPh withheld — deducted from seller's revenue  |

The `is_pph` flag is **not** a third tax type — it is a boolean category selector. A PPh tax record is always exclusive (withholding reduces the amount the seller receives), so the `type` column for PPh records is always `'exclusive'`.

**Model update (`Tax.php`):**

```php
// Add to $fillable and $casts:
protected $fillable = [
    // ... existing fields ...
    'is_pph',
];

protected $casts = [
    // ... existing casts ...
    'is_pph' => 'boolean',
];
```

### 13.3 Two Independent Dropdowns on Documents

Every financial document form (Sales, Purchase, Quotation, Sales Return, Purchase Return) now has **two tax dropdowns**:

```
┌─────────────────────────────────────────────────┐
│  PPN Tax (Output VAT)    [Dropdown ▼]           │  ← existing dropdown, filtered: is_pph = FALSE
│  PPh Tax (Withholding)   [Dropdown ▼]           │  ← NEW dropdown, filtered: is_pph = TRUE
└─────────────────────────────────────────────────┘
```

**Filtering logic in controllers/services:**

```php
// PPN taxes (output VAT) — existing dropdown
$ppnTaxes = Tax::where('is_active', true)
    ->where('is_pph', false)
    ->orderBy('name')
    ->get();

// PPh taxes (withholding) — new dropdown
$pphTaxes = Tax::where('is_active', true)
    ->where('is_pph', true)
    ->orderBy('name')
    ->get();
```

Each dropdown is **independent** — selecting a PPN tax does not affect the PPh selection, and vice versa. Both can be `null` (no tax applied for that category).

### 13.4 FK Columns on Financial Documents

PPh columns were added by **three separate per-table migrations** (plus a settings backfill), not one combined migration:

| Migration | Table | Purpose |
| --------- | ----- | ------- |
| `2026_07_28_000002_add_pph_fields_to_sales.php` | `sales` | `pph_tax_id`, `pph_tax_rate`, `pph_tax_amount`, `pph_enabled` |
| `2026_07_28_000003_backfill_pph_account_settings.php` | `settings` | Seeded `pph_receivable_account_id` so the sale-side PPh journal has a target account |
| `2026_07_28_000004_add_pph_fields_to_po.php` | `po` | Same PPh columns on purchase orders |
| `2026_07_28_000005_add_pph_fields_to_quotations.php` | `quotations` | Same PPh columns on quotations |

```sql
-- sales (2026_07_28_000002): add pph_tax_id, pph_tax_rate, pph_tax_amount, pph_enabled
ALTER TABLE sales ADD COLUMN pph_tax_id     BIGINT UNSIGNED NULL;
ALTER TABLE sales ADD COLUMN pph_tax_rate   DECIMAL(5,2) NOT NULL DEFAULT 0;
ALTER TABLE sales ADD COLUMN pph_tax_amount DECIMAL(15,2) NOT NULL DEFAULT 0;
ALTER TABLE sales ADD COLUMN pph_enabled    BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE sales ADD FOREIGN KEY (pph_tax_id) REFERENCES taxes(id) ON DELETE SET NULL;

-- po (2026_07_28_000004): same pattern
ALTER TABLE po ADD COLUMN pph_tax_id     BIGINT UNSIGNED NULL;
ALTER TABLE po ADD COLUMN pph_tax_rate   DECIMAL(5,2) NOT NULL DEFAULT 0;
ALTER TABLE po ADD COLUMN pph_tax_amount DECIMAL(15,2) NOT NULL DEFAULT 0;
ALTER TABLE po ADD FOREIGN KEY (pph_tax_id) REFERENCES taxes(id) ON DELETE SET NULL;

-- quotations (2026_07_28_000005): same pattern
ALTER TABLE quotations ADD COLUMN pph_tax_id     BIGINT UNSIGNED NULL;
ALTER TABLE quotations ADD COLUMN pph_tax_rate   DECIMAL(5,2) NOT NULL DEFAULT 0;
ALTER TABLE quotations ADD COLUMN pph_tax_amount DECIMAL(15,2) NOT NULL DEFAULT 0;
ALTER TABLE quotations ADD FOREIGN KEY (pph_tax_id) REFERENCES taxes(id) ON DELETE SET NULL;
```

> **Note:** There are **no `pph_*` columns on `sales_returns`.** Sales returns copy PPh from the parent sale's snapshot columns at return time — the return row itself stores PPN/PPh amounts via the shared `tax_id`/`tax_amount` columns, not a dedicated PPh FK. (There are also two later backfills, `2026_08_05_000001_backfill_pph_22_tax_record.php` and `2026_08_05_000002_backfill_pph_final_tax_record.php`, which converted the old `PPH 22` seed to the current PPh set.)

**Column naming convention:** PPh columns use the `pph_` prefix to clearly distinguish them from PPN columns (`tax_id`, `tax_rate`, `tax_amount` / `total_tax`).

### 13.5 Snapshot Pattern

PPh follows the **exact same snapshot pattern** as PPN:

- `pph_tax_id` — FK to the `taxes` row (for reference and display of tax name/rate)
- `pph_tax_rate` — **snapshotted** rate at time of document creation (survives tax record edits)
- `pph_tax_amount` — **snapshotted** calculated amount at time of document creation

This means if a PPh tax record's rate is later changed, existing documents retain the correct historical rate and amount.

**Why both `pph_tax_id` and `pph_tax_rate`?**  
The `pph_tax_id` FK enables JOIN-based reporting ("show all sales where PPh tax = X"). The `pph_tax_rate` snapshot ensures the displayed and calculated values are always correct even if the Tax record is modified or deleted (`ON DELETE SET NULL`).

### 13.6 Calculation

PPh is always calculated as a **deduction** (exclusive type). In the shipped implementation (`SalesService::createSale()` / `updateSale()`), the base is the **taxable amount before PPN** — the subtotal after order discount — matching the UMKM PPh Final gross-base convention, where VAT is excluded from peredaran bruto:

```
pph_tax_amount = taxable_amount (before PPN) × (pph_tax_rate / 100)
grand_total    = (taxable_amount + ppn_tax_amount) - pph_tax_amount
```

**For exclusive PPN + PPh together:**

```
subtotal_after_discount = 1,000,000
PPN rate (exclusive)    = 11%
ppn_tax_amount          = 1,000,000 × 0.11 = 110,000

PPh rate                = 2%
pph_tax_amount          = 1,000,000 × 0.02 = 20,000   ← base is BEFORE PPN
grand_total             = 1,000,000 + 110,000 - 20,000 = 1,090,000
```

**Important:** PPh is calculated on the **pre-PPN taxable amount** (`$taxableAmount`), not on the grand total including PPN. This is deliberate — the UMKM PPh Final regime computes peredaran bruto excluding VAT.

**SalesHelper update:**

```php
public static function calculatePPhAmount(float $taxableAmount, float $pphRate): float
{
    if ($pphRate <= 0 || $taxableAmount <= 0) {
        return 0.0;
    }
    return round($taxableAmount * ($pphRate / 100), 2);
}
```

### 13.7 Journal Entries

PPh creates an additional **withholding tax** journal entry that offsets the seller's accounts receivable. The accounting treatment:

**Standard PPN-only sale (existing):**

```
Dr  Accounts Receivable     1,110,000
    Cr  Revenue                        1,000,000
    Cr  PPN Output Tax Payable           110,000
```

**Sale with PPh applied (new) — `SalesService::createSale()`:**

The main journal stays the same (AR debited for the gross total). A second entry then moves the withheld amount out of AR into the PPh receivable asset:

```
Dr  PPh Receivable (Tax Prepaid)  20,000
    Cr  Accounts Receivable                 20,000
```

Net effect: AR net of withholding, with the withheld portion carried as a receivable against the tax office (it offsets the company's PPh payable when filing). The journal is only created when the tenant's jurisdiction is Indonesian **and** the selected tax has `is_pph = true`.

**Account codes for PPh:**

| Code  | Account Name                 | Type      |
| ----- | ---------------------------- | --------- |
| 1170  | PPh Receivable (Tax Prepaid) | Asset     |
| 2150  | PPh Payable                  | Liability |

The **PPh Receivable** account is not hardcoded — it is resolved from the tenant's accounting settings (`pph_receivable_account_id`, backfilled by `2026_07_28_000003_backfill_pph_account_settings.php`); 1170 is the seeded default. **PPh Payable (2150)** is credited on the **purchase side** where the company withholds from suppliers (`PurchaseService::createPurchase()`).

**Implementation in `SalesService`: (after the standard PPN journal entry)**

```php
if ($pphAmount > 0 && app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false)) {
    $pphReceivableAccountId = $accountingSettings['pph_receivable_account_id'] ?? null;
    if ($pphReceivableAccountId) {
        $pphReceivableAccount = Account::find($pphReceivableAccountId);
        if ($pphReceivableAccount) {
            $pphTransactions = [
                ['account_name' => $pphReceivableAccount->name, 'type' => 'debit',  'amount' => $pphAmount],
                ['account_name' => $accountsReceivableAccountName, 'type' => 'credit', 'amount' => $pphAmount],
            ];
            $this->accountingService->createJournalEntry(
                "PPh Withholding on Sale #{$sale->invoice}",
                Carbon::parse($data['order_date']),
                $pphTransactions,
                $sale
            );
        }
    }
}
```

### 13.8 Feature Gating

PPh is gated by the tenant's **tax jurisdiction** (Indonesia), not by a per-tenant settings flag. PPh applies only when `app('currentTenant')->isIndonesian()` is `true` **and** the selected tax has `is_pph = true`. Non-Indonesian tenants never see the PPh dropdown. (There is no `SettingService` and no `pph_enabled` setting key — the PPh columns' `pph_enabled` flag on each document is derived from the jurisdiction + selected tax at snapshot time.)

**Jurisdiction check:**

```php
app('currentTenant')->isIndonesian()
```

**UI gating — tax dropdowns:**

```blade
@if(app('currentTenant')->isIndonesian())
<div class="form-group">
    <label>PPh Tax (Withholding)</label>
    <select name="pph_tax_id">
        <option value="">None</option>
        @foreach($pphTaxes as $tax)
            <option value="{{ $tax->id }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
        @endforeach
    </select>
</div>
@endif
```

**Service-layer gating (`SalesService::createSale()`):**

```php
$pphTaxId = $data['pph_tax_id'] ?? null;
$pphTax = $pphTaxId ? Tax::find($pphTaxId) : null;
$pphTaxRate = $pphTax ? (float) $pphTax->rate : 0;
$pphAmount = SalesHelper::calculatePphAmount($taxableAmount, $pphTaxRate);
// ...
$grandTotal = max(0, $grandTotal - $pphAmount);

// stored on the sale:
'pph_enabled' => app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false),
'pph_rate'    => app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false) ? $pphTaxRate : null,
'pph_amount'  => app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false) ? $pphAmount : 0,
'pph_tax_id'  => app('currentTenant')->isIndonesian() && ($pphTax?->is_pph ?? false) ? $pphTax?->id : null,
```

**Report gating:** Reports that display tax breakdowns render the PPh column only for Indonesian tenants.

### 13.9 Tax CRUD — PPh in Settings

The Tax settings page now includes the `is_pph` checkbox:

- **Create form:** checkbox labeled "This is a withholding tax (PPh)"
- **Edit form:** same checkbox, with a warning if the tax is already referenced by documents
- **List view:** a badge/tag on each tax row showing "PPh" or "PPN" based on `is_pph`
- **Default tax logic:** **one default per tenant** — `TaxService::setDefault()` clears `is_default` across *all* of the tenant's taxes (regardless of `is_pph`), then sets the target. There is no per-category default; `TaxService::getDefault()` returns the single tenant default (falling back to the first active tax).

```php
// TaxService::setDefault() — single default per tenant, not per category
return DB::transaction(function () use ($tax) {
    Tax::where('tenant_id', app('currentTenant')->id)->update(['is_default' => false]);
    $tax->update(['is_default' => true]);
    return $tax->fresh();
});
```

### 13.10 Updated Consistency Matrix

| Module                | Accepts pph_tax_id     | Snapshots PPh                | PPh source              | PPh calculation    |
| --------------------- | ---------------------- | ---------------------------- | ----------------------- | ------------------ |
| SalesService          | Yes (if enabled)       | Yes (id, rate, amount)       | User select / default   | SalesHelper        |
| PurchaseService       | Yes (if enabled)       | Yes (id, rate, amount)       | User select / default   | PurchaseHelper     |
| SalesReturnService    | No (copies parent)     | Yes (copies parent's data)   | Parent sale             | SalesHelper        |
| PurchaseReturnService | No (copies parent)     | Yes (copies parent's data)   | Parent purchase         | PurchaseHelper     |
| QuotationController   | Yes (if enabled)       | Yes (id, rate, amount)       | User select / default   | SalesHelper        |
| PosService            | No (PPH not applicable)| N/A                          | N/A                     | N/A                |
| SalesPipelineService  | No (default only)      | No (transient only)          | Tax model default       | SalesHelper        |

---

## 14. Indonesian Auto-Seed & Jurisdiction Clarity (Aug 2026)

### 14.1 Auto-Seed PPN/PPh on Switching to Indonesia

Switching the **Tax Jurisdiction** to Indonesia (either from the **System Settings → Tax Region** card or the **e-Faktur settings** page) now **automatically creates** the standard Indonesian tax records via `TaxService::ensureIndonesianDefaults()`, so users never have to configure compliance rates by hand. It creates **three records**:

| Record         | Rate | Type      | Flags                     | Default?                                   |
| -------------- | ---- | --------- | ------------------------- | ------------------------------------------ |
| PPN 11%        | 11%  | Exclusive | `is_ppn = true`           | Yes, **only if no default tax exists yet** |
| PPH 23         | 2%   | Exclusive | `is_pph = true`           | No                                         |
| PPh Final 0.5% | 0.5% | Exclusive | `is_pph = true`           | No                                         |

Rules:

- **Idempotent** — if the tenant already has an `is_ppn = true` or `is_pph = true` record, it is left untouched and no duplicate is created. Toggling jurisdiction back and forth never duplicates records.
- **Existing default preserved** — a user's current default tax is never overridden; PPN 11% only becomes default when the tenant has no default at all.
- **No removal on switch-off** — switching away from Indonesia leaves existing tax records intact (users keep their data; only compliance features are gated by jurisdiction).

### 14.2 Jurisdiction vs Tax Configuration — What the User Should Understand

These two concepts are easy to confuse, and the tutorial system now explains the difference on both pages:

- **Tax Jurisdiction** (`None / Indonesia`) is a *compliance switch* on the **System Settings** page. Choosing Indonesia turns on PPN/e-Faktur features (Faktur Pajak, NSFP, NPWP) across the app. It does **not** by itself add a tax rate to any transaction.
- **Tax Configuration** (the **Tax** page) defines the actual *tax records* — name, rate, and type (Exclusive / Inclusive / None) — that get applied to products, sales, and purchases (e.g. PPN 11%, PPh 23 2%). A record only takes effect once it is assigned to a product or order, and PPN additionally requires the jurisdiction to be Indonesia.

New tutorial steps: `tax_jurisdiction_vs_config` (System Settings) and `tax_config_vs_jurisdiction` (Tax page), EN + ID. The stale `define_tax_rates` ("only one tax rate can be configured at a time") and `sales_application` ("tax is not applied to purchase orders") steps were corrected to reflect multi-record configuration and purchase/POS application.

### 14.3 Safeguard Fix — `canDisableIndonesianJurisdiction()`

The disable-safeguard previously queried a nonexistent `sales.faktur_pajak_id` column (the FK lives on `faktur_pajak.sales_id`), which produced a SQL error → 500 → a generic "An error occurred" alert when switching away from Indonesia. It now uses `whereDoesntHave('fakturPajak')`, and the Settings page JS reloads on 419/401 and surfaces the real error message.
