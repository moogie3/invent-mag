# Invent-MAG — Product Roadmap

> **Last updated:** May 15, 2026  
> **Version:** 1.7  
> **Stack:** Laravel 11 · Blade/Alpine.js · MySQL · Redis · Dompdf · Spatie Multitenancy · Spatie Permission  
> **Deployment:** Docker · DigitalOcean (backend) · Vercel (frontend/marketing)  
> **Domain:** https://invent-mag.web.id

---

## Table of Contents

1. [Codebase Architecture Reference](#1-codebase-architecture-reference)
2. [Current Feature State & Plan Tiers](#2-current-feature-state--plan-tiers)
3. [Immediate Frontend / Marketing Fixes](#3-immediate-frontend--marketing-fixes)
4. [e-Faktur & PPN Compliance — Full Robust Implementation](#4-e-faktur--ppn-compliance--full-robust-implementation)
   - [Phase 1 — PPN Foundation & Faktur Pajak](#phase-1--ppn-foundation--faktur-pajak-estimated-3-4-weeks)
   - [Phase 2 — PJAP API Direct Submission](#phase-2--pjap-api-direct-submission-estimated-3-4-weeks)
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
- Feature slugs currently in use: `accounting`, `pos`, `crm`, `forecasting`, `api`, `audit_log`, `stock_opname`
- **New e-Faktur feature slugs to add**: `ppn`, `efaktur_api` (Phase 2)
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
| `is_pos` | boolean | POS vs regular sale |
| `customer_credit_used` | decimal | Credit applied |
| `sales_opportunity_id` | FK | CRM linkage |

Line items are in `sales_items` table via `SalesItem` model. Fields: `sales_id`, `product_id`, `name`, `quantity`, `customer_price`, `discount`, `discount_type`, `total`.

### 1.4 Accounting Layer

- `accounts` table: CoA. Fields: `tenant_id`, `parent_id`, `name`, `code`, `type` (asset/liability/equity/revenue/expense), `is_active`, `is_contra`, `normal_balance`, `tax_type` (PPN_IN/PPN_OUT/PPH — field exists but not yet used)
- `journal_entries` table: `tenant_id`, `date`, `description`, `sourceable_id`, `sourceable_type` (polymorphic), `status` (draft/posted/void), `entry_type` (standard/opening/reversal/adjusting/closing)
- `transactions` table: `tenant_id`, `journal_entry_id`, `account_id`, `type` (debit/credit), `amount`
- **Accounting settings stored per User** (not per Tenant): `users.accounting_settings` JSON column. Contains: `sales_revenue_account_id`, `accounts_receivable_account_id`, `cost_of_goods_sold_account_id`, `inventory_account_id`, `accounts_payable_account_id`, `cash_account_id`
- **`AccountingService`** (`app/Services/AccountingService.php`) is the single entry point for all journal entry creation:

```php
$this->accountingService->createJournalEntry(
    string $description,
    Carbon $date,
    array $transactions,    // [['account_name' => '...', 'type' => 'debit'/'credit', 'amount' => 0.00]]
    ?Model $sourceDocument  // polymorphic source (Sales, Payment, FakturPajak, etc.)
);
```

All service classes that need accounting (SalesService, PurchaseService, etc.) inject and use `AccountingService`. **Do not create journal entries directly** — always go through `AccountingService::createJournalEntry()`.

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

### Starter — $19/month (Rp 313,500/bulan)

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
| **Sales forecasting (AI)** | ❌ Not included | Feature slug: `forecasting` |
| **RESTful API** | ❌ Not included | Feature slug: `api` |

> **Note:** Accounting is scoped to Pro & Enterprise. The Starter plan is intentionally positioned as an inventory + order management tool.

---

### Professional — $59/month (Rp 973,500/bulan)

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

### Enterprise — $109/month (Rp 1,798,500/bulan)

| Feature | Status | Notes |
|---|---|---|
| Everything in Professional | ✅ Live | |
| Unlimited users | ✅ Live | `plans.max_users = -1` |
| Unlimited products | ✅ Live | `plans.max_products = -1` |
| Sales forecasting — Holt-Winters | ✅ Live | Feature slug: `forecasting` |
| RESTful API (Scribe-documented) | ✅ Live | Feature slug: `api` |
| Audit trail & compliance logging | ✅ Live | Feature slug: `audit_log` |
| Custom roles & 79 granular permissions | ✅ Live | Spatie Permission |
| **HR & Payroll** | ✅ Live | Feature slug: `hr` |
| Dedicated support & SLA | ✅ Live | |

---

## 3. Immediate Frontend / Marketing Fixes

### 3.1 IDR Pricing Display

**Status:** ✅ Already implemented on `/id/pricing` (Rp 313.500 / Rp 973.500 / Rp 1.798.500)

**Rate:** 1 USD = 16,500 IDR (rounded to nearest 500). Review quarterly.

---

### 3.2 Pricing Comparison Table vs. Zahir Online

**Status:** Planned — add below pricing cards in `invent-mag-fe/src/components/Pricing.astro`

| Feature | Invent-MAG Starter | Invent-MAG Pro | Zahir Small Business |
|---|:---:|:---:|:---:|
| Price | $19/mo | $59/mo | Rp 150k/mo (annual only) |
| Users | 3 | 10 | 1 |
| Billing | Monthly | Monthly | **Annual only** |
| Accounting | ❌ | ✅ | ✅ |
| Sales & purchase returns | ✅ | ✅ | ✅ |
| CRM / Pipeline | ❌ | ✅ | ❌ |
| POS | ✅ | ✅ | ❌ |
| AI Forecasting | ❌ | ❌ | ❌ |
| e-Faktur (DJP) | 🔜 | 🔜 | CSV only |
| REST API | ❌ | ❌ | ❌ |
| Free Trial | 7 days | 7 days | 30 days |

---

### 3.3 Extend Free Trial to 30 Days (Optional)

**Rationale:** Zahir offers 30 days. Accounting software takes longer to evaluate than simpler SaaS — users need time to set up CoA, post journal entries, and run reports.

**Files to change if implemented:**

1. `database/seeders/PlanSeeder.php` — change `trial_days` from 7 to 30
2. Migration to update existing plan records
3. `invent-mag-fe/src/components/Pricing.astro` — update "7-day" to "30-day" in all badges and CTA text
4. `invent-mag-fe/src/layouts/BaseLayout.astro` — update JSON-LD `offers` trial duration
5. Both pricing page meta descriptions
6. `invent-mag-fe/public/og-image.svg` — if trial days are mentioned

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
- Feature slugs to add: `ppn` (Phase 1), `efaktur_api` (Phase 2)

---

### Phase 1 — PPN Foundation & Faktur Pajak (Estimated: 3–4 weeks)

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

### Phase 2 — PJAP API Direct Submission (Estimated: 3–4 weeks)

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
- Tenant has Klikpajak API key configured

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

---

## 5. Other Planned Features

### 5.1 Bank Reconciliation

**Why:** Essential for the accounting module. Without reconciliation, GL balance and bank statement can drift. Required for accurate financial statements.

**What:**
- Import bank statements CSV (BCA, Mandiri, BNI, BRI — each has its own CSV format)
- Auto-match transactions to existing Journal Entries by amount + date proximity
- Manual match UI for unmatched items
- Reconciliation lock (prevent editing reconciled journal entries)
- Reconciliation report per period

**Complexity:** Medium (~3 weeks). Bank CSV parsers are the tricky part — each bank uses a different format and column layout. Build a `BankStatementParser` interface with implementations per bank.

---

### 5.2 Cash Flow Statement

**Why:** The third core financial statement. P&L and Balance Sheet already exist. Cash Flow Statement completes the set — required by Indonesian accounting standards (PSAK).

**Method:** Indirect method (Net Income → adjust for non-cash items + working capital changes). More standard and easier to implement than direct method.

**What:** New `FinancialReportingService` method (or extend existing `AccountingReportService`). Requires classifying all CoA accounts as operating/investing/financing activity types — add `cash_flow_category` field to `accounts` table.

**Complexity:** Medium (~2 weeks).

---

### 5.3 WhatsApp Invoice Delivery

**Why:** Indonesian businesses overwhelmingly use WhatsApp Business for customer communication. Sending invoice PDFs via WhatsApp outperforms email for this market segment.

**How:** 
- Primary: WhatsApp Business Cloud API (Meta) — direct, official, no per-message fee at low volume
- Alternative: Fonnte (`fonnte.com`) — popular in Indonesia, simpler API, per-message pricing

**Flow:** Button on invoice view page → generates PDF → sends via WhatsApp API → includes payment link (Midtrans Snap URL)

**Complexity:** Low-Medium (~1 week). High business impact for Indonesian market.

---

### 5.4 Midtrans Payment Links on Invoices

**Status:** Midtrans Snap already integrated for SaaS subscription payments (`Api/PaymentController.php`, `BillingService.php`).

**Extension:** Generate Snap payment links on customer invoices. On payment webhook, update `sales.status = 'Paid'` and create journal entry automatically (Dr Cash, Cr AR).

**Complexity:** Low (~1 week — Midtrans SDK already installed and configured).

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
| AI Sales Forecasting | ❌ | ❌ | ❌ |
| e-Faktur CSV | 🔜 Phase 1 | 🔜 Phase 1 | ✅ (CSV only) |
| e-Faktur Direct API | 🔜 Phase 2 | 🔜 Phase 2 | ❌ |
| REST API | ❌ | ❌ | ❌ |
| Free Trial | 7 days | 7 days | 30 days |

**Invent-MAG advantages:**
1. Monthly billing (no 12-month lock-in)
2. 3 users at Starter vs Zahir's 1 user
3. CRM & sales pipeline (unique in this price range)
4. AI Sales Forecasting — Holt-Winters (unique in Indonesian ERP market)
5. RESTful API (Enterprise)
6. Post Phase 2: one-click DJP submission vs Zahir's manual CSV

**Gaps to address:**
1. Only 7-day trial vs Zahir's 30-day (risk: not enough time to evaluate accounting)
2. No e-Faktur yet (Phase 1 = parity, Phase 2 = superior)
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
