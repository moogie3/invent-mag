# Invent-MAG — Product Roadmap

> **Last updated:** November 2026  
> **Version:** 2.5  
> **Stack:** Laravel 12 · Blade/Alpine.js · MySQL · Redis · Dompdf · Spatie Multitenancy · Spatie Permission  
> **Deployment:** Railway (backend — current production) · Docker image published to GHCR and deployed to a VPS via GitHub Actions on release tags · Vercel (frontend/marketing)  
> **Domain:** https://invent-mag.web.id

---

## Table of Contents

1. [Codebase Architecture Reference](#1-codebase-architecture-reference)
2. [Current Feature State & Plan Tiers](#2-current-feature-state--plan-tiers)
3. [Immediate Frontend / Marketing Fixes](#3-immediate-frontend--marketing-fixes)
4. [e-Faktur & PPN Compliance — Full Robust Implementation](#4-e-faktur--ppn-compliance--full-robust-implementation)
   - [Phase 1 — PPN Foundation & Faktur Pajak](#phase-1--ppn-foundation--faktur-pajak--completed-july-2026)
   - [Phase 2 — PJAP API Direct Submission](#phase-2--pjap-api-direct-submission--completed-2026-11-05)
5. [Other Planned Features](#5-other-planned-features)
6. [Competitive Positioning](#6-competitive-positioning)
7. [Architecture Decisions](#7-architecture-decisions)

---

## 1. Codebase Architecture Reference

This section documents the actual architecture so all future development stays synchronized.

### 1.1 Tenancy: Spatie Multitenancy (subdomain-based)

- Package: `spatie/laravel-multitenancy`
- Each tenant = one row in `tenants` table (central DB). Has `domain` (subdomain), `plan_id`, `plan_status`, `trial_ends_at`, etc.
- Tenant-scoped data lives in **same database, filtered by `tenant_id`**
- Every tenant model uses `BelongsToTenant` trait (`app/Models/Concerns/BelongsToTenant.php`), which:
  - Auto-applies `TenantScope` (global Eloquent scope — adds `WHERE tenant_id = ?` to all queries)
  - Auto-sets `tenant_id` on `creating` from `app('currentTenant')->id`
- Current tenant resolved via: `app('currentTenant')` — bound by Spatie middleware from subdomain
- **All new e-Faktur models MUST use `BelongsToTenant` trait**

### 1.2 Plan / Feature System

- `plans` table: `slug` (starter/professional/enterprise), `features` (JSON array of feature slugs), `max_users`, `max_warehouses`, `max_products`, `trial_days`
- Feature check: `$tenant->hasFeature('accounting')` → checks JSON array
- Route-level guard: `->middleware('plan.feature:accounting')` → `CheckPlanFeature` middleware
- Blade-level guard: `@planHas('accounting') ... @endPlanHas`
- Feature slugs currently in use (verified against `database/seeders/PlanSeeder.php` + route middleware): `accounting`, `pos`, `crm`, `sales_pipeline`, `stock_opname`, `sales_returns`, `purchase_returns`, `hr`, `cash_flow`, `quotations`, `ppn`, `efaktur_api`, `financial_reports`, `multi_currency`, `sales_forecasting`, `api_access`, `audit_compliance`, `bank_reconciliation` (+ non-gated baseline slugs: `products`, `inventory`, `sales`, `purchases`, `invoices`, `dashboard`, `basic_reports`, `customers`, `suppliers`)
- **Plan gating is enforced at the route layer** (`->middleware('plan.feature:<slug>')` on each route group) and controller layer for dashboard widgets (sales forecasting). Every Professional/Enterprise feature is verified gated by `tests/Feature/Admin/PlanFeatureGateTest.php` (15 tests — blocked/allowed per tier, JSON 403 for API clients, legacy no-plan full access). The dead `feature_routes` map was removed from `config/plans.php` in Aug 2026.
- **e-Faktur feature slug (Phase 2) — ADDED 2026-11-05**: `efaktur_api` (Enterprise only), via migration `2026_11_04_000001_add_efaktur_api_feature_to_plans.php` — `ppn` (Phase 1) is already added to Professional & Enterprise plans
- How to add a feature to a plan: migration that updates the JSON array (see `2026_04_18_071144_add_stock_opname_feature_to_plans.php` as pattern)

### 1.3 The "Invoice" Model is `Sales`

> **Critical for e-Faktur implementation**: There is no `Invoice` model.

What looks like an "invoice" in the UI is the `Sales` model (`app/Models/Sales.php`, table `sales`).

| Field | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `tenant_id` | FK → tenants | Auto-managed by BelongsToTenant |
| `invoice` | string UNIQUE | Invoice number, e.g. "INV-0001" |
| `customer_id` | FK → customers | |
| `user_id` | FK → users | |
| `warehouse_id` | FK → warehouses | |
| `order_date` | timestamp | Timezone-aware via accessors |
| `due_date` | timestamp | |
| `tax_rate` | decimal(5,2) | General tax rate (from `taxes` table) |
| `total_tax` | decimal(10,2) | Pre-calculated tax amount |
| `total` | decimal(15,2) | Grand total including tax |
| `status` | enum | Unpaid/Paid/Partial/Returned |
| `type` | enum | `'regular'` or `'pos'` (POS vs regular sale; replaced `is_pos` boolean in May 2026, backward-compat accessor `getIsPosAttribute()` still available) |
| `customer_credit_used` | decimal | Credit applied |
| `sales_opportunity_id` | FK | CRM linkage |

Line items are in `sales_items` table via `SalesItem` model. Fields: `sales_id`, `product_id`, `name`, `quantity`, `customer_price`, `discount`, `discount_type`, `total`.

### 1.4 Accounting Layer

- `accounts` table: CoA. Fields: `tenant_id`, `parent_id`, `name`, `code`, `type` (asset/liability/equity/revenue/expense), `is_active`, `is_contra`, `normal_balance`, `tax_type` (PPN_IN/PPN_OUT/PPH — field exists but not yet used)
- `journal_entries` table: `tenant_id`, `date`, `description`, `sourceable_id`, `sourceable_type` (polymorphic), `status` (draft/posted/void), `entry_type` (standard/opening/reversal/adjusting/closing)
- `transactions` table: `tenant_id`, `journal_entry_id`, `account_id`, `type` (debit/credit), `amount`
- **Accounting settings stored per Tenant:** `tenants.accounting_settings` JSON column (migrated from `users` table, May 2026). Contains: `sales_revenue_account_id`, `accounts_receivable_account_id`, `cost_of_goods_sold_account_id`, `inventory_account_id`, `accounts_payable_account_id`, `cash_account_id`
- **`AccountingService`** (`app/Services/AccountingService.php`) is the single entry point for all journal entry creation:

```php
$this->accountingService->createJournalEntry(
    string $description,
    Carbon $date,
    array $transactions,    // [['account_name' => '...', 'type' => 'debit'/'credit', 'amount' => 0.00]]
    ?Model $sourceDocument  // polymorphic source (Sales, Payment, FakturPajak, etc.)
);
```

All service classes that need accounting (SalesService, PurchaseService, **PosService**, etc.) inject and use `AccountingService`. **Do not create journal entries directly** — always go through `AccountingService::createJournalEntry()`.

> **July 2026 Update:** PosService now posts journal entries through AccountingService: Dr AR/Cr Revenue, Dr COGS/Cr Inventory, Dr Cash/Cr AR. Purchase return journal entries corrected (uses Purchase Returns account 5120 instead of self-cancelling AP entry). Sales/purchase return bulk operations now process individual side effects (stock, journal, credit).

### 1.5 Existing `TaxService` (Do Not Overwrite)

`app/Services/TaxService.php` currently handles the **generic sales tax** (not PPN):
- Gets/updates the tenant's generic `Tax` record (name, rate, is_active)
- Used in `SalesService` to look up the active tax rate for sales orders

**The e-Faktur PPN service must be a SEPARATE class** — `PpnService` or `EFakturService` — not a replacement for or extension of `TaxService`. Both will coexist.

### 1.6 MVC Pattern (No Filament)

This is a **standard Laravel MVC app** with Blade views. There is no Filament.

Structure:
```
app/Http/Controllers/Admin/     — admin panel controllers
resources/views/admin/          — Blade views
app/Services/                   — business logic services
app/Models/                     — Eloquent models
database/migrations/            — migrations
```

All new e-Faktur UI must follow this pattern:
- Controller in `app/Http/Controllers/Admin/EFakturController.php`
- Views in `resources/views/admin/efaktur/`
- Service in `app/Services/EFakturService.php`
- Routes in `routes/web.php` under the admin group

### 1.7 PDF Generation: Dompdf (Already in Use)

`SalesService::printInvoice()` uses `\Dompdf\Dompdf` directly. Same approach for Faktur Pajak PDF:

```php
use Dompdf\Dompdf;

$html = view('admin.efaktur.faktur-pdf', compact('faktur'))->render();
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
return $dompdf->stream('FP-' . $faktur->nsfp . '.pdf', ['Attachment' => false]);
```

---

## 2. Current Feature State & Plan Tiers

### Starter — $19/month (Rp 304.000/bulan)

| Feature | Status | Notes |
|---|---|---|
| Up to 3 users | ✅ Live | `plans.max_users = 3` |
| 1 warehouse | ✅ Live | `plans.max_warehouses = 1` |
| Up to 100 products | ✅ Live | `plans.max_products = 100` |
| Product & inventory management | ✅ Live | |
| Sales & purchase orders | ✅ Live | `Sales` + `Purchase` models |
| Invoice generation (PDF) | ✅ Live | Dompdf via `SalesService::printInvoice()` |
| Dashboard & basic reports | ✅ Live | |
| **POS** | ✅ Included | Feature slug: `pos` |
| **Sales & purchase returns** | ✅ Included | Feature slugs: `sales_returns`, `purchase_returns` |
| **Full accounting suite** | ❌ Not included | Feature slug: `accounting` |
| **CRM & sales pipeline** | ❌ Not included | Feature slug: `crm` |
| **Stock Opname** | ❌ Not included | Feature slug: `stock_opname` |
| **Sales forecasting (AI)** | ❌ Not included | Feature slug: `sales_forecasting` |
| **RESTful API** | ❌ Not included | Feature slug: `api_access` |

> **Note:** Accounting is scoped to Pro & Enterprise. The Starter plan is intentionally positioned as an inventory + order management tool.

---

### Professional — $59/month (Rp 944.000/bulan)

| Feature | Status | Notes |
|---|---|---|
| Everything in Starter | ✅ Live | |
| Up to 10 users | ✅ Live | `plans.max_users = 10` |
| Unlimited warehouses | ✅ Live | `plans.max_warehouses = -1` |
| Up to 500 products | ✅ Live | `plans.max_products = 500` |
| Point of Sale (POS) | ✅ Live | Feature slug: `pos` |
| **Full accounting suite** | ✅ Live | Double-entry CoA, journal entries, P&L, Balance Sheet, Trial Balance |
| CRM & sales pipeline | ✅ Live | Feature slug: `crm` |
| Financial reports & multi-currency | ✅ Live | |
| Stock Opname & Auditing | ✅ Live | Feature slug: `stock_opname` |
| Sales & purchase returns | ✅ Live | |
| **HR & Payroll** | ✅ Live | Feature slug: `hr` |
| **Sales forecasting (AI)** | ❌ Not included | Enterprise only |
| **RESTful API** | ❌ Not included | Enterprise only |

---

### Enterprise — $109/month (Rp 1.744.000/bulan)

| Feature | Status | Notes |
|---|---|---|
| Everything in Professional | ✅ Live | |
| Unlimited users | ✅ Live | `plans.max_users = -1` |
| Unlimited products | ✅ Live | `plans.max_products = -1` |
| Sales forecasting — Holt-Winters | ✅ Live | Feature slug: `sales_forecasting` |
| RESTful API (Scribe-documented) | ✅ Live | Feature slug: `api_access` — scope: essential endpoints only (auth, CMS, payment/webhooks, plan lookup). Full CRUD API = future Enterprise expansion |
| Audit trail & compliance logging | ✅ Live | Feature slug: `audit_compliance` |
| Custom roles & 102 granular permissions | ✅ Live | Spatie Permission |
| **HR & Payroll** | ✅ Live | Feature slug: `hr` |
| **Shopee XLSX product import** | ✅ Live | Feature slug: `shopee_import` — bulk update via Seller Centre mass-update files, matched by SKU Induk; splits Shopee variations into sellable products; stock posts to the main warehouse only |
| Dedicated support & SLA | ✅ Live | |

---

## 3. Immediate Frontend / Marketing Fixes

### 3.1 IDR Pricing Display

**Status:** ✅ Already implemented on `/id/pricing` (Rp 304.000 / Rp 944.000 / Rp 1.744.000 at a 16,000 rate — USD prices now from public `GET /api/plans`, IDR from CMS `billing_rate`)

**Rate:** Billing is charged in IDR at a **superadmin-managed exchange rate**, stored in the `billing_settings` table (fallback: `config/billing.php` = 16,000). The backend (`Admin/PlanController.php`) reads it via `BillingSetting::getRate()`, and `GET /api/cms` exposes it as `billing_rate`. The marketing site (`invent-mag-fe`) uses that CMS `billing_rate` for IDR prices, with `PUBLIC_USD_TO_IDR` as a fallback override. Update it at **Superadmin → Settings → Billing Rate** (`superadmin.settings.billing-rate`). ⚠️ The Astro site is statically generated — rebuild it after changing the rate so the marketing prices reflect it (same workflow as CMS content).

**Data-driven USD prices (Aug 2026):** The card prices themselves (`$19 / $59 / $109`) are no longer hardcoded in `Pricing.astro`. Public `GET /api/plans` (`Api/PlanController`) serves active plan prices from the `plans` table; `invent-mag-fe/src/lib/plans.ts` fetches it at build time (falling back to 19/59/109 when the API is unreachable) and `Pricing.astro` renders the `$` price from it, while `BaseLayout` converts to IDR client-side using `billing_rate`. Edit plan prices at **Superadmin → Plans**, then rebuild the marketing site.

---

### 3.2 Extend Free Trial to 30 Days ✅ DONE

**Rationale:** Zahir offers 30 days. Accounting software takes longer to evaluate than simpler SaaS — users need time to set up CoA, post journal entries, and run reports.

**Implementation (July 2026):**
- `database/seeders/PlanSeeder.php` — all 3 plans updated to `trial_days => 30`
- Competitive tables updated

---

## 4. e-Faktur & PPN Compliance — Full Robust Implementation

### Background & Context

**e-Faktur** is Indonesia's mandatory electronic tax invoice system (DJP — Direktorat Jenderal Pajak). Any business registered as **PKP (Pengusaha Kena Pajak)** must:

1. Issue **Faktur Pajak** for every taxable sale (BKP/JKP)
2. Report and remit **PPN** (Pajak Pertambahan Nilai = VAT, currently **11%**) monthly via SPT Masa PPN
3. Submit Faktur Pajak electronically to DJP

**Why it matters:**
- All medium/large Indonesian businesses are PKP
- Without e-Faktur, Invent-MAG cannot be the primary accounting system for any PKP business
- Zahir Online: CSV export only (manual DJP upload)
- Phase 1 (CSV) = Zahir parity. Phase 2 (PJAP API) = beats Zahir

**Plan tier for e-Faktur:**
- Phase 1 (PPN + Faktur Pajak PDF + CSV export): **Professional & Enterprise** — depends on accounting module
- Phase 2 (PJAP API direct submission): **Enterprise** — premium automation
- Feature slug: `efaktur_api` (Phase 2) — **added 2026-11-05**, Enterprise only; `ppn` (Phase 1) already added

---

### Phase 1 — PPN Foundation & Faktur Pajak ✅ COMPLETED (July 2026)

#### 1.1 New Plan Feature Slugs Migration

Follow the pattern of `2026_04_18_071144_add_stock_opname_feature_to_plans.php`:

```php
// database/migrations/YYYY_MM_DD_add_ppn_feature_to_plans.php
public function up(): void
{
    // ppn = Phase 1 (PDF + CSV) — Professional & Enterprise
    $plans = Plan::whereIn('slug', ['professional', 'enterprise'])->get();
    foreach ($plans as $plan) {
        $features = $plan->features ?? [];
        if (!in_array('ppn', $features)) {
            $features[] = 'ppn';
            $plan->features = $features;
            $plan->save();
        }
    }

    // efaktur_api = Phase 2 (PJAP API) — Enterprise only
    $enterprise = Plan::where('slug', 'enterprise')->first();
    if ($enterprise) {
        $features = $enterprise->features ?? [];
        if (!in_array('efaktur_api', $features)) {
            $features[] = 'efaktur_api';
            $enterprise->features = $features;
            $enterprise->save();
        }
    }
}
```

#### 1.2 Database Schema — New Tables

**Migration: `create_faktur_pajak_table`**

```php
Schema::create('faktur_pajak', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('sales_id')->constrained('sales')->cascadeOnDelete();   // links to Sales model, NOT "invoice"

    // NSFP — Nomor Seri Faktur Pajak
    // Format: XXX.XX-YY.XXXXXXXX  (e.g. 010.000-26.00000001)
    // Assigned from nsfp_pool, must be used sequentially
    $table->string('nsfp', 25)->unique();

    // Tax period
    $table->date('tanggal_faktur');
    $table->unsignedTinyInteger('masa_pajak');   // 1–12
    $table->unsignedSmallInteger('tahun_pajak');

    // Penjual (seller) — snapshotted from tenant settings at time of creation
    // Snapshots are REQUIRED: tenant may change NPWP later, but Faktur is immutable
    $table->string('npwp_penjual', 20);
    $table->string('nama_penjual');
    $table->text('alamat_penjual');

    // Pembeli (buyer) — snapshotted from customer at time of creation
    $table->string('npwp_pembeli', 20)->nullable();  // NULL = non-PKP buyer → use 00.000.000.0-000.000
    $table->string('nama_pembeli');
    $table->text('alamat_pembeli');

    // Tax amounts — all stored as actual values, not cents (cents only used in CSV export)
    $table->decimal('dpp', 15, 2);      // Dasar Pengenaan Pajak (taxable base, excl. PPN)
    $table->decimal('ppn', 15, 2);      // PPN amount = dpp * ppn_rate / 100
    $table->decimal('ppnbm', 15, 2)->default(0);  // PPnBM (luxury goods tax, usually 0)
    $table->decimal('ppn_rate', 5, 2)->default(11.00);  // stored for audit — rate may change

    // Status lifecycle: draft → submitted → approved/rejected
    // 'amended' = Faktur Pengganti issued; 'cancelled' = void
    $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'amended', 'cancelled'])
          ->default('draft')
          ->index();

    // DJP / PJAP submission tracking
    $table->string('djp_reference_number', 50)->nullable();   // DJP's approval ref
    $table->timestamp('djp_submitted_at')->nullable();
    $table->timestamp('djp_response_at')->nullable();
    $table->json('djp_response_payload')->nullable();         // full API response for audit

    $table->string('pjap_submission_id', 100)->nullable();
    $table->string('pjap_status', 50)->nullable();
    $table->unsignedTinyInteger('retry_count')->default(0);
    $table->timestamp('last_retry_at')->nullable();

    // Faktur Pengganti (amendment) tracking
    $table->boolean('is_pengganti')->default(false);
    $table->foreignId('original_faktur_id')->nullable()->constrained('faktur_pajak')->nullOnDelete();

    $table->timestamps();

    $table->index(['tenant_id', 'masa_pajak', 'tahun_pajak']);  // SPT Masa PPN queries
});
```

**Migration: `create_faktur_pajak_items_table`**

```php
Schema::create('faktur_pajak_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();          // BelongsToTenant required
    $table->foreignId('faktur_pajak_id')->constrained('faktur_pajak')->cascadeOnDelete();

    $table->string('nama_barang');                    // product/service name
    $table->decimal('harga_satuan', 15, 2);
    $table->decimal('jumlah_barang', 15, 4);
    $table->decimal('harga_total', 15, 2);            // harga_satuan * jumlah_barang
    $table->decimal('diskon', 15, 2)->default(0);
    $table->decimal('dpp', 15, 2);                    // harga_total - diskon
    $table->decimal('ppn', 15, 2);

    // BKP/JKP classification code per DJP spec
    // 01 = BKP (Barang Kena Pajak), 04 = JKP (Jasa Kena Pajak)
    $table->string('kode_objek_pajak', 10)->default('01');

    $table->timestamps();

    $table->index('faktur_pajak_id');
});
```

**Migration: `create_nsfp_pool_table`**

```php
// NSFP = Nomor Seri Faktur Pajak
// DJP allocates these in blocks. They MUST be used sequentially and cannot be reused.
// Tenants upload their NSFP allocation here; system assigns them FIFO.
Schema::create('nsfp_pool', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->string('nsfp', 25)->unique();
    $table->enum('status', ['available', 'used', 'cancelled'])->default('available')->index();
    $table->timestamp('used_at')->nullable();
    $table->foreignId('faktur_pajak_id')->nullable()->constrained('faktur_pajak')->nullOnDelete();
    $table->timestamps();

    $table->index(['tenant_id', 'status']);  // fast NSFP allocation query
});
```

**Migration: `add_ppn_fields_to_tenants_table`**

```php
Schema::table('tenants', function (Blueprint $table) {
    $table->string('npwp', 20)->nullable()->after('domain');          // tenant's NPWP
    $table->boolean('pkp_status')->default(false)->after('npwp');     // is registered as PKP?
    $table->date('pkp_date')->nullable()->after('pkp_status');        // date registered as PKP
    $table->decimal('ppn_rate', 5, 2)->default(11.00)->after('pkp_date');  // current DJP rate
    $table->string('klikpajak_api_key')->nullable()->after('ppn_rate'); // Phase 2
    $table->string('klikpajak_secret')->nullable()->after('klikpajak_api_key'); // Phase 2
    $table->boolean('klikpajak_use_sandbox')->default(true)->after('klikpajak_secret'); // Phase 2
});
```

**Migration: `add_ppn_fields_to_customers_table`**

```php
Schema::table('customers', function (Blueprint $table) {
    $table->string('npwp', 20)->nullable()->after('email');
    $table->boolean('pkp_status')->default(false)->after('npwp');
    $table->date('pkp_date')->nullable()->after('pkp_status');
    // Address as registered with DJP (may differ from operational address)
    $table->text('alamat_npwp')->nullable()->after('pkp_date');
});
```

**Migration: `add_ppn_fields_to_sales_table`**

```php
Schema::table('sales', function (Blueprint $table) {
    // PPN is separate from the generic tax_rate (which stays for sales tax)
    $table->boolean('ppn_enabled')->default(false)->after('total_tax');
    $table->decimal('ppn_rate', 5, 2)->nullable()->after('ppn_enabled');    // rate at time of sale
    $table->decimal('ppn_amount', 15, 2)->default(0)->after('ppn_rate');   // computed PPN amount
});
```

**Migration: `add_ppn_accounts_to_accounting_settings`**

No migration needed for this — `accounting_settings` is a JSON column on `users`. Update the UI settings form and `ChartOfAccountsService::validateSettingsData()` to include:

```php
// Add to the validation array in ChartOfAccountsService::validateSettingsData()
'ppn_keluaran_account_id' => 'nullable|exists:accounts,id',   // PPN Output Tax (liability)
'ppn_masukan_account_id'  => 'nullable|exists:accounts,id',   // PPN Input Tax (asset)
```

Also ensure the default CoA seeder creates these two accounts:

```
2110 — PPN Keluaran    (type: liability, normal_balance: credit, tax_type: PPN_OUT)
1140 — PPN Masukan     (type: asset,     normal_balance: debit,  tax_type: PPN_IN)
```

(The `accounts.tax_type` field already exists from `2026_02_11_000004_add_enhanced_fields_to_accounts_table.php` — use it.)

---

#### 1.3 Eloquent Models

**`app/Models/FakturPajak.php`**

```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FakturPajak extends Model
{
    // BelongsToTenant: auto-sets tenant_id on creating, auto-scopes all queries
    use BelongsToTenant;

    protected $table = 'faktur_pajak';

    protected $guarded = ['id', 'tenant_id'];

    protected $casts = [
        'tanggal_faktur'       => 'date',
        'djp_submitted_at'     => 'datetime',
        'djp_response_at'      => 'datetime',
        'djp_response_payload' => 'array',
        'is_pengganti'         => 'boolean',
        'pkp_status'           => 'boolean',
        'dpp'                  => 'decimal:2',
        'ppn'                  => 'decimal:2',
        'ppnbm'                => 'decimal:2',
        'ppn_rate'             => 'decimal:2',
    ];

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    /**
     * The Sales record this Faktur Pajak was generated from.
     * NOTE: "Sales" is the invoice model — there is no separate Invoice model.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sales::class, 'sales_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(FakturPajakItem::class);
    }

    public function originalFaktur(): BelongsTo
    {
        return $this->belongsTo(FakturPajak::class, 'original_faktur_id');
    }

    // -----------------------------------------------------------------------
    // Business Logic Helpers
    // -----------------------------------------------------------------------

    public function isSubmittable(): bool
    {
        return $this->status === 'draft';
    }

    public function totalTagihan(): float
    {
        return (float) $this->dpp + (float) $this->ppn + (float) $this->ppnbm;
    }

    public function formattedNsfp(): string
    {
        return $this->nsfp; // already stored in formatted form: 010.000-26.00000001
    }
}
```

**`app/Models/FakturPajakItem.php`**

```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FakturPajakItem extends Model
{
    use BelongsToTenant;

    protected $table = 'faktur_pajak_items';

    protected $guarded = ['id', 'tenant_id'];

    protected $casts = [
        'harga_satuan'  => 'decimal:2',
        'jumlah_barang' => 'decimal:4',
        'harga_total'   => 'decimal:2',
        'diskon'        => 'decimal:2',
        'dpp'           => 'decimal:2',
        'ppn'           => 'decimal:2',
    ];

    public function fakturPajak(): BelongsTo
    {
        return $this->belongsTo(FakturPajak::class);
    }
}
```

**`app/Models/NsfpPool.php`**

```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NsfpPool extends Model
{
    use BelongsToTenant;

    protected $table = 'nsfp_pool';

    protected $guarded = ['id', 'tenant_id'];

    protected $casts = [
        'used_at' => 'datetime',
    ];

    public function fakturPajak(): BelongsTo
    {
        return $this->belongsTo(FakturPajak::class);
    }

    /**
     * Scope: only available (unassigned) NSFP entries, ordered for FIFO allocation.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')->orderBy('nsfp');
    }
}
```

**Add relationship to `Sales` model:**

```php
// In app/Models/Sales.php — add to existing relationships:
public function fakturPajak(): \Illuminate\Database\Eloquent\Relations\HasOne
{
    return $this->hasOne(FakturPajak::class, 'sales_id');
}
```

**Add relationship to `Customer` model:**

```php
// In app/Models/Customer.php — add after existing fillable:
// Also add 'npwp', 'pkp_status', 'pkp_date', 'alamat_npwp' to $fillable
```

---

#### 1.4 `EFakturService` — Core Business Logic

**`app/Services/EFakturService.php`**

> **NOT named `TaxService`** — that name is already taken by the generic sales tax service. This is a distinct class.

```php
<?php

namespace App\Services;

use App\Models\FakturPajak;
use App\Models\FakturPajakItem;
use App\Models\NsfpPool;
use App\Models\Sales;
use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

class EFakturService
{
    public function __construct(
        protected AccountingService $accountingService
    ) {}

    // -----------------------------------------------------------------------
    // Faktur Pajak Generation
    // -----------------------------------------------------------------------

    /**
     * Generate a FakturPajak from a Sales record.
     *
     * Guards:
     * - Tenant must be PKP
     * - Sale must have ppn_enabled = true
     * - No existing FakturPajak for this sale (prevent duplicates)
     * - NSFP pool must have available numbers
     *
     * Atomicity:
     * - DB::transaction wraps NSFP allocation + FakturPajak + Items + Journal Entry
     * - lockForUpdate() on NSFP row prevents race conditions under concurrent requests
     */
    public function generateFakturPajak(Sales $sale): FakturPajak
    {
        $tenant = app('currentTenant');

        if (! $tenant->pkp_status) {
            throw new \DomainException('Tenant is not registered as PKP. Cannot issue Faktur Pajak.');
        }

        if (! $sale->ppn_enabled) {
            throw new \DomainException('PPN is not enabled on this sale. Toggle PPN first.');
        }

        if ($sale->fakturPajak()->exists()) {
            throw new \DomainException('A Faktur Pajak has already been generated for this sale (Invoice: ' . $sale->invoice . ').');
        }

        return DB::transaction(function () use ($sale, $tenant) {
            // Allocate NSFP atomically — pessimistic lock prevents race conditions
            $nsfp = NsfpPool::available()
                ->lockForUpdate()
                ->firstOrFail('No available NSFP numbers. Please import more NSFP from DJP.');

            $nsfp->update(['status' => 'used', 'used_at' => now()]);

            $customer = $sale->customer;
            $ppnRate  = $tenant->ppn_rate ?? 11.00;

            // Calculate DPP: use the sale's stored total as DPP base (excl. PPN)
            // If the sale already has ppn_amount set, DPP = total - ppn_amount
            // If not, DPP = total / (1 + rate/100) — work backwards from inclusive price
            $dpp = $sale->ppn_amount > 0
                ? ($sale->total - $sale->ppn_amount)
                : round($sale->total / (1 + $ppnRate / 100), 2);

            $ppn = round($dpp * ($ppnRate / 100), 2);

            $faktur = FakturPajak::create([
                'sales_id'        => $sale->id,
                'nsfp'            => $nsfp->nsfp,
                'tanggal_faktur'  => $sale->order_date->toDateString(),
                'masa_pajak'      => $sale->order_date->month,
                'tahun_pajak'     => $sale->order_date->year,
                'npwp_penjual'    => $tenant->npwp,
                'nama_penjual'    => $tenant->name,
                'alamat_penjual'  => Auth::user()->address ?? '',
                'npwp_pembeli'    => $customer->npwp ?? '00.000.000.0-000.000',
                'nama_pembeli'    => $customer->name,
                'alamat_pembeli'  => $customer->alamat_npwp ?? $customer->address,
                'dpp'             => $dpp,
                'ppn'             => $ppn,
                'ppnbm'           => 0,
                'ppn_rate'        => $ppnRate,
                'status'          => 'draft',
            ]);

            // Update NSFP pool with back-reference
            $nsfp->update(['faktur_pajak_id' => $faktur->id]);

            // Create line items from the sale's sales_items
            $sale->load('salesItems.product');
            foreach ($sale->salesItems as $item) {
                $itemDpp = round($item->total, 2);
                $itemPpn = round($itemDpp * ($ppnRate / 100), 2);

                FakturPajakItem::create([
                    'faktur_pajak_id' => $faktur->id,
                    'nama_barang'     => $item->name ?? $item->product->name,
                    'harga_satuan'    => $item->customer_price,
                    'jumlah_barang'   => $item->quantity,
                    'harga_total'     => $item->total,
                    'diskon'          => $item->discount ?? 0,
                    'dpp'             => $itemDpp,
                    'ppn'             => $itemPpn,
                    'kode_objek_pajak'=> '01',  // BKP default
                ]);
            }

            // Post PPN Keluaran journal entry via AccountingService
            // This follows the exact same pattern as SalesService::createSale()
            $this->postPpnKeluaranJournalEntry($faktur, $sale);

            Log::info('FakturPajak generated', [
                'faktur_id'  => $faktur->id,
                'nsfp'       => $faktur->nsfp,
                'sales_id'   => $sale->id,
                'tenant_id'  => $tenant->id,
            ]);

            return $faktur;
        });
    }

    // -----------------------------------------------------------------------
    // Journal Entry for PPN Keluaran
    // -----------------------------------------------------------------------
    //
    // NOTE (Phase 1E, Aug 2026): this journal-at-generation method was REMOVED
    // in Phase 1E. PPN Keluaran is now recognized at SALE CREATION via the
    // 5-leg VAT-split journal in SalesService::createSale()/PosService
    // (Dr AR gross / Cr Revenue net / Cr PPN Keluaran), and Faktur Pajak
    // generation posts NO journal — it is a compliance-only document.
    // The snippet below is retained as historical context for the original plan.

    /**
     * Post PPN Keluaran (output VAT) journal entry.
     *
     * When a Faktur Pajak is issued, the PPN portion creates:
     *   Dr. Accounts Receivable     ppn_amount   (customer owes us the PPN)
     *   Cr. PPN Keluaran (Liability) ppn_amount  (we owe DJP the PPN)
     *
     * Account IDs come from user's accounting_settings JSON — same pattern
     * as SalesService::createSale() and SalesService::addPayment().
     *
     * PPN Keluaran and PPN Masukan account IDs are added to accounting_settings
     * via the e-Faktur settings page (not hardcoded account codes).
     */
    protected function postPpnKeluaranJournalEntry(FakturPajak $faktur, Sales $sale): void
    {
        $accountingSettings = Auth::user()->accounting_settings ?? [];

        // These two account IDs are configured in the e-Faktur settings page
        $ppnKeluaranAccountId = $accountingSettings['ppn_keluaran_account_id'] ?? null;
        $arAccountId          = $accountingSettings['accounts_receivable_account_id'] ?? null;

        // If PPN accounts aren't configured yet, skip posting (don't crash)
        if (! $ppnKeluaranAccountId || ! $arAccountId) {
            Log::warning('PPN journal entry skipped — ppn_keluaran_account_id or AR account not configured.', [
                'faktur_id' => $faktur->id,
            ]);
            return;
        }

        $ppnKeluaranAccount = Account::find($ppnKeluaranAccountId);
        $arAccount          = Account::find($arAccountId);

        if (! $ppnKeluaranAccount || ! $arAccount) {
            Log::warning('PPN journal entry skipped — account not found.', ['faktur_id' => $faktur->id]);
            return;
        }

        // Use AccountingService — do NOT create journal entries directly
        $this->accountingService->createJournalEntry(
            description: 'PPN Keluaran — FP ' . $faktur->nsfp . ' / ' . $faktur->nama_pembeli,
            date: Carbon::parse($faktur->tanggal_faktur),
            transactions: [
                [
                    'account_name' => $arAccount->name,
                    'type'         => 'debit',
                    'amount'       => $faktur->ppn,
                ],
                [
                    'account_name' => $ppnKeluaranAccount->name,
                    'type'         => 'credit',
                    'amount'       => $faktur->ppn,
                ],
            ],
            sourceDocument: $faktur  // FakturPajak is the sourceable, not Sales
        );
    }

    // -----------------------------------------------------------------------
    // PPN Calculation Utility
    // -----------------------------------------------------------------------

    /**
     * Calculate PPN components for a given subtotal.
     * Used in the sales form to show real-time PPN preview via AJAX.
     */
    public function calculatePpn(float $subtotal, ?float $ppnRate = null): array
    {
        $tenant  = app('currentTenant');
        $rate    = $ppnRate ?? $tenant->ppn_rate ?? 11.0;
        $dpp     = round($subtotal, 2);
        $ppn     = round($dpp * ($rate / 100), 2);
        $total   = $dpp + $ppn;

        return compact('dpp', 'ppn', 'total', 'rate');
    }

    // -----------------------------------------------------------------------
    // NSFP Pool Management
    // -----------------------------------------------------------------------

    /**
     * Import a block of NSFP numbers from a newline-separated string.
     * Called from the e-Faktur settings page.
     *
     * Validates format: must match DJP NSFP pattern.
     * Skips duplicates silently (idempotent import).
     */
    public function importNsfpPool(string $nsfpText): array
    {
        $lines  = preg_split('/\r?\n/', trim($nsfpText));
        $tenant = app('currentTenant');
        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        foreach ($lines as $line) {
            $nsfp = trim($line);
            if (empty($nsfp)) continue;

            // Validate NSFP format: XXX.XXX-YY.XXXXXXXX
            if (! preg_match('/^\d{3}\.\d{3}-\d{2}\.\d{8}$/', $nsfp)) {
                $errors[] = "Invalid NSFP format: {$nsfp}";
                continue;
            }

            // Skip if already in pool (idempotent)
            if (NsfpPool::where('nsfp', $nsfp)->exists()) {
                $skipped++;
                continue;
            }

            NsfpPool::create([
                'tenant_id' => $tenant->id,
                'nsfp'      => $nsfp,
                'status'    => 'available',
            ]);
            $imported++;
        }

        return compact('imported', 'skipped', 'errors');
    }

    // -----------------------------------------------------------------------
    // DJP e-Faktur CSV Export
    // -----------------------------------------------------------------------

    /**
     * Export Faktur Pajak as DJP e-Faktur CSV format for a given tax period.
     *
     * This is the minimum viable e-Faktur compliance — same level as Zahir Online.
     * The CSV can be imported into DJP's e-Faktur desktop application.
     *
     * DJP CSV spec:
     * - FK row: Faktur Keluaran header
     * - OF row: item detail rows
     * - Amounts are in CENTS (integer, no decimal point)
     * - UTF-8 BOM required for Excel compatibility with Indonesian locale
     */
    public function exportCsvForDjp(int $masaPajak, int $tahunPajak): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $fakturs = FakturPajak::with('items')
            ->where('masa_pajak', $masaPajak)
            ->where('tahun_pajak', $tahunPajak)
            ->whereIn('status', ['draft', 'approved'])
            ->orderBy('nsfp')
            ->get();

        $filename = sprintf('FK-%02d-%d-efaktur.csv', $masaPajak, $tahunPajak);

        return response()->streamDownload(function () use ($fakturs) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM — required for Indonesian Excel locale
            fwrite($handle, "\xEF\xBB\xBF");

            // Header row
            fputcsv($handle, [
                'FK', 'KD_JENIS_TRANSAKSI', 'FG_PENGGANTI', 'NOMOR_FAKTUR',
                'MASA_PAJAK', 'TAHUN_PAJAK', 'TANGGAL_FAKTUR',
                'NPWP', 'NAMA', 'ALAMAT_LENGKAP',
                'JUMLAH_DPP', 'JUMLAH_PPN', 'JUMLAH_PPNBM',
                'ID_KETERANGAN_TAMBAHAN', 'FG_UANG_MUKA',
                'UANG_MUKA_DPP', 'UANG_MUKA_PPN', 'UANG_MUKA_PPNBM',
                'REFERENSI',
            ]);

            foreach ($fakturs as $faktur) {
                // FK row — Faktur header
                fputcsv($handle, [
                    'FAPR',
                    '01',   // 01 = penyerahan BKP/JKP dalam negeri biasa
                    $faktur->is_pengganti ? '1' : '0',
                    $faktur->nsfp,
                    $faktur->masa_pajak,
                    $faktur->tahun_pajak,
                    $faktur->tanggal_faktur->format('Y-m-d'),
                    $faktur->npwp_pembeli,
                    $faktur->nama_pembeli,
                    $faktur->alamat_pembeli,
                    // DJP format: amounts in CENTS (integer, multiply by 100, cast to int)
                    (int) round($faktur->dpp * 100),
                    (int) round($faktur->ppn * 100),
                    (int) round($faktur->ppnbm * 100),
                    '',     // keterangan tambahan — usually empty
                    '0',    // uang muka flag
                    '0', '0', '0',
                    $faktur->sale->invoice ?? '',  // original sales invoice number for reference
                ]);

                // OF rows — item detail
                foreach ($faktur->items as $item) {
                    fputcsv($handle, [
                        'OF',
                        $item->kode_objek_pajak,  // 01 = BKP
                        $item->nama_barang,
                        (int) round($item->harga_satuan * 100),
                        $item->jumlah_barang,
                        (int) round($item->harga_total * 100),
                        (int) round($item->diskon * 100),
                        (int) round($item->dpp * 100),
                        (int) round($item->ppn * 100),
                        '0',  // PPnBM
                    ]);
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // -----------------------------------------------------------------------
    // SPT Masa PPN Summary
    // -----------------------------------------------------------------------

    /**
     * Get summary data for SPT Masa PPN monthly report.
     *
     * Returns:
     * - Total PPN Keluaran (output VAT from sales)
     * - Count of approved/draft Faktur for the period
     * - Breakdown by status
     *
     * Note: PPN Masukan (input VAT from purchases) requires a separate
     * Faktur Pajak Masukan tracking system — not in Phase 1 scope.
     */
    public function getSptMasaSummary(int $masaPajak, int $tahunPajak): array
    {
        $query = FakturPajak::where('masa_pajak', $masaPajak)
            ->where('tahun_pajak', $tahunPajak);

        return [
            'masa_pajak'      => $masaPajak,
            'tahun_pajak'     => $tahunPajak,
            'total_faktur'    => $query->count(),
            'approved'        => $query->where('status', 'approved')->count(),
            'draft'           => $query->where('status', 'draft')->count(),
            'rejected'        => $query->where('status', 'rejected')->count(),
            'total_dpp'       => $query->whereIn('status', ['draft', 'approved'])->sum('dpp'),
            'total_ppn'       => $query->whereIn('status', ['draft', 'approved'])->sum('ppn'),
        ];
    }
}
```

---

#### 1.5 Faktur Pajak PDF — Blade Template

`resources/views/admin/efaktur/faktur-pdf.blade.php`

The PDF must include per DJP format:
- "FAKTUR PAJAK" heading in bold
- Kode & Nomor Seri Faktur Pajak (NSFP) prominently
- Tanggal Faktur
- NPWP, Nama, Alamat — Penjual (seller)
- NPWP, Nama, Alamat — Pembeli (buyer)
- Table: Nama BKP/JKP, Harga Satuan, Jumlah Barang, Harga Total, DPP, PPN, PPnBM
- Totals: DPP, PPN, PPnBM, Total Tagihan
- Signature block (Nama, Jabatan, Tanggal)

Generate via Dompdf in `EFakturController::printFaktur()` — same pattern as `SalesService::printInvoice()`.

---

#### 1.6 Controller

**`app/Http/Controllers/Admin/EFakturController.php`**

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FakturPajak;
use App\Models\Sales;
use App\Services\EFakturService;
use Illuminate\Http\Request;
use Dompdf\Dompdf;

class EFakturController extends Controller
{
    public function __construct(protected EFakturService $efakturService) {}

    // List all Faktur Pajak for the tenant
    public function index(Request $request)
    {
        $masa  = $request->get('masa', now()->month);
        $tahun = $request->get('tahun', now()->year);

        $fakturs = FakturPajak::with(['sale.customer'])
            ->where('masa_pajak', $masa)
            ->where('tahun_pajak', $tahun)
            ->orderBy('nsfp')
            ->paginate(25);

        $summary = $this->efakturService->getSptMasaSummary($masa, $tahun);

        return view('admin.efaktur.index', compact('fakturs', 'summary', 'masa', 'tahun'));
    }

    // Generate Faktur Pajak from a sale
    public function generate(Sales $sale)
    {
        try {
            $faktur = $this->efakturService->generateFakturPajak($sale);
            return redirect()->route('admin.efaktur.show', $faktur)
                ->with('success', 'Faktur Pajak ' . $faktur->nsfp . ' berhasil dibuat.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // Show a single Faktur Pajak
    public function show(FakturPajak $fakturPajak)
    {
        $fakturPajak->load(['items', 'sale.customer', 'sale.salesItems.product']);
        return view('admin.efaktur.show', compact('fakturPajak'));
    }

    // Print Faktur Pajak PDF
    public function print(FakturPajak $fakturPajak)
    {
        $fakturPajak->load(['items', 'sale.customer', 'tenant']);
        $html = view('admin.efaktur.faktur-pdf', compact('fakturPajak'))->render();

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->stream('FP-' . str_replace('.', '', $fakturPajak->nsfp) . '.pdf', ['Attachment' => false]);
    }

    // Export CSV for DJP e-Faktur
    public function exportCsv(Request $request)
    {
        $masa  = $request->get('masa', now()->month);
        $tahun = $request->get('tahun', now()->year);
        return $this->efakturService->exportCsvForDjp($masa, $tahun);
    }

    // Settings page: NPWP, PKP status, NSFP import, Klikpajak API (Phase 2)
    public function settings()
    {
        $tenant = app('currentTenant');
        $nsfpCount = \App\Models\NsfpPool::where('status', 'available')->count();
        return view('admin.efaktur.settings', compact('tenant', 'nsfpCount'));
    }

    public function saveSettings(Request $request)
    {
        $validated = $request->validate([
            'npwp'      => 'nullable|string|max:20',
            'pkp_status'=> 'boolean',
            'pkp_date'  => 'nullable|date',
            'ppn_rate'  => 'numeric|min:0|max:100',
        ]);

        app('currentTenant')->update($validated);
        return back()->with('success', 'Pengaturan e-Faktur berhasil disimpan.');
    }

    public function importNsfp(Request $request)
    {
        $request->validate(['nsfp_list' => 'required|string']);
        $result = $this->efakturService->importNsfpPool($request->nsfp_list);

        return back()->with('success',
            "Import selesai: {$result['imported']} NSFP berhasil diimpor, {$result['skipped']} sudah ada."
            . (count($result['errors']) > 0 ? ' Errors: ' . implode(', ', $result['errors']) : '')
        );
    }
}
```

#### 1.7 Routes

Add to `routes/web.php` inside the existing admin route group with plan.feature middleware:

```php
// e-Faktur routes — Pro & Enterprise only
Route::prefix('efaktur')->name('admin.efaktur.')->middleware(['plan.feature:ppn'])->group(function () {
    Route::get('/',              [EFakturController::class, 'index'])->name('index');
    Route::post('/generate/{sale}', [EFakturController::class, 'generate'])->name('generate');
    Route::get('/{fakturPajak}', [EFakturController::class, 'show'])->name('show');
    Route::get('/{fakturPajak}/print', [EFakturController::class, 'print'])->name('print');
    Route::get('/export/csv',    [EFakturController::class, 'exportCsv'])->name('export.csv');
    Route::get('/settings',      [EFakturController::class, 'settings'])->name('settings');
    Route::post('/settings',     [EFakturController::class, 'saveSettings'])->name('settings.save');
    Route::post('/nsfp/import',  [EFakturController::class, 'importNsfp'])->name('nsfp.import');
});
```

---

### Phase 2 — PJAP API Direct Submission ✅ COMPLETED (2026-11-05)

> **Implementation status (shipped 2026-11-05).** The
> specification below was implemented as written in §2.1–§2.8, with seven
> deliberate deviations from the illustrative snippets (full rationale and
> the 35-test matrix in `docs/CHANGELOG.md`, entry *2026-11-05*):
>
> | # | Snippet says | Shipped | Why |
> |---|---|---|---|
> | 1 | `handle(PjapInterface $pjap)` method injection | `Tenant::find(...)->makeCurrent()` + `app(PjapInterface::class)` inside `handle()` | queue workers have no HTTP tenant context; the container resolves the per-tenant binding before `handle()` runs |
> | 2 | guard on `isSubmittable()` | `pjap_submission_id !== null \|\| status ∉ {draft, submitted}` | a `submitted` row **without** a submission id is a failed attempt being retried — `isSubmittable()` would swallow every retry (the `X-Idempotency-Key` makes the repeat safe) |
> | 3 | `checkStatus()` returns a normalized array | throws on API failure | a failed check must retry, never fabricate a verdict or stamp `djp_response_at` with junk |
> | 4 | `Authorization` + `X-Idempotency-Key` headers | HMAC-SHA256 request signing (`Date` + `Digest` + `Authorization: hmac username=...`) instead of `Bearer` + `X-API-Secret`; endpoints under `{base}/v1/efaktur/out...`; responses unwrapped from the `{code, message, data}` envelope | reconciled to Mekari's **live** Klikpajak Public API v1 docs on 2026-11-05: the `Bearer`/`X-API-Secret` model and `/v1/faktur/upload` endpoints assumed by the snippet did not exist (full note below) |
> | 5 | dispatch on the dedicated redis `tax` connection | `->onQueue('tax-submissions')` on the **default** connection; the `tax` connection ships as opt-in (`TAX_QUEUE_CONNECTION`) | existing workers and the sync test environment keep working; the §2.6 topology is one env var away |
> | 6 | register in `config/app.php` `providers` | `bootstrap/providers.php` | Laravel 11+ moved the provider list |
> | 7 | one settings form | the Klikpajak card posts on its own form; `saveSettings()` gates jurisdiction logic on `$request->has('tax_jurisdiction')` | the card sends no jurisdiction fields — without a key-presence gate every Klikpajak save would null the tenant's jurisdiction |
>
> **External dependency (by design):** Klikpajak is a **Mekari** product —
> production submissions require the tenant's *own* active Mekari Klikpajak
> subscription, billed separately from Invent-MAG (the settings card says
> so). CSV export (Phase 1) remains free on every plan, and `PjapInterface`
> keeps the provider swappable.
>
> **API contract verified against Mekari's live docs (2026-11-05):** the
> Klikpajak Public API v1 collection (Postman) and the developers.mekari.com
> HMAC spec were checked directly, and `KlikpajakService` was rewritten to
> match: HMAC request signing with the tenant's **Client ID / Client Secret**
> from a developers.mekari.com app registration (no Bearer token, no
> `X-API-Secret`; `Date` + `Digest` headers, 300s clock skew), base URLs
> `https://sandbox-api.mekari.com/v2/klikpajak` / `https://api.mekari.com/v2/klikpajak`,
> endpoints `POST/GET /v1/efaktur/out...`, `PUT .../cancel`,
> `GET /v1/subscription`, the `{code, message, data}` envelope ("00" ok,
> "02" bulk accepted, "03" bulk in progress, "04" approve/cancel ok, "1801"
> bulk finished with failures) and Mekari's `client_reference_id` / `customer`
> / `items` payload shape. One nuance to watch on the first live call: the
> canonical spec signs the request line **with** the query string
> (`pathWithQueryParam`) while the Postman demo script uses the bare path:
> if a real call returns 401, flip `headers()` in the service. What is still
> missing before the first production call: **real credentials** (app
> registration is manual via Mekari sales, secret shown once) + an active
> subscription. Sandbox is the default (`klikpajak_use_sandbox = true`).
>
> **Deployment prerequisites:** (1) `php artisan migrate` (adds
> `efaktur_api` to the Enterprise plan); (2) a queue worker listening on
> the **`tax-submissions`** queue (default connection — or set
> `TAX_QUEUE_CONNECTION=tax` and use the §2.6 supervisor block). Without a
> listener, submissions sit in the queue. Note: `submitBatch()` and
> `PjapInterface::cancel()` are no longer service-level only: the index
> now has a checkbox column with a "Submit Selected to DJP" button, and the
> show page has "Cancel at DJP". `cancel()` throws on transport failure so
> the queued cancel's retries, backoff and `failed()` are reachable;
> a business refusal from DJP still returns false.
>
> **Provider swap procedure (code-level, deliberate):** every call site
> knows only `PjapInterface`. To run a different PJAP: (1) add
> `App\Services\Pjap\<Provider>Service implements PjapInterface`;
> (2) return it from the binding closure in
> `app/Providers/EFakturServiceProvider.php` (the single swap point);
> (3) point the settings fields/labels (`klikpajak_*` columns, card copy)
> at the new provider. No job, controller, route, or test changes. A
> runtime UI provider selector is intentionally deferred until a second
> provider actually exists.

Phase 1 gives CSV export parity with Zahir Online. Phase 2 is the differentiator:
- One-click "Submit ke DJP" button on Faktur Pajak detail page
- Real-time approval/rejection status from DJP
- Bulk submission for month-end
- No manual CSV download, no DJP desktop app

#### 2.1 PJAP Selection — Why Mekari Klikpajak

| PJAP | API Quality | Sandbox | Notes |
|---|---|---|---|
| **Mekari Klikpajak** | Excellent | Full sandbox | Best docs, REST API, most widely used |
| Mitra Pajakku | Good | Available | Solid alternative |
| Soltax | Basic | Limited | Not recommended |

**Use Klikpajak.** Abstract behind a `PjapInterface` contract so swapping is possible without touching business logic.

#### 2.2 Contract Interface

**`app/Contracts/PjapInterface.php`**

```php
<?php

namespace App\Contracts;

use App\Models\FakturPajak;

interface PjapInterface
{
    /**
     * Submit a single FakturPajak. Returns PJAP's submission tracking ID.
     */
    public function submit(FakturPajak $faktur): string;

    /**
     * Submit multiple FakturPajak in one batch call.
     * Returns [faktur_id => pjap_submission_id].
     */
    public function submitBatch(array $fakturIds): array;

    /**
     * Check submission status. Returns normalized array:
     * ['status' => 'pending'|'approved'|'rejected', 'djp_reference' => ..., 'rejection_reason' => ...]
     */
    public function checkStatus(string $pjapSubmissionId): array;

    /**
     * Cancel/void a previously submitted Faktur (Faktur Batal).
     */
    public function cancel(string $pjapSubmissionId): bool;

    /**
     * Verify API credentials. Returns true if connection OK.
     */
    public function testConnection(): bool;
}
```

#### 2.3 Klikpajak Service Implementation

> **Live-docs reconciliation (2026-11-05):** the service and provider
> snippets below are the original illustrative spec. The shipped
> `KlikpajakService` was rewritten against Mekari's **live** Klikpajak
> Public API v1 docs: HMAC request signing with clientId/clientSecret
> (`Date`/`Digest`/`Authorization: hmac ...` headers, no Bearer or
> `X-API-Secret`), `{base}/v1/efaktur/out...` endpoints on mekari.com hosts,
> the `{code, message, data}` response envelope, and Mekari's
> `client_reference_id` / `customer` / `items` payload shape. Treat the
> snippets below as historical; the shipped file and the Phase 2 status
> block at the top of this section are authoritative.

**`app/Services/Pjap/KlikpajakService.php`**

```php
<?php

namespace App\Services\Pjap;

use App\Contracts\PjapInterface;
use App\Models\FakturPajak;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KlikpajakService implements PjapInterface
{
    private string $baseUrl;
    private string $apiKey;
    private string $apiSecret;
    private string $npwp;

    public function __construct(array $config)
    {
        $this->baseUrl   = $config['base_url'];
        $this->apiKey    = $config['api_key'];
        $this->apiSecret = $config['api_secret'];
        $this->npwp      = $config['npwp'];
    }

    public function submit(FakturPajak $faktur): string
    {
        $response = Http::withHeaders($this->headers())
            ->timeout(30)
            // Idempotency key prevents duplicate submissions even if the request is retried
            ->withHeaders(['X-Idempotency-Key' => 'FP-' . $faktur->id . '-' . $faktur->nsfp])
            ->retry(3, sleepMilliseconds: 1000, throw: false)
            ->post($this->baseUrl . '/v1/faktur/upload', $this->buildPayload($faktur));

        if ($response->failed()) {
            Log::error('Klikpajak submit failed', [
                'faktur_id' => $faktur->id,
                'status'    => $response->status(),
                'body'      => $response->body(),
            ]);
            throw new \RuntimeException('Klikpajak API error ' . $response->status() . ': ' . $response->body());
        }

        return $response->json('data.submission_id');
    }

    public function submitBatch(array $fakturIds): array
    {
        $fakturs  = FakturPajak::with('items')->whereIn('id', $fakturIds)->get();
        $payloads = $fakturs->map(fn($f) => $this->buildPayload($f))->values()->toArray();

        $response = Http::withHeaders($this->headers())
            ->timeout(120)
            ->post($this->baseUrl . '/v1/faktur/upload-batch', ['fakturs' => $payloads]);

        if ($response->failed()) {
            throw new \RuntimeException('Batch submission failed: ' . $response->body());
        }

        return collect($response->json('data'))
            ->keyBy('faktur_id')
            ->map(fn($item) => $item['submission_id'])
            ->toArray();
    }

    public function checkStatus(string $pjapSubmissionId): array
    {
        $response = Http::withHeaders($this->headers())
            ->get($this->baseUrl . '/v1/faktur/status/' . $pjapSubmissionId);

        return [
            'status'           => $this->normalizeStatus($response->json('data.status')),
            'djp_reference'    => $response->json('data.nomor_faktur_djp'),
            'rejection_reason' => $response->json('data.keterangan'),
            'raw'              => $response->json(),
        ];
    }

    public function cancel(string $pjapSubmissionId): bool
    {
        $response = Http::withHeaders($this->headers())
            ->delete($this->baseUrl . '/v1/faktur/' . $pjapSubmissionId);
        return $response->successful();
    }

    public function testConnection(): bool
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(10)
                ->get($this->baseUrl . '/v1/ping');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function buildPayload(FakturPajak $faktur): array
    {
        return [
            'kd_jenis_transaksi' => '01',
            'fg_pengganti'       => $faktur->is_pengganti ? '1' : '0',
            'nomor_faktur'       => $faktur->nsfp,
            'masa_pajak'         => $faktur->masa_pajak,
            'tahun_pajak'        => $faktur->tahun_pajak,
            'tanggal_faktur'     => $faktur->tanggal_faktur->format('Y-m-d'),
            'npwp_penjual'       => $faktur->npwp_penjual,
            'nama_penjual'       => $faktur->nama_penjual,
            'alamat_penjual'     => $faktur->alamat_penjual,
            'npwp_pembeli'       => $faktur->npwp_pembeli,
            'nama_pembeli'       => $faktur->nama_pembeli,
            'alamat_pembeli'     => $faktur->alamat_pembeli,
            'jumlah_dpp'         => $faktur->dpp,
            'jumlah_ppn'         => $faktur->ppn,
            'jumlah_ppnbm'       => $faktur->ppnbm,
            'detail'             => $faktur->items->map(fn($item) => [
                'kode_objek_pajak' => $item->kode_objek_pajak,
                'nama_barang'      => $item->nama_barang,
                'harga_satuan'     => $item->harga_satuan,
                'jumlah_barang'    => $item->jumlah_barang,
                'diskon'           => $item->diskon,
                'dpp'              => $item->dpp,
                'ppn'              => $item->ppn,
            ])->toArray(),
        ];
    }

    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];
    }

    private function normalizeStatus(string $raw): string
    {
        return match (strtoupper($raw)) {
            'APPROVED', 'DITERIMA', 'SUCCESS' => 'approved',
            'REJECTED', 'DITOLAK', 'FAILED'   => 'rejected',
            default                            => 'pending',
        };
    }
}
```

#### 2.4 Service Provider

**`app/Providers/EFakturServiceProvider.php`**

```php
<?php

namespace App\Providers;

use App\Contracts\PjapInterface;
use App\Services\Pjap\KlikpajakService;
use Illuminate\Support\ServiceProvider;

class EFakturServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind PjapInterface per-tenant: each tenant has their own Klikpajak credentials
        $this->app->bind(PjapInterface::class, function ($app) {
            $tenant = $app->make('currentTenant');

            $useSandbox = $tenant->klikpajak_use_sandbox ?? true;
            $baseUrl    = $useSandbox
                ? config('services.klikpajak.sandbox_url', 'https://api-sandbox.klikpajak.id')
                : config('services.klikpajak.production_url', 'https://api.klikpajak.id');

            return new KlikpajakService([
                'base_url'   => $baseUrl,
                'api_key'    => $tenant->klikpajak_api_key ?? '',
                'api_secret' => $tenant->klikpajak_secret ?? '',
                'npwp'       => $tenant->npwp ?? '',
            ]);
        });
    }
}
```

Register in `config/app.php` `providers` array:
```php
App\Providers\EFakturServiceProvider::class,
```

#### 2.5 Queue Jobs

**Why queues?** Klikpajak API calls can take 2–10 seconds per faktur. Synchronous calls would timeout on bulk submissions. Queues keep the UI responsive, allow retries, and isolate tax submissions from other jobs.

**`app/Jobs/SubmitFakturPajakJob.php`**

```php
<?php

namespace App\Jobs;

use App\Contracts\PjapInterface;
use App\Models\FakturPajak;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SubmitFakturPajakJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 300;   // 5 minutes between retries
    public int $timeout = 60;    // 60 second per job

    public function __construct(public readonly FakturPajak $faktur) {}

    /**
     * Prevent duplicate jobs for the same FakturPajak.
     * If a submission job is already queued/running for this faktur, skip.
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping('faktur-' . $this->faktur->id)];
    }

    public function handle(PjapInterface $pjap): void
    {
        if (! $this->faktur->isSubmittable()) {
            Log::warning('FakturPajak not submittable, skipping', ['faktur_id' => $this->faktur->id]);
            return;
        }

        $this->faktur->update([
            'status'           => 'submitted',
            'djp_submitted_at' => now(),
            'retry_count'      => $this->attempts(),
            'last_retry_at'    => now(),
        ]);

        try {
            $pjapSubmissionId = $pjap->submit($this->faktur);

            $this->faktur->update([
                'pjap_submission_id' => $pjapSubmissionId,
                'pjap_status'        => 'pending_djp',
            ]);

            // Start polling after 30 seconds (DJP processing time)
            PollFakturPajakStatusJob::dispatch($this->faktur)
                ->onQueue('tax-submissions')
                ->delay(now()->addSeconds(30));

        } catch (\Exception $e) {
            Log::error('FakturPajak submission failed', [
                'faktur_id' => $this->faktur->id,
                'attempt'   => $this->attempts(),
                'error'     => $e->getMessage(),
            ]);

            $this->faktur->update(['pjap_status' => 'error_attempt_' . $this->attempts()]);
            throw $e; // re-throw → Laravel will retry up to $tries times
        }
    }

    public function failed(\Throwable $exception): void
    {
        // All retries exhausted — revert to draft so user can retry manually
        $this->faktur->update([
            'status'      => 'draft',
            'pjap_status' => 'failed_permanently',
        ]);

        Log::critical('FakturPajak permanently failed', [
            'faktur_id' => $this->faktur->id,
            'error'     => $exception->getMessage(),
        ]);
    }
}
```

**`app/Jobs/PollFakturPajakStatusJob.php`**

```php
<?php

namespace App\Jobs;

use App\Contracts\PjapInterface;
use App\Models\FakturPajak;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PollFakturPajakStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 10;
    public int $backoff = 60;   // 1 minute between polls

    public function __construct(public readonly FakturPajak $faktur) {}

    public function handle(PjapInterface $pjap): void
    {
        if (! $this->faktur->pjap_submission_id) return;

        $result = $pjap->checkStatus($this->faktur->pjap_submission_id);

        $this->faktur->update([
            'pjap_status'          => $result['status'],
            'djp_response_at'      => now(),
            'djp_response_payload' => $result['raw'],
        ]);

        match ($result['status']) {
            'approved' => $this->faktur->update([
                'status'               => 'approved',
                'djp_reference_number' => $result['djp_reference'],
            ]),
            'rejected' => $this->faktur->update(['status' => 'rejected']),
            'pending'  => $this->reschedulePolling(),
        };
    }

    private function reschedulePolling(): void
    {
        if ($this->attempts() < $this->tries) {
            self::dispatch($this->faktur)
                ->onQueue('tax-submissions')
                ->delay(now()->addMinutes(5));
        }
        // After max polls, leave as 'submitted' with pjap_status 'pending' — user can check manually
    }
}
```

#### 2.6 Queue Configuration

Dedicated `tax-submissions` queue prevents DJP jobs from being starved by other background work.

In `config/queue.php`, add to `connections`:
```php
'tax' => [
    'driver'      => 'redis',
    'connection'  => 'default',
    'queue'       => 'tax-submissions',
    'retry_after' => 90,
    'block_for'   => null,
],
```

Dispatch jobs to the dedicated queue:
```php
SubmitFakturPajakJob::dispatch($faktur)->onQueue('tax-submissions');
```

Supervisor config (`/etc/supervisor/conf.d/invent-mag-tax.conf`):
```ini
[program:invent-mag-tax-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/artisan queue:work redis --queue=tax-submissions --sleep=3 --tries=3 --timeout=60 --max-jobs=500
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/invent-mag-tax.log
stopwaitsecs=60
```

#### 2.7 "Submit ke DJP" UI

Add to `resources/views/admin/efaktur/show.blade.php` — a button visible only if:
- Faktur is in `draft` status
- Tenant has `efaktur_api` plan feature
- Tenant has the Klikpajak Client ID configured (stored in the `klikpajak_api_key` column)

```blade
@planHas('efaktur_api')
    @if($fakturPajak->isSubmittable() && app('currentTenant')->klikpajak_api_key)
        <form method="POST" action="{{ route('admin.efaktur.submit', $fakturPajak) }}">
            @csrf
            <button type="submit" class="btn btn-success"
                onclick="return confirm('Kirim Faktur Pajak ini ke DJP via Klikpajak?')">
                Submit ke DJP
            </button>
        </form>
    @endif
@endPlanHas
```

Add the route and controller method:
```php
// In route group
Route::post('/{fakturPajak}/submit', [EFakturController::class, 'submitToDjp'])
    ->name('admin.efaktur.submit')
    ->middleware('plan.feature:efaktur_api');

// In EFakturController
public function submitToDjp(FakturPajak $fakturPajak)
{
    SubmitFakturPajakJob::dispatch($fakturPajak)->onQueue('tax-submissions');
    return back()->with('success', 'Faktur Pajak sedang diproses. Status akan diperbarui otomatis.');
}
```

---

#### 2.8 Idempotency & Safety — Five Layers

Tax double-submissions to DJP are a legal problem. Protection at 5 levels:

| Layer | Mechanism |
|---|---|
| Database | `UNIQUE` constraint on `nsfp_pool.nsfp` and `faktur_pajak.nsfp` |
| HTTP | `X-Idempotency-Key: FP-{id}-{nsfp}` header on all Klikpajak API calls |
| Queue | `WithoutOverlapping('faktur-{id}')` middleware prevents concurrent jobs |
| Application | `isSubmittable()` check at job start — rejects if already submitted |
| Database Transaction | `DB::transaction()` + `lockForUpdate()` on NSFP allocation |

**As implemented (2026-11-05):** the application-layer guard is the wider
`pjap_submission_id !== null || status ∉ {draft, submitted}` (deviation 2
in the status block above) — a `submitted` row without a submission id is a
failed retry and must fall through, not be rejected.

---

#### 2.9 Deploy Runbook: Phase 2 on a live tenant

Everything a production host needs before the first **Submit ke DJP** button
can be pressed. It is env, four data migrations, one queue worker, an
assets build, a verification checklist and a rollback note: no application
code changes. Run it in order; each step names the failure it prevents.

**1. Environment: no new required keys**

| Key | Default | Purpose |
|---|---|---|
| `KLIKPAJAK_SANDBOX_URL` | `https://sandbox-api.mekari.com/v2/klikpajak` | Mekari sandbox host |
| `KLIKPAJAK_PRODUCTION_URL` | `https://api.mekari.com/v2/klikpajak` | Mekari live host |

Both have working defaults in `config/services.php`, so a host that already
runs Phase 1 needs no new env at all:

```bash
php artisan config:clear && php artisan cache:clear
```

**Tenant credentials are not env**: they are ordinary tenant columns
(shipped with Phase 1 in `2026_07_17_000004`), set per tenant from
**e-Faktur → Settings**:

| Column | Holds |
|---|---|
| `klikpajak_api_key` | Mekari **Client ID** (the HMAC username) |
| `klikpajak_secret` | Mekari **Client Secret** (the signing key) |
| `klikpajak_use_sandbox` | `true` by default: a tenant that has not flipped it always talks to sandbox |

**2. Migration: four, all data-only**

```bash
php artisan migrate --force
php artisan migrate:status | grep efaktur_api    # must show Ran
php artisan migrate:status | grep invoice_email  # must show Ran
php artisan migrate:status | grep reverse_journal_entries  # must show Ran
php artisan migrate:status | grep restore_stock  # must show Ran
```

`2026_11_04_000001_add_efaktur_api_feature_to_plans.php` appends
`efaktur_api` to the **enterprise** plan's `features` array. There is **no
schema change**, and the `in_array()` guard makes a re-run a no-op, so it is
safe on live. Verify the result:

```sql
SELECT features FROM plans WHERE slug = 'enterprise';   -- must contain efaktur_api
```

This exact step was **missed on live on 2026-11-05** and had to be run out
of band: the button renders for nobody until it lands.

`2026_11_05_000002_add_invoice_email_feature_to_plans.php` is the second,
and it ships with the release that added email delivery (§5.4). It appends
`invoice_email` to **every** plan's `features` array, which is what makes the
"available on all tiers" claim true. Same `in_array()` no-op guard, no schema
change, safe on live. Nothing gates on the slug, so a missed run costs only
the plan pages not listing the row:

```sql
SELECT slug, features FROM plans;   -- every row must contain invoice_email
```

`2026_11_08_000001_reverse_journal_entries_of_canceled_returns.php` is the
third, and it corrects data the cancel bug had already written. Before the
2026-11-08 fix, `cancelSalesReturn()`/`cancelPurchaseReturn()` only reverted
the GL when the return was `Completed`, so every return canceled from
`Pending` kept the journal entry its creation had posted: 41 rows on the
reference database (20 sales, 21 purchase; AR/AP over-reversed by Rp 35,860
and Rp 68,930). The migration writes one balanced reversal per live entry
(exactly what `reverseJournalEntry()` would write), dated the day it runs,
selected by the live-entry test the cancel paths use (no reversal points
at the entry), so a re-run finds nothing to do, and
it writes nothing at all on a database where no canceled return holds a live
entry. Verify:

```sql
-- must return 0 rows (swap in App\Models\PurchaseReturn + purchase_returns
-- for the purchase half; both halves were 41 rows before the migration):
SELECT je.id FROM journal_entries je
JOIN sales_returns sr
  ON je.sourceable_type = 'App\\Models\\SalesReturn' AND je.sourceable_id = sr.id
WHERE sr.status = 'Canceled' AND je.status = 'posted'
  AND NOT EXISTS (SELECT 1 FROM journal_entries r WHERE r.reversed_entry_id = je.id);
```

`2026_11_08_000002_restore_stock_of_canceled_returns.php` is the fourth,
the stock half of the same bug: the 41 canceled returns also kept the stock
their creation had moved (52 units too many on sales, 111 too few on
purchase; product 28 / warehouse 17 was left at -4). It corrects only the
returns the GL half marked (the marker lives in the reversal's notes), so a
return the old gate already reverted from Completed is never corrected
twice; on this database that is the same 41 rows. It mirrors both cancel
paths per item row and runs the `po_items.remaining_quantity` walks with
their original filters and caps. Verify:

```sql
-- must return 0:
SELECT COUNT(*) FROM product_warehouse WHERE quantity < 0;
```

Run both corrections inside the deploy window: no cancel should land
between them and the fixed code (together they correct rows the bug
already wrote, and neither re-runs).

**3. The queue worker: the step that is easy to forget**

All three dispatch sites (`EFakturController@submitToDjp`,
`SubmitFakturPajakJob`, `PollFakturPajakStatusJob`) use
`->onQueue('tax-submissions')` on the **default** connection:

- **Default setup (no env change):** jobs land on `default`, so whatever
  worker already runs `queue:work` picks them up. Confirm one is alive:
  `ps aux | grep "[q]ueue:work"`.
- **Dedicated worker (§2.6):** set `TAX_QUEUE_CONNECTION=tax` and install
  the supervisor block from §2.6 verbatim, then
  `supervisorctl reread && supervisorctl update`.

**Failure mode if this step is skipped:** nothing errors; submissions
simply sit in the `jobs` table and the UI stays on `submitted`-pending
forever. That is the whole reason §2.6 exists.

**4. Assets: only if this release changed JS/CSS**

```bash
npm run build && php artisan optimize
```

**5. Verification checklist, in order**

1. `php artisan migrate:status | grep efaktur_api` → **Ran**.
2. Enterprise `features` contains `efaktur_api`.
3. `php artisan migrate:status | grep reverse_journal_entries` → **Ran**,
   and the canceled-return leak query above returns **0 rows**; likewise
   `grep restore_stock` → **Ran**, with `SELECT COUNT(*) FROM
   product_warehouse WHERE quantity < 0` → **0**.
4. A worker is alive for the queue the jobs actually land on.
5. Tenant plan is **enterprise**: the button is wrapped in
   `@planHas('efaktur_api')`, and the whole e-Faktur area sits behind
   `plan.feature:ppn`, so both gates must pass.
6. Tenant has Client ID + Secret set, and `klikpajak_use_sandbox = 1`.
7. Generate a Faktur → it is `draft` → **Submit ke DJP** is visible.
8. First submission goes to **sandbox**. Watch `storage/logs/laravel.log`
   for the HMAC signature headers and the `{code, message, data}` envelope;
   a 401 there is a Client ID/Secret mismatch, not a code fault.
9. Only after a clean sandbox round-trip, flip `klikpajak_use_sandbox` off
   for that tenant: Mekari production requires an active subscription.

**6. Rollback**

- Migration `down()` removes `efaktur_api` from the plan, which hides the
  button for every tenant at once: the effective kill switch.
- The correction migrations' `down()`s delete only what they wrote: the GL
  one removes the reversal entries carrying its notes marker (their
  transactions first), so the originals go live again; the stock one puts
  every stock row back with the opposite delta and runs the PO walks in
  reverse, exact while nothing else moved in between.
- Worker and env changes are additive; removing a worker only queues jobs,
  it loses no data.
- No schema to revert; credentials are UI-set columns.

**Standing open item:** the **first live Mekari call has never been made**.
It needs a Client ID/Secret registered at developers.mekari.com and an
active Klikpajak subscription. Everything above is verified against the
contract and `KlikpajakServiceTest`, not against Mekari's servers: one
known ambiguity is recorded in §2.2 (the canonical spec signs the request
line with the query string; Mekari's own Postman demo signs the bare path).

---

## 5. Other Planned Features

### 5.1 Bank Reconciliation ✅ DONE

**Why:** Essential for the accounting module. Without reconciliation, GL balance and bank statement can drift. Required for accurate financial statements.

**What:**
- Import bank statements CSV (BCA, Mandiri, BNI, BRI — each has its own CSV format)
- Auto-match transactions to existing Journal Entries by amount + date proximity
- Manual match UI for unmatched items
- Reconciliation lock (prevent editing reconciled journal entries)
- Reconciliation report per period

**Implementation (CSV-based, no bank API — decision):**
- `BankStatementParser` interface + `AbstractBankStatementParser` (delimiter sniffing, Indonesian/US number normalization, date parsing) with per-bank subclasses (BCA, Mandiri, BNI, BRI) and a `BankStatementParserFactory`
- `BankReconciliationService` — import, auto-match (±5 day window, exact amount), manual match, unmatch, complete, delete, totals recalc, journal-entry candidate listing, and `isJournalEntryReconciled()` lock check
- Models: `BankReconciliation` (open/completed) + `BankReconciliationItem` (unmatched/matched/manually_matched) with computed closing balance & variance
- Reconciliation lock wired into `AccountingService::reverseJournalEntry / voidJournalEntry / unpostJournalEntry` via `assertNotReconciled()`
- Controller + routes under `admin/bank-reconciliation.*`; plan feature `bank_reconciliation` gated to Professional+; nav item under Reports
- EN/ID translations; full feature tests (`BankReconciliationControllerTest`) + parser unit tests (`BankStatementParserTest`)

**Post-completion hardening (Aug 2026):**
- **Variance gate** — `complete()` throws `DomainException` when `abs(variance) > 0.005`; Complete button disabled out-of-balance with tooltip; error surfaced via flash
- **Reconciled visibility** — `JournalEntry::withReconciledStatus()` scope (eager-loads count of items in completed reconciliations, no N+1) + `isReconciled()`; "Terekonsiliasi" badges in Journal, Ledger, manual-entry lists/details
- **Opening-balance prefill** — `getCreateData()` returns per-account opening balances; create form prefills the statement opening balance from the account Opening Balance (swap on account change, hint text)
- **Tutorial clarity** — new Opening Balance vs Bank Reconciliation steps in `config/tutorial.php` + EN/ID `lang/tutorial.php`; page-specific Import Statement context (`admin.bank-reconciliation.create`) explaining why the statement must be imported first; "Statement Start vs System Opening Balance" step on the recon page. Tax Jurisdiction vs Tax Configuration steps added on the System Settings and Tax pages (with auto-seed of PPN 11% + PPh 23 2% + PPh Final 0.5% on switching to Indonesia).

**Complexity:** Medium (~3 weeks). Bank CSV parsers are the tricky part — each bank uses a different format and column layout. ✅ Completed. CSV-based scope replaces the bank-API roadmap below.

---

### 5.2 Advanced Multi-Currency (Real-Time Rates)

**Why:** Current multi-currency is static configuration (per-tenant currency + conversion rates manually set). Real-time rates would let invoices, P&L, and aging reports reflect daily FX.

**What:**
- Live FX rate provider (e.g., open.er-api.com / Bank Indonesia reference rates)
- Daily automatic rate sync + manual override
- Revaluation entries (Dr/Cr FX gain-loss) for open AR/AP at period end
- Multi-currency reporting (report in base currency with rate at transaction date)

**Complexity:** Medium (~2-3 weeks). Requires a rates provider, scheduled sync job, and revaluation journal entry pattern through `AccountingService`.

---

### 5.3 Cash Flow Statement ✅ DONE

**Why:** The third core financial statement. P&L and Balance Sheet already exist. Cash Flow Statement completes the set — required by Indonesian accounting standards (PSAK).

**Method:** Indirect method (Net Income → adjust for non-cash items + working capital changes). Uses existing CoA classification — no `cash_flow_category` field needed; the system derives operating/investing/financing from account type and natural balance.

**Implementation:**
- `CashFlowService` — generates cash flow statement data (operating, investing, financing sections)
- `ReportController::cashFlow()` + `exportCashFlow()` — report view and PDF/CSV export
- `cash-flow.blade.php` + `cash-flow-export-pdf.blade.php` — report views
- Plan feature tiering: `cash_flow` slug, gated behind Professional+ plans
- Navigation menu item under Reports section
- Full feature tests (`CashFlowServiceTest`)
- EN/ID language translations
- Route-level middleware protection (`plan.feature:cash_flow`)

**Complexity:** Medium (~2 weeks). ✅ Completed.

---

### 5.4 Invoice Delivery by Email ✅ Shipped (2026-11-06)

**Decision (2026-11-06): shipped as email, not WhatsApp.** WhatsApp Business Cloud API (Meta) and Fonnte were the candidates; both bill per message, and that cost buys nothing over the SMTP the platform already sends subscription invoices with. Email is free per send, available on every plan, and the customer's address already sits on the `customers` record.

**Flow (as built):** "Send Invoice Email" button on the invoice view (behind `@can('edit-sales')`) → `SalesController::sendInvoice()` → `SalesInvoiceMail` (markdown body with the itemised table, invoice PDF attached) → the existing SMTP mailer. The send is synchronous, so a transport failure comes back as a flash error instead of dying in a queued job; a customer with no email on file gets a clear error and nothing is sent.

**Gating:** `invoice_email` feature slug, declared on all three plans, no route gate.

**Deploy note:** live `plans.features` gains the slug only when
`2026_11_05_000002_add_invoice_email_feature_to_plans.php` runs (§2.9 step 2,
`php artisan migrate --force`). Until then the "all tiers" claim is true of
the code and false of the database; nothing gates on the slug, so the only
visible effect is the plan pages not listing it.

**PDF:** the attachment and the Print button both come from `SalesService::renderInvoicePdf()`, so the emailed document is byte-identical to the printed one.

**Not included:** a payment link inside the email (see §5.5).

**Complexity:** Low. ✅ Completed.

---

### 5.5 Midtrans Payment Links on Invoices ❌ Declined (2026-11-06)

**Decision (2026-11-06): built, then reverted.** Tenants settle customer invoices **by bank transfer directly, or in cash when the salesperson visits the office**, so a hosted Midtrans checkout on the customer invoice would not be used ("having this, I don't think they will use it at all"). It would also mean a Midtrans credential set per tenant: the `.env` keys hold the platform's own subscription billing money, not the tenant's revenue.

**What was removed before commit:** `PaymentLinkService`, the per-tenant key settings page, the pay-link button, the webhook invoice branch (`sales.status = 'Paid'` plus Dr Cash/Cr AR), 21 tests and the `payment_links` slug. Nothing of it ships.

**What stays:** the subscription flow (`Api/PaymentController.php`, `Api/Webhook/MidtransController.php`) is untouched; that is the platform's own billing.

**Complexity:** n/a.

---

### 5.6 SPT Report Templates: ❌ Declined (2026-11-05)

**Decision (2026-11-05): dropped deliberately.** Invent-MAG is not, and does not try to be, a full accounting/tax suite ("we can't be all in one"). Monthly SPT Masa PPN *filing* stays where it already works: DJP's own tools (and Klikpajak, for Phase 2 tenants). The preparation side below already shipped, which is what Indonesian SMBs actually use this system for.

**Current state (Aug 2026):** PPN Keluaran (output VAT) summary exists via `EFakturService::getSptMasaSummary()` (masa/tahun, faktur counts, DPP, PPN). PPN Masukan (input VAT) tracking on purchases shipped (Phase 1D) — `po` carries `ppn_enabled/rate/amount` snapshots, the purchase journal debits Input VAT (1140→1160), and the SPT summary now returns `total_ppn_masukan`, `net_ppn_payable` and `ppn_credit` (Kurang/Lebih Bayar).

**VAT accounting note (Phase 1E, Aug 2026):** PPN Keluaran is now recognized at **sale creation** — `SalesService::createSale()` and `PosService` post a VAT-split journal (`Dr AR gross / Cr Revenue net / Cr PPN Keluaran`) for Indonesian PPN sales, so revenue is booked net of VAT and the Faktur Pajak carries no journal (compliance-only). Sales returns reverse PPN whenever the original sale `isPpn()` (no Faktur Pajak required).

**What shipped and stays:**
- PPN Keluaran (output VAT) summary from Faktur Pajak — ✅ done
- PPN Masukan (input VAT) summary from purchase data — ✅ done (prerequisite shipped Aug 2026: `po.ppn_enabled` snapshot + PPN Masukan 1160 journal)
- PPN Kurang Bayar / Lebih Bayar (net payable/credit) — ✅ done (summary cards on e-Faktur index)
- DJP-compatible export: ✅ done as Phase 1's CSV (the format the desktop/Coretax tools import)

**What is declined:** the pre-filled SPT Masa PPN *form* template and PDF form generation. Revisit only if real tenants ask for it after filing manually for a while.

---

### 5.7 Bank Integrations (BCA, BNI, Mandiri) — Optional future enhancement

**Why:** Direct bank API integration enables real-time balance checking and automated transaction matching. Bank Reconciliation (5.1) already ships as CSV-based; this is a possible Phase-2 automation layer on top of it.

**What:**
- BCA, BNI, Mandiri API connections
- Automated bank statement import (replaces manual CSV upload)
- Real-time balance verification

**Complexity:** High (~2-3 weeks per bank). Each bank has different API requirements and authentication flows. Not currently planned — CSV import covers the UMKM workflow at zero per-bank integration cost.

---

### 5.8 PPh Support (21, 23, 25, Final 0.5%)

**Why:** PPh (Pajak Penghasilan / Income Tax) withholding is required alongside PPN for comprehensive Indonesian tax compliance.

**Approach (July 2026):** All PPh types run on the **universal tax system** — a Tax record flagged `is_pph = true` automatically appears in the PPh dropdown on Sales, PO, POS, and Quotation forms and posts through the existing shared PPh journal path (sale side: `Dr PPh Receivable 1170 / Cr Accounts Receivable`; purchase side: `Cr PPh Payable 2150` alongside Dr Inventory / Cr AP). No per-type engines are planned; for the UMKM target they would add complexity without accounting accuracy (every PPh type posts to the same two accounts — receivable on sales, payable on purchases).

**What:**
- ~~PPh 23 (service fee withholding)~~ — ✅ **Completed (July 2026).** Seeded Tax record `PPH 23` (2%, `is_pph = true`). Snapshot fields (`pph_enabled`/`pph_rate`/`pph_amount`) on Sales/PO/POS/Quotation, journal posting, and the sales-view PPh line all live.
- PPh 21 (employee income tax) — 🔜 **Future (low effort).** Correct UMKM scope is a flat **"Payroll Tax" picker** on the payroll screen (withholding line in the payment journal), **not** a full TER/PTKP/1721-II engine. Reuses the `is_pph` mechanism. Depends on HR & Payroll feature (`hr` slug).
- ~~PPh Final 0.5% (UMKM final income tax)~~ — ✅ **Completed (August 2026).** Seeded Tax record `PPh Final 0.5%` (0.5%, `is_pph = true`) added to `TaxSeeder` + `TaxService::ensureIndonesianDefaults()` (idempotent per-record) with backfill migration `2026_08_05_000002` converting existing `PPH 22` rows in place — it flows through every existing PPh dropdown and journal path unchanged. No new models or journal code. `PPH 22` (1.5%) remains creatable on the Tax page but is no longer seeded.
- PPh 25 (monthly installments) — ⏸️ **Deferred.** A manual journal entry covers the monthly installment for UMKM; a dedicated calculation engine is out of scope.

**Complexity:** Low. PPh 23 and PPh Final 0.5% are done; PPh 21 is a small payroll picker. The heavy "calculation engines" once planned for 21/22/25 are intentionally **not** built.

---

### 5.9 Mobile Application

**Why:** Field sales teams and warehouse staff need mobile access for inventory checks, quick sales, and pipeline updates.

**What:**
- React Native or Flutter cross-platform app
- Core features: POS, inventory lookup, sales pipeline, customer management
- Offline support for areas with poor connectivity

**Complexity:** High (~3+ months). Separate codebase, API-first design required.

---

### 5.10 Advanced BI Dashboards

**Why:** Current reporting covers P&L, Balance Sheet, Cash Flow, and Trial Balance. Advanced BI would provide deeper insights — trend analysis, forecasting visualizations, and cross-period comparisons.

**What:**
- Interactive charts and graphs (revenue trends, expense breakdowns, customer segments)
- Cross-period comparison (month-over-month, year-over-year)
- Drill-down from summary to transaction detail
- Export to PDF/presentation format

**Complexity:** Medium (~2 weeks). Depends on charting library choice and data aggregation layer.

---

### 5.11 Shopee XLSX Import ✅ DONE (Oct 2026)

**What shipped:** Enterprise-gated "Import Shopee" modal (`shopee_import` feature slug, `import-products` permission) that bulk-updates the catalog from Shopee Seller Centre mass-update XLSX exports (Basic required; Sales required only when creating products; Media/Shipping optional).

- Rows match `products.code` = *SKU Induk*; multi-variation families split into one sellable product per variation (`products.parent_id` + "Variant of" badge) with per-variation price/stock
- **Stock posts to the MAIN warehouse only** (absolute quantity; other warehouses untouched; skipped when no main warehouse exists)
- Cover images download via queued `DownloadShopeeCoverImages` jobs (unchanged covers skipped, failed batches retried) with a live progress bar in the modal
- **Auto-categorized**: `ShopeeCategoryResolver` files each product into the 35-category SKU taxonomy (tier 1 SKU prefix/alias → tier 2 EN/ID name keywords → tier 3 Shopee leaf → tier 4 MISC) from `config/shopee_categories.php`; legacy "Shopee Import" rows re-filed via `php artisan categories:backfill-shopee` (`--dry-run` / `--tenant=`), which the deploy workflow runs after migrations
- Imported products get `low_stock_threshold = 10` so they don't need a manual threshold
- Shipped with its own guide in the tutorial (`shopee_import` step)

**Still deferred:** listing sync (create/update Shopee listings from Invent-MAG) and TikTok integration — the current feature is catalog *import* only.

**Planned (not built):** a downstream **accounting / Financial Health integration** for Shopee. Once listing sync exists, Shopee orders and payouts can post into the GL (revenue, COGS, marketplace fees, settlements) so marketplace sales feed the same ledger the Financial Health page reads — turning Shopee from a catalog source into a revenue channel the dashboards already measure. Deliberately deferred until listing sync ships, since it depends on order/payout data the import-only feature does not yet pull.

---

## 6. Competitive Positioning

### Invent-MAG vs. Zahir Online

| Feature | Invent-MAG Starter | Invent-MAG Pro | Zahir Small Business |
|---|:---:|:---:|:---:|
| Price | $19/mo (Rp 313k) | $59/mo (Rp 973k) | Rp 150k/mo (annual only) |
| Users | 3 | 10 | 1 |
| Billing Flexibility | Monthly ✅ | Monthly ✅ | Annual only ❌ |
| Accounting (double-entry) | ❌ | ✅ | ✅ |
| CRM / Pipeline | ❌ | ✅ | ❌ |
| Sales & purchase returns | ✅ | ✅ | Limited |
| POS | ✅ | ✅ | ❌ |
| Stock Opname | ❌ | ✅ | Limited |
| Multi-currency | ❌ | ✅ | ✅ (paid add-on) |
| AI Sales Forecasting | ❌ | ✅ Enterprise | ❌ |
| e-Faktur CSV + PDF | ✅ Completed | ✅ Completed | ✅ (CSV only) |
| e-Faktur Direct API (Klikpajak) | ❌ | ✅ Enterprise | ❌ |
| REST API | ❌ | ✅ Enterprise | ❌ |
| Free Trial | 30 days | 30 days | 30 days |

**Invent-MAG advantages:**
1. Monthly billing (no 12-month lock-in)
2. 3 users at Starter vs Zahir's 1 user
3. CRM & sales pipeline (unique in this price range)
4. AI Sales Forecasting — Holt-Winters (unique in Indonesian ERP market)
5. RESTful API (Enterprise)
6. One-click DJP submission (Phase 2, shipped Nov 2026) vs Zahir's manual CSV

**Gaps to address:**
1. ~~Only 7-day trial vs Zahir's 30-day~~ ✅ **30-day trial implemented (July 2026)**
2. ~~e-Faktur Phase 2 not yet live~~ ✅ **Phase 2 shipped Nov 2026** — one-click DJP submission via Mekari Klikpajak (Enterprise; requires the tenant's separate Klikpajak subscription), superior to Zahir's manual CSV
3. Zahir has been in market since 1999 (brand trust gap — takes time to close)

---

## 7. Architecture Decisions

### Why the existing `TaxService` is NOT modified for e-Faktur

`TaxService` (`app/Services/TaxService.php`) manages the generic sales tax (`taxes` table: name, rate, is_active). It's a simple, intentional tool for applying a blanket tax rate to sales orders.

e-Faktur / PPN is a completely separate legal obligation with its own data model, workflow, and regulatory requirements. Mixing them would violate Single Responsibility and make the generic tax logic fragile.

**Decision:** New `EFakturService` class. Both services coexist independently.

### Why `AccountingService` must be used for journal entries

`AccountingService::createJournalEntry()` is the single point of accounting truth. It:
- Validates balanced debits/credits
- Handles `sourceable` polymorphic linking
- Integrates with `AccountingAuditService`
- Is used consistently across all existing services (Sales, Purchase, etc.)

Creating journal entries outside `AccountingService` would bypass audit logging and break the balanced-books invariant.

### Why `BelongsToTenant` on all new models

The `BelongsToTenant` trait auto-injects `tenant_id` on model creation and applies a global Eloquent scope for all queries. Without it, tenant data isolation relies on manual `WHERE tenant_id = ?` clauses — which will eventually be missed in some query, causing a data leak between tenants.

Every model in this codebase uses `BelongsToTenant`. New e-Faktur models are no exception.

### Why NSFP pool with `lockForUpdate()`

DJP's NSFP (Nomor Seri Faktur Pajak) numbers are:
1. Pre-allocated by DJP in blocks
2. Must be used sequentially (cannot skip, cannot reuse)
3. Cannot be duplicated

Under concurrent requests (two users generating Faktur Pajak simultaneously), without `lockForUpdate()`, both requests could read the same "lowest available" NSFP and generate two Faktur with the same number — a serious legal violation.

`lockForUpdate()` inside `DB::transaction()` ensures only one request can hold the row at a time.

### Why a dedicated `tax-submissions` queue

If DJP/Klikpajak API is slow or down, DJP submission jobs back up. On a shared queue, this starves all other background work (notifications, reports, etc.). A dedicated queue allows:
- Independent scaling (2 workers just for tax)
- Different retry/timeout settings
- Monitoring of tax-specific queue health separately

### Financial Calculation Precision

Monetary values stored as `DECIMAL(15,2)` in the database — not `FLOAT` (which has IEEE 754 representation errors). For intermediate calculations:

```php
// BAD — float accumulates errors
$ppn = $subtotal * 0.11;

// GOOD — round at each step
$ppn = round($subtotal * ($ppnRate / 100), 2);

// OR use bcmath for highest precision
$ppn = (float) bcdiv(bcmul((string)$subtotal, (string)$ppnRate, 4), '100', 2);
```

DJP CSV export converts to integer cents: `(int) round($amount * 100)` — this matches the DJP spec exactly.

### Idempotency in Tax Operations

Every operation that touches DJP must be safe to run twice:

| Operation | Idempotency Mechanism |
|---|---|
| Generate Faktur Pajak | Check `fakturPajak()->exists()` on Sale before generating |
| Allocate NSFP | `lockForUpdate()` + unique DB constraint on `nsfp` column |
| Submit to Klikpajak | `X-Idempotency-Key` HTTP header (Klikpajak deduplicates on their end) |
| Queue job dispatch | `WithoutOverlapping` middleware (no two jobs for same faktur_id) |

---

*This roadmap is a living document. Update it as features are completed and regulatory requirements change (e.g., PPN rate changes from DJP).*
