# Invent-MAG Codebase — Senior Engineer Interview Q&A

> **Purpose:** This document is intended for senior engineers interviewing candidates who will work on the `invent-mag` codebase. Questions are ordered from foundational to deeply complex. Every question targets real code, real logic, and real architectural decisions made in this system. Answers reference actual implementation details.

---

## Table of Contents

1. [Architecture & System Design](#1-architecture--system-design)
2. [Multi-Tenancy](#2-multi-tenancy)
3. [Database Design & Relationships](#3-database-design--relationships)
4. [Subscription & Plan System](#4-subscription--plan-system)
5. [Inventory & FEFO Logic](#5-inventory--fefo-logic)
6. [Sales & Financial Calculations](#6-sales--financial-calculations)
7. [Double-Entry Accounting](#7-double-entry-accounting)
8. [Payroll Engine](#8-payroll-engine)
9. [Sales Forecasting Algorithm](#9-sales-forecasting-algorithm)
10. [Authentication & Authorization](#10-authentication--authorization)
11. [Security Implementation](#11-security-implementation)
12. [Performance & Query Optimization](#12-performance--query-optimization)
13. [API Design & Sanctum](#13-api-design--sanctum)
14. [Service Layer Pattern](#14-service-layer-pattern)
15. [Observer Pattern & Audit Logging](#15-observer-pattern--audit-logging)
16. [Customer Credit System](#16-customer-credit-system)
17. [CRM & AR/AP Aging](#17-crm--arap-aging)
18. [Middleware Chain](#18-middleware-chain)
19. [Frontend Architecture](#19-frontend-architecture)
20. [DevOps & Deployment](#20-devops--deployment)
21. [Quotation Module & Billing Consistency (v1.9)](#21-quotation-module--billing-consistency-v19)
22. [Super Admin Panel (SaaS Management)](#22-super-admin-panel-saas-management)
23. [Recent Production Incidents & Decisions (Aug 2026)](#23-recent-production-incidents--decisions-aug-2026)
24. [Indonesian Tax Compliance — PPN, e-Faktur & PPh (Implemented)](#24-indonesian-tax-compliance--ppn-e-faktur--pph-implemented)
25. [Bank Reconciliation (CSV-Based)](#25-bank-reconciliation-csv-based)

---

## 1. Architecture & System Design

---

### Q1.1 — Describe the overall architecture of invent-mag. What pattern does it follow and why?

**Answer:**

Invent-MAG is a **monolithic Laravel MVC application with a dedicated Service Layer**. It is not a microservices system — all modules (inventory, sales, accounting, HR, CRM, POS, forecasting) live in a single Laravel 11 codebase.

The key architectural decisions are:

| Layer | Location | Responsibility |
|---|---|---|
| Controller | `app/Http/Controllers/Admin/` | HTTP request handling, validation delegation, response rendering |
| Service | `app/Services/` | All business logic (40+ services) |
| Model | `app/Models/` | Eloquent ORM, relationships, scopes |
| Observer | `app/Observers/` | Cross-cutting concerns (audit logs, credit sync) |
| Middleware | `app/Http/Middleware/` | Request pipeline (auth, tenant resolution, feature gates, security) |
| DTO | `app/DTOs/` | Typed data transfer between layers |
| Resource | `app/Http/Resources/` | API JSON transformation |

**Why monolithic?** For a B2B SaaS ERP at this scale, the operational simplicity of a single deployable unit outweighs the flexibility of microservices. Shared transactional boundaries (e.g., a sale must atomically update stock, create journal entries, and log audit trails) are far easier in a monolith with database transactions.

The Service Layer is critical: controllers are intentionally thin — they validate input via Form Requests and delegate all business logic to services. This keeps controllers testable and prevents business logic from leaking into HTTP layer.

**Follow-up trap question:** "Is there anything wrong with this architecture at scale?"

A candidate should note:
- The single database becomes a bottleneck under heavy multi-tenant load
- **Cache/queue drivers:** the *config defaults* are `database` (`config/cache.php` `CACHE_STORE`, `config/queue.php` `QUEUE_CONNECTION`), while `.env.example` sets **Redis**. Production runs on Redis (Redis Cloud on Railway). A candidate who says "the file cache driver is the default and doesn't scale" without checking `config/` is demonstrating they haven't read the current code — the defaults are `database`, and production uses Redis.

---

### Q1.2 — What tech stack does this project use? List both backend and frontend.

**Answer:**

**Backend:**
- PHP 8.2 with Laravel 11.31
- `spatie/laravel-multitenancy` ^4.0 — domain-based multi-tenancy
- `spatie/laravel-permission` ^6.20 — RBAC (roles + permissions)
- `spatie/laravel-backup` ^9.3 — installed as a dependency but **not** used for tenant backups; the `backup:tenant` Artisan command is a custom implementation (see Q20.2)
- `laravel/fortify` ^1.25 — authentication pipeline
- `laravel/sanctum` ^4.0 — API token authentication
- `hotwired-laravel/turbo-laravel` ^2.0 — Hotwire Turbo Drive (SPA-like navigation)
- `midtrans/midtrans-php` — payment gateway integration
- `dompdf/dompdf` ^3.1 — PDF invoice/report generation
- `knuckleswtf/scribe` ^5.8 — auto-generated API documentation
- `resend/resend-laravel` ^1.3 — transactional email (Resend.com)
- `nesbot/carbon` ^3.0 — date/time handling

**Frontend:**
- **Blade** templates (server-rendered HTML)
- **Alpine.js** ^3.4.2 — lightweight reactive UI (dropdowns, modals, form state)
- **Tailwind CSS** ^3.1 with `@tailwindcss/forms`
- **Tabler UI** (pre-built admin component library, included as static assets in `/public/tabler/`)
- **Hotwire Turbo** — eliminates full page reloads on navigation
- **Vite** ^6.0 — asset bundling and HMR in development
- **Vitest** — JavaScript unit testing
- **Axios** — HTTP client for API calls from frontend

**Database:** MySQL (primary production), migration files are SQLite-compatible.

**Infrastructure:** Docker, **Railway (PaaS — current production deployment)**, GitHub Actions CI/CD (CI on every push/PR; release automation builds a GHCR image and deploys it to a VPS via `appleboy/ssh-action` on version tags — see Q20.1). Domains: `invent-mag.web.id` (marketing/landing) and `api.invent-mag.web.id` (backend API).

---

### Q1.3 — Why was Hotwire Turbo chosen over a full SPA framework like Vue or React?

**Answer:**

Hotwire Turbo allows the application to feel like a Single Page Application (fast navigation without full page reloads) while keeping the stack **server-rendered Blade templates**. This is a deliberate trade-off:

- **No JSON API required** for page navigation — HTML fragments are returned directly from the server
- **Less JavaScript complexity** — Alpine.js handles only reactive UI (modals, dropdowns), not routing or state management
- **Faster initial development** — backend developers do not need React/Vue expertise
- **SEO-friendly** — server renders complete HTML
- **Simpler auth** — session-based auth works without token management for web views

The trade-off is that highly interactive UIs (like a real-time POS terminal or complex spreadsheet-like grids) require more effort with Alpine.js + Turbo than with React.

Alpine.js is used for local component state (e.g., toggling modals, form validation feedback), while Turbo handles page-level navigation and form submissions.

---

## 2. Multi-Tenancy

---

### Q2.1 — How does invent-mag implement multi-tenancy? Explain the full isolation mechanism.

**Answer:**

Invent-MAG uses **single-database multi-tenancy** implemented via `spatie/laravel-multitenancy`.

**Tenant identification:** Domain-based. Each tenant gets a subdomain (e.g., `acme.127.0.0.1.nip.io`). The `DomainTenantFinder` class resolves the tenant from the HTTP `Host` header on every request. Configuration:
```
APP_DOMAIN=127.0.0.1.nip.io
SESSION_DOMAIN=.127.0.0.1.nip.io
```

**Database isolation:** All tenant data lives in the **same database**. Every tenant-scoped table has a `tenant_id` foreign key column.

**Automatic filtering:** The `BelongsToTenant` trait (in `app/Models/Concerns/BelongsToTenant.php`) is applied to every tenant-scoped model. This trait:
1. Registers a global `TenantScope` that automatically appends `WHERE tenant_id = ?` to every Eloquent query
2. On model `creating` event, automatically sets `tenant_id = app('currentTenant')->id`

**Result:** A developer cannot accidentally query another tenant's data through Eloquent — the global scope makes cross-tenant leakage impossible at the ORM level.

**Landlord vs. Tenant models:**
- `Tenant` and `Plan` models are **landlord models** — they live outside the tenant scope and are accessible globally
- Everything else is tenant-scoped

**Middleware:**
- `NeedsTenant` — enforces that a valid tenant context exists; throws `NoCurrentTenant` if tenant not found (rendered by the exception handler — 404 JSON for `/api/*`, redirect to super-admin dashboard on the super domain, redirect to frontend login otherwise; see Q2.2)
- `HandleTenantFromPath` — custom middleware that resolves tenant on each request

**Critical weakness a senior engineer should identify:**
Raw `DB::table()` queries bypass the global scope and do NOT automatically filter by `tenant_id`. Developers must manually add `->where('tenant_id', $tenantId)` on raw queries. The codebase does this deliberately for performance-critical aggregation queries (e.g., dashboard metrics), but it is a potential source of data leakage if a developer forgets.

---

### Q2.2 — What happens when a tenant is not found? What is the fallback behavior?

**Answer:**

When `DomainTenantFinder` cannot match the HTTP `Host` header to any tenant record in the `tenants` table, `NeedsTenant` middleware throws `Spatie\Multitenancy\Exceptions\NoCurrentTenant`. The behavior is centralized in `bootstrap/app.php` (`withExceptions`):

- **API routes (`/api/*`)** → returns a **404 JSON** response (`Workspace not found or no tenant specified.`)
- **Super admin domain** (`super.invent-mag.web.id`) → redirects to the **super admin dashboard** (super admin routes deliberately exclude `NeedsTenant`/`HandleTenantFromPath`)
- **Any other web request** → redirects to the **frontend login page** (`config('app.frontend_url') . '/login'`)

This is deliberate: failing closed (404 for API) is safer than accidentally serving another tenant's data or an unauthenticated dashboard. The main marketing/landing page domain and the public API endpoints (registration, tenant lookup, contact) are excluded from the tenant-resolving middleware entirely.

---

### Q2.3 — What are the `tenants` table columns and what do the billing-related columns do?

**Answer:**

The `tenants` table (landlord migration) contains:

| Column | Purpose |
|---|---|
| `id` | Primary key |
| `name` | Tenant/company name |
| `domain` | Subdomain (e.g., `acme.127.0.0.1.nip.io`) |
| `npwp` | NPWP tax registration number (DJP) |
| `pkp_status` | Whether the tenant is a PKP (VAT-registered) business |
| `pkp_date` | Date of PKP registration |
| `ppn_rate` | Default PPN rate (%) — defaults to `11.00` |
| `klikpajak_api_key` | KlikPajak PJAP API key (Phase 2 — stored for future e-invoice submission) |
| `klikpajak_secret` | KlikPajak PJAP API secret (Phase 2) |
| `klikpajak_use_sandbox` | Whether KlikPajak calls use the sandbox environment (Phase 2) |
| `tax_jurisdiction` | `ID` or null — set to `ID` enables Indonesian tax enforcement (PPN/PPh) |
| `plan_id` | Current active plan FK |
| `plan_status` | `active`, `trialing`, `expired`, `terminated` |
| `trial_ends_at` | Trial expiry timestamp |
| `plan_expires_at` | Subscription expiry timestamp |
| `plan_changed_at` | When the plan was last changed |
| `pending_plan_id` | Scheduled downgrade target plan |
| `pending_plan_effective_at` | When the scheduled downgrade takes effect |
| `billing_period_start` | Current billing cycle start |
| `billing_period_end` | Current billing cycle end |
| `proration_credit` | Credit balance from mid-cycle upgrades |
| `accounting_settings` | JSON — maps accounting roles to `accounts` table IDs (sales_revenue, AR, COGS, inventory, AP, cash, salary_expense, salary_payable). Migrated from `users` table (May 2026). |
| `backup_password` | Backup encryption key — stored using Laravel `encrypted` cast |
| `backup_encrypted` | Whether backup is encrypted |

**Billing logic:**
- **Upgrades** are immediate. The remaining days on the current plan are prorated and stored as `proration_credit`, which offsets the cost of the new plan.
- **Downgrades** are scheduled: `pending_plan_id` + `pending_plan_effective_at` are set, and the `ProcessScheduledPlanChanges` Artisan command (run via cron) applies the downgrade at the correct date by calling `Tenant::applyScheduledDowngrade()`.

---

## 3. Database Design & Relationships

---

### Q3.1 — Explain the `product_warehouse` table. Why does it exist and what problem does it solve?

**Answer:**

`product_warehouse` is a **pivot table with extra data** that implements multi-warehouse stock tracking.

Schema: `id, tenant_id, product_id, warehouse_id, quantity`

**Problem it solves:** A product can be stocked in multiple warehouses. Without this table, you would need to store `quantity` on the `products` table — but then you could only track total stock, not per-warehouse stock. The `product_warehouse` table allows:
- Querying stock of a specific product in a specific warehouse: `WHERE product_id = ? AND warehouse_id = ?`
- Querying all products in a warehouse
- Aggregating total stock across all warehouses
- Inter-warehouse stock transfers (decrement source warehouse, increment destination)

**Key relationship:**
```php
// Product has many warehouses via pivot
Product::hasMany(ProductWarehouse::class)

// Stock transfer: atomic decrement + increment
DB::transaction(function() {
    ProductWarehouse::where([...])->decrement('quantity', $qty);
    ProductWarehouse::where([...])->increment('quantity', $qty);
});
```

**Why `tenant_id` on the pivot?** Because the `BelongsToTenant` trait requires it for the global scope filter. Without `tenant_id` on this table, the `TenantScope` cannot protect it.

---

### Q3.2 — The `payments` table uses a polymorphic relationship. Explain how it works and why this design was chosen.

**Answer:**

The `payments` table has columns `paymentable_type` and `paymentable_id`, implementing Laravel's **polymorphic relationship**.

This allows a single `payments` table to record payments for multiple types of transactions:
- `paymentable_type = 'App\Models\Sales'`, `paymentable_id = 123` — payment for a sales order
- `paymentable_type = 'App\Models\Purchase'`, `paymentable_id = 456` — payment for a purchase order

**Why polymorphic?** The alternative is separate `sales_payments` and `purchase_payments` tables — which would duplicate columns, queries, and logic. Polymorphic consolidates payment tracking into a single table with shared logic for:
- Recording payment amounts, dates, methods
- Calculating outstanding balances
- Generating payment receipts

**Trade-off:** Polymorphic relationships **cannot use foreign key constraints** at the database level (`paymentable_id` cannot reference two different tables simultaneously). This means referential integrity must be enforced at the application layer.

**Senior engineers should note:** The query `payments WHERE paymentable_type = 'Sales' AND paymentable_id IN (...)` is heavily used for calculating payment totals. The compound index on `(paymentable_type, paymentable_id)` is essential for performance.

---

### Q3.3 — The `po_items` table has a `remaining_quantity` column. What is it for and how is it maintained?

**Answer:**

`remaining_quantity` is used to implement **FEFO (First Expired, First Out) batch tracking**.

When a purchase order is received:
- `po_items.quantity` = total quantity received from supplier
- `po_items.remaining_quantity` = quantity not yet consumed by sales (initially equals `quantity`)

When a sale is created:
1. The system queries `po_items` for the given product, ordered by `expiry_date ASC` (oldest expiry first)
2. It decrements `remaining_quantity` batch by batch until the sale quantity is fulfilled
3. COGS (Cost of Goods Sold) is calculated from the actual `po_items.price` of each consumed batch

When a sale is deleted or returned:
- The `remaining_quantity` is restored to the relevant batches (in reverse FEFO order, or by re-crediting the specific batches)

This is a **real inventory accounting mechanism** — it ensures that:
1. Products closest to expiry are sold first (reduces waste)
2. COGS accurately reflects the actual purchase cost of goods sold
3. Stock levels per batch are trackable

---

### Q3.4 — How does the `journal_entries` and `transactions` table relationship model double-entry bookkeeping?

**Answer:**

This is a classic **header-line double-entry ledger design**:

**`journal_entries`** (header):
- `id, description, date, status (draft/posted/void), entry_type (standard/opening/reversal/adjusting/closing)`
- `sourceable_type + sourceable_id` — polymorphic link to the business event that triggered this entry (e.g., a `Sales` record)
- `reversed_entry_id` — FK to the entry being reversed (for reversal entries)

**`transactions`** (lines):
- `id, journal_entry_id, account_id, type (debit/credit), amount`
- Each journal entry has at minimum **2 transaction lines**

**The golden rule:** `SUM(debit amounts) MUST EQUAL SUM(credit amounts)` for every journal entry. The `AccountingService::validateTransactions()` method enforces this before allowing an entry to be posted.

**Example — Sale of goods:**
```
Journal Entry: "Sale INV-0001"
  DEBIT:  Accounts Receivable    $1,000
  CREDIT: Revenue                $1,000
  DEBIT:  Cost of Goods Sold     $600
  CREDIT: Inventory              $600
```

**Lifecycle states:**
- `draft` → entry is created but not yet in effect; can be edited
- `posted` → entry is finalized and affects financial reports; cannot be directly edited
- `void` → the entry is cancelled in place by setting `status = void` and appending the void reason to `notes` (`AccountingService::voidJournalEntry()`). **No reversal entry is created** — the original lines remain but the entry no longer counts as `posted`. Voiding requires the entry to be `posted` and blocks entries tied to a completed bank reconciliation (`assertNotReconciled()`).
- `reversal` → a correcting entry created by `AccountingService::reverseJournalEntry()`: it builds a new entry with the debits/credits swapped, `entry_type = reversal`, posts it immediately, and points its `reversed_entry_id` at the original entry. The original must have `status = posted` (`canBeReversed()`); reversal is also blocked on reconciled entries.

---

## 4. Subscription & Plan System

---

### Q4.1 — How does the plan/feature access control system work end-to-end?

**Answer:**

There are **four enforcement layers**, each at a different level:

**Layer 1 — Middleware (`CheckPlanFeature`):**
```php
Route::get('/accounting', ...)->middleware('plan.feature:accounting');
```
If the current tenant's plan does not include the `accounting` feature, the middleware redirects to a "feature locked" upgrade page.

**Layer 2 — Middleware (`CheckSubscriptionStatus`):**
Applied to all authenticated tenant routes. If `tenant.plan_status = expired` AND the trial has ended, all routes except the plan management page are blocked.

**Layer 3 — Blade directive (`@planHas`):**
```blade
@planHas('pos')
  <a href="/pos">Point of Sale</a>
@endPlanHas
```
Hides navigation items and UI elements that the current plan does not include. Registered as a custom Blade directive in a service provider.

**Layer 4 — Navigation filter (`NavigationServiceProvider`):**
The navigation config (`config/navigation.php`) has a `plan_feature` key per menu item. `NavigationServiceProvider` filters the menu array at runtime, removing items the current plan doesn't support.

**Plan features per tier** (verified against `database/seeders/PlanSeeder.php`):

| Feature Slug | Starter | Professional | Enterprise |
|---|---|---|---|
| `products, inventory, sales, purchases, invoices, dashboard, basic_reports, customers, suppliers` | ✓ | ✓ | ✓ |
| `pos, sales_returns, purchase_returns` | ✓ | ✓ | ✓ |
| `stock_opname, accounting, crm, sales_pipeline, financial_reports, multi_currency, hr, cash_flow, quotations, ppn, bank_reconciliation` | ✗ | ✓ | ✓ |
| `sales_forecasting, api_access, audit_compliance` | ✗ | ✗ | ✓ |

Note: `efaktur_api` (e-Faktur Phase 2 — PJAP direct DJP submission) is an **Enterprise-only** slug planned but not yet live; `ppn` (Phase 1 — Faktur Pajak PDF + DJP CSV) is already on Professional & Enterprise. See ROADMAP §4.

**Backward compatibility:** Tenants without a plan assigned (legacy tenants) get enterprise-level access. The config key `plans.legacy_full_access = true` controls this behavior.

---

### Q4.2 — Explain how plan upgrade proration works.

**Answer:**

When a tenant upgrades mid-billing-cycle:

1. **Calculate remaining days** on current plan:
   ```
   remaining_days = billing_period_end - today
   daily_rate = current_plan.price / billing_cycle_days
   proration_credit = remaining_days × daily_rate
   ```

2. **Store** `proration_credit` on the `tenants` table.

3. **Calculate new plan cost:**
   ```
   new_plan_cost = new_plan.price - proration_credit
   ```
   The `BillingService` ensures the charge is never negative (minimum = 0).

4. **Charge** the prorated amount via Midtrans.

5. **On payment confirmation** (Midtrans webhook): call `Tenant::assignPlan()` to immediately switch the tenant to the new plan, update `billing_period_start`, `billing_period_end`, and reset `proration_credit` to 0.

**Downgrade flow:**
- Downgrades are **not immediate** — they are scheduled for the end of the current billing period.
- `pending_plan_id` and `pending_plan_effective_at` are set on the tenant record.
- The `ProcessScheduledPlanChanges` Artisan command runs daily and calls `Tenant::applyScheduledDowngrade()` when `pending_plan_effective_at <= now()`.
- This ensures the tenant gets the full value of the current billing period before being downgraded.

---

### Q4.3 — How does the Midtrans webhook handler verify payment authenticity?

**Answer:**

The `MidtransController::handleWebhook()` method verifies the **SHA-512 signature**:

```php
$signatureKey = hash('sha512',
    $payload['order_id'] .
    $payload['status_code'] .
    $payload['gross_amount'] .
    config('services.midtrans.server_key')
);

if ($signatureKey !== $payload['signature_key']) {
    return response()->json(['message' => 'Invalid signature'], 403);
}
```

This concatenation of `order_id + status_code + gross_amount + server_key` and SHA-512 hashing is the **Midtrans standard verification method**. It ensures:
1. The webhook originated from Midtrans (only they know the server key)
2. The order ID, status, and amount have not been tampered with in transit

**On `capture` or `settlement` status:**
- Find the `SubscriptionOrder` by `order_number`
- Call `Tenant::assignPlan()` to upgrade the plan
- Send `SubscriptionInvoiceMail` to the tenant admin

**On `cancel`, `deny`, or `expire` status:**
- Mark `SubscriptionOrder.status = failed`
- The tenant stays on their current plan

**Security note:** The webhook endpoint must NOT require CSRF verification (it's an external POST from Midtrans). This is handled by adding the route to the `except` array in the CSRF middleware or by registering it on an API route group that has CSRF disabled.

---

## 5. Inventory & FEFO Logic

---

### Q5.1 — Explain FEFO and how it is implemented in the sales flow.

**Answer:**

**FEFO = First Expired, First Out.** It is an inventory rotation strategy where the batch with the earliest expiration date is consumed first. This is mandatory for industries with perishable goods.

**Implementation in `SalesService`:**

When creating a sale with items that have `has_expiry = true`:

```php
// Step 1: Get all batches for this product, ordered by earliest expiry
$batches = POItem::where('product_id', $productId)
    ->where('remaining_quantity', '>', 0)
    ->whereNotNull('expiry_date')
    ->orderBy('expiry_date', 'asc')
    ->get();

// Step 2: Consume batches in order
$quantityNeeded = $saleItemQuantity;
foreach ($batches as $batch) {
    if ($quantityNeeded <= 0) break;

    $consume = min($batch->remaining_quantity, $quantityNeeded);
    $batch->decrement('remaining_quantity', $consume);

    // Calculate COGS from actual batch cost
    $cogs += $consume * $batch->price;
    $quantityNeeded -= $consume;
}

// Step 3: Check if we could fulfill the full quantity
if ($quantityNeeded > 0) {
    throw new InsufficientStockException();
}
```

**Why this matters for accounting:** FEFO is not just about stock rotation — it directly impacts COGS calculation. Using the actual cost of the batch being depleted (rather than an average cost) produces more accurate financial statements.

**Contrast with FIFO:** FIFO (First In, First Out) orders by purchase date, not expiry date. FEFO is a superset of FIFO for products with expiry dates.

---

### Q5.2 — What happens to stock levels when a sale is deleted vs. when a sales return is created? Are they the same?

**Answer:**

**No, they are fundamentally different operations.**

**Sale deletion** (`SalesService::deleteSale()`):
- Completely reverses all stock deductions
- Restores `remaining_quantity` to all consumed `po_items` batches
- Deletes the accounting journal entries
- Deletes all payment records
- The sale is gone as if it never happened

**Sales return** (`SalesReturnService::createReturn()`):
- The original sale remains in the database (for audit trail and accounting history)
- A new `sales_returns` record is created with `reason`, `return_date`, `status`
- Stock is re-added to `product_warehouse.quantity` (but NOT necessarily restored to the original `po_item` batch)
- A **credit note** is created — either as a `customer_credit` record (if the tenant offers store credit) or as a cash refund
- A **reversal journal entry** is created in accounting to negate the revenue and COGS of the returned items
- `sales_returns.customer_credit_id` links to the generated credit if applicable

**Key distinction:** A return is an accounting event with its own paper trail. A deletion is an erasure. Returns are the correct operation for post-delivery situations; deletion is only appropriate for data entry errors.

---

### Q5.3 — How does stock opname (physical stock count) work and what happens when variances are found?

**Answer:**

Stock opname is a physical inventory count process managed by `StockOpnameService`.

**Workflow:**

1. **Create a stock opname session** (`stock_opnames` record) — captures the session date, warehouse, and who initiated it
2. **System records expected stock** — `stock_opname_items` are pre-populated with `system_quantity` from `product_warehouse.quantity`
3. **Physical count** — staff enters the actual counted quantity into `stock_opname_items.actual_quantity`
4. **Variance calculation:**
   ```
   variance = actual_quantity - system_quantity
   ```
   Positive variance = more stock found than system shows (gain)
   Negative variance = less stock than system shows (shrinkage/loss)
5. **Approval** — a supervisor reviews variances
6. **Apply adjustments** — on approval:
   - `product_warehouse.quantity` is updated to match `actual_quantity`
   - `stock_adjustments` records are created for each variance
   - Accounting journal entries are posted: Inventory account is debited/credited, with the offset going to an Inventory Adjustment expense/income account

**Why this matters:** Without stock opname reconciliation, the system's stock numbers diverge from reality over time (due to theft, damage, counting errors, system bugs). Regular opname cycles are a fundamental internal control requirement.

---

## 6. Sales & Financial Calculations

---

### Q6.1 — Walk through the exact calculation logic for a sales order total, including item discounts, order discounts, and taxes.

**Answer:**

The calculation follows a specific order of operations (implemented in `SalesHelper` and `SalesService`):

**Step 1 — Item-level calculation:**
```
item_subtotal = unit_price × quantity

if discount_type == 'percentage':
    item_discount_amount = item_subtotal × (discount / 100)
else:
    item_discount_amount = discount (fixed amount)

item_total = item_subtotal - item_discount_amount
```

**Step 2 — Order subtotal:**
```
subtotal = SUM(item_total for all items)
```

**Step 3 — Order-level discount:**
```
if order_discount_type == 'percentage':
    order_discount_amount = subtotal × (order_discount / 100)
else:
    order_discount_amount = order_discount (fixed amount)

discounted_subtotal = subtotal - order_discount_amount
```

**Step 4 — Tax:**
```
# For exclusive tax (tax added on top):
tax_amount = discounted_subtotal × (tax_rate / 100)
grand_total = discounted_subtotal + tax_amount

# For inclusive tax (tax already in price):
tax_amount = discounted_subtotal - (discounted_subtotal / (1 + tax_rate / 100))
grand_total = discounted_subtotal  (unchanged — tax is already inside the price)

# For none (exempt):
tax_amount = 0
grand_total = discounted_subtotal
```
Note: Tax type (`exclusive`/`inclusive`/`none`) is determined by the selected `Tax` record's `type` field. Tax is applied **after** the order discount, not on the gross subtotal.

**Step 5 — Grand total:**
```
# For exclusive tax:
grand_total = discounted_subtotal + tax_amount
# For inclusive tax or none:
grand_total = discounted_subtotal
```

**Step 6 — Payment status determination:**
```
total_paid = SUM(payments.amount) + sales.customer_credit_used
if total_paid >= grand_total: status = 'Paid'
elif total_paid > 0: status = 'Partial'
else: status = 'Unpaid'
```

**Common interview trap:** Ask "what if discount is applied after tax?" — The answer in this system is: discount is applied BEFORE tax. Getting this order wrong produces incorrect financial statements.

---

### Q6.2 — How does the system handle partial payments? What columns track payment state?

**Answer:**

The `sales` table tracks:
- `total` — the grand total owed
- `amount_received` — amount paid in cash on POS transactions
- `change_amount` — change given back on POS transactions
- `customer_credit_used` — amount of customer credit applied

The `payments` table tracks all subsequent payment installments:
```
payments WHERE paymentable_type = 'App\Models\Sales' AND paymentable_id = {sales_id}
```

**Outstanding balance:**
```
outstanding = sales.total - SUM(payments.amount) - sales.customer_credit_used
```

**Payment status IS stored** as the `status` enum column on `sales` — `['Unpaid', 'Paid', 'Partial', 'Returned']` (migration `2025_02_03_065737`, default `Unpaid`, indexed). It is determined at write time from `total`, payments, and credit used, and is kept in sync by `SalesService` when payments/returns/credit applications change. (This column also powers the aging queries in `DashboardService`, which filter `WHERE status IN ('Unpaid', 'Partial')`.)

**POS special case:** POS sales (`type = 'pos'`) are always marked as **Paid** at creation time. The POS flow does not support partial payment — the customer must pay in full before completing the transaction. Note: the underlying column is now a `type` string (`'regular'` or `'pos'`); the old boolean `is_pos` column was dropped in migration `2026_05_18_075029`. A backward-compat accessor `getIsPosAttribute()` exists so legacy view code using `$sale->is_pos` still works. **POS sales now create accounting journal entries through AccountingService (July 2026): Dr AR/Cr Revenue, Dr COGS/Cr Inventory, Dr Cash/Cr AR — identical to regular sales.**

---

## 7. Double-Entry Accounting

---

### Q7.1 — A new developer asks: "Why can't I just directly update an account balance?" How do you explain double-entry and the role of `AccountingService`?

**Answer:**

Double-entry bookkeeping is the foundation of accurate financial reporting. You cannot simply update an account balance because:

1. **Every financial event affects at least two accounts** — if cash increases, something else must change (e.g., liability increases, or revenue increases). This is the accounting equation: `Assets = Liabilities + Equity`.

2. **Debits must always equal credits** — this is not just a rule; it is a mathematical constraint that proves the accounting equation holds. If they don't balance, the books are wrong.

3. **Immutability** — posted journal entries cannot be edited. To correct a mistake, you create a **reversal entry** that negates the original. This preserves the complete audit trail.

**`AccountingService` enforces these rules:**

```php
protected function validateTransactions(array $transactions): void
{
    $debits = 0;
    $credits = 0;

    foreach ($transactions as $tx) {
        // each line must carry an account reference, a 'debit'/'credit' type, and an amount
        if ((! isset($tx['account_name']) && ! isset($tx['account_code']) && ! isset($tx['account_id'])) || ! isset($tx['type'], $tx['amount'])) {
            throw new \Exception('Each transaction must have an account_id, account_name, or account_code, type, and amount.');
        }
        if (! in_array($tx['type'], ['debit', 'credit'])) {
            throw new \Exception("Invalid transaction type: {$tx['type']}. Must be 'debit' or 'credit'.");
        }
        $tx['type'] === 'debit' ? $debits += $tx['amount'] : $credits += $tx['amount'];
    }

    if (count($transactions) < 2) {
        throw new \Exception('At least two transactions (debit and credit) are required.');
    }
    if (round($debits, 2) !== round($credits, 2)) {
        throw new \Exception("Debits ({$debits}) do not equal credits ({$credits}).");
    }
}
```

Note: this method throws the generic `\Exception` — there are **no** dedicated `UnbalancedJournalEntryException`/`InvalidJournalEntryException` classes in the codebase. `validateTransactions()` is `protected` and called from `createJournalEntry()` / `postJournalEntry()`; the balanced-state and minimum-line-count checks are the same in either entry point.

**`SalesService` uses `AccountingService`** to automatically create journal entries when a sale is created, paid, or returned — the developer creating a sale does not need to think about accounting; it happens automatically.

---

### Q7.2 — What journal entries are automatically created when a sale is posted, and when payment is received?

**Answer:**

**On sale creation (posted):**

```
DEBIT:  Accounts Receivable (Asset)    $1,000   [customer owes us]
CREDIT: Sales Revenue (Revenue)        $1,000   [we earned revenue]

DEBIT:  Cost of Goods Sold (Expense)  $600     [expense the inventory cost]
CREDIT: Inventory (Asset)             $600     [reduce inventory value]
```

Note: The COGS amount comes from the actual batch cost calculated during FEFO processing.

**Indonesian PPN (VAT) sales — VAT-split at creation (Phase 1E, Aug 2026):** For tenants in the Indonesian jurisdiction (`isIndonesian()`), a sale whose tax record is flagged `is_ppn = true` posts a **VAT-split** entry at sale creation instead of a single AR/Revenue entry:

```
DEBIT:  Accounts Receivable (Asset)   $1,110   [gross = net + VAT]
CREDIT: Sales Revenue (Revenue)        $1,000   [net of VAT]
CREDIT: PPN Keluaran (Liability)         $110   [VAT owed to DJP]
```

This is why revenue is booked **net of VAT** and the Faktur Pajak itself carries no journal (it is a compliance document). The PPN Keluaran account comes from `accounting_settings.ppn_keluaran_account_id` (seeded default **2130** — Output VAT). Sales returns reverse the PPN whenever the original sale `isPpn()` — no Faktur Pajak is required for the reversal. Purchases similarly carry a PPN Masukan (input VAT) snapshot (`po.ppn_enabled/rate/amount`) that debits the input-VAT account (1160), and `EFakturService::getSptMasaSummary()` returns `total_ppn_masukan` and `net_ppn_payable` (PPN Kurang Bayar = max(0, Keluaran − Masukan)). See PPN_EVOLUTION.md Phase 1E.

**POS sales** (July 2026) use the exact same journal entry pattern as regular sales. PosService injects AccountingService and posts all three entries (AR/Revenue, COGS/Inventory, Cash/AR) at creation time since POS sales are always paid in full.

**On payment received:**

```
DEBIT:  Cash/Bank (Asset)              $1,000   [we received cash]
CREDIT: Accounts Receivable (Asset)    $1,000   [clear the receivable]
```

**On sales return:**

```
DEBIT:  Sales Returns & Allowances (contra-Revenue)  $300   [reduce revenue]
CREDIT: Accounts Receivable (Asset)                  $300   [reduce what customer owes]

DEBIT:  Inventory (Asset)                            $180   [restore inventory]
CREDIT: Cost of Goods Sold (Expense)                 $180   [reverse COGS]
```

**The `sourceable` polymorphic link** on `journal_entries` (`sourceable_type = 'App\Models\Sales'`, `sourceable_id = 123`) ensures that every journal entry can be traced back to the business event that created it.

**On purchase return:**

```
DEBIT:  Accounts Payable (Liability)              $300   [reduce what we owe supplier]
CREDIT: Purchase Returns & Allowances (contra-COGS) $300   [reduce cost]

DEBIT:  Cost of Goods Sold (Expense)              $180   [reverse COGS]
CREDIT: Inventory (Asset)                         $180   [restore inventory]
```

Purchase returns use the Purchase Returns account (code 5120) for the credit side, not the AP account directly (which would create a self-cancelling entry).

**Bulk return operations (July 2026):** Both `bulkCompleteSalesReturns()` and `bulkCancelSalesReturns()` process each return individually through `completeSalesReturn()` / `cancelSalesReturn()`, creating journal entries, adjusting stock, and issuing/reversing credits as needed. Purchase return bulk operations similarly use `cancelPurchaseReturn()` for reversal side effects.

---

### Q7.3 — What is the difference between `void` and `reverse` for a journal entry? When should each be used?

**Answer:**

**Void:**
- Marks the journal entry `status = void`
- Effectively cancels it — it no longer appears in financial reports
- Used for entries that were created in `draft` status and never should have been posted
- Cannot void a `posted` entry directly without creating a reversal

**Reverse:**
- Creates a new journal entry (`entry_type = 'reversal'`) that has all debit/credit lines swapped
- The original entry remains in the system with `status = posted` (immutable audit record)
- The new reversal entry negates the accounting effect
- `reversed_entry_id` FK links the reversal back to the original entry
- Used for: correcting errors in posted entries, reversing accruals at period end

**Why immutability matters:** In accounting, you cannot erase history. If you discover a $10,000 sale was recorded incorrectly, you do not delete it — you create a reversal and then create the correct entry. This ensures:
1. The audit trail shows exactly what happened and when it was corrected
2. Financial reports for historical periods remain accurate
3. Compliance requirements are met (tax authorities often require this)

---

## 8. Payroll Engine

---

### Q8.1 — Walk through the complete payroll calculation for an employee with absences and overtime.

**Answer:**

The `PayrollService::createPayrollItem()` uses this formula:

**Given:**
- `base_salary` = $5,000/month
- `working_days` = 21 (from `AttendancePeriod.working_days`)
- `present_days` = 18
- `absent_days` = 3 (absent without approved leave)
- `overtime_hours` = 8 hours
- `allowances` = `{"transport": 200, "meal": 150}` → $350
- `deductions` = `{"bpjs": 100}` → $100

**Step 1 — Absence deduction:**
```
daily_rate = base_salary / working_days = $5,000 / 21 = $238.10
absence_deduction = absent_days × daily_rate = 3 × $238.10 = $714.29
```

**Step 2 — Overtime pay:**
```
hourly_rate = base_salary / 176   # 176 = standard monthly hours (22 days × 8 hrs)
overtime_pay = overtime_hours × hourly_rate = 8 × ($5,000 / 176) = $227.27
```

**Step 3 — Gross pay:**
```
gross_pay = base_salary + allowances + overtime_pay - absence_deduction
gross_pay = $5,000 + $350 + $227.27 - $714.29 = $4,862.98
```

**Step 4 — Net pay:**
```
net_pay = gross_pay - other_deductions = $4,862.98 - $100 = $4,762.98
```

**Payroll journal entries (auto-created by `PayrollService`):**

*Accrual (when payroll run is processed):*
```
DEBIT:  Salary Expense      $4,762.98
CREDIT: Salaries Payable    $4,762.98
```

*Payment (when payroll is marked paid):*
```
DEBIT:  Salaries Payable    $4,762.98
CREDIT: Cash/Bank           $4,762.98
```

---

### Q8.2 — What is the significance of `attendance_periods.status` and what happens when you close a period?

**Answer:**

`attendance_periods.status` can be `open` or `closed`.

**Open period:**
- Attendance records can be added, edited, or deleted
- Payroll has not been finalized
- No payroll run has been approved

**Closed period:**
- Attendance records are **locked** — no edits allowed
- Payroll has been approved and marked as paid
- Closing a period triggers a check: no payroll run can be left in `draft` status before closing
- Prevents retroactive attendance manipulation after payroll is processed

**Why this matters:** Without period locking, a bad actor could modify attendance records after payroll was paid to hide unauthorized absence deductions or inflate overtime. Closing periods is a financial control mechanism.

The `AttendanceService` enforces this: any attempt to create/update/delete an `AttendanceRecord` where `attendance_period.status = closed` throws an exception.

---

## 9. Sales Forecasting Algorithm

---

### Q9.1 — Explain the Holt-Winters algorithm used in `SalesForecastService`. What are the three components and how are they used?

**Answer:**

**Holt-Winters Triple Exponential Smoothing** (also called "Triple Exponential Smoothing") decomposes a time series into three components. This implementation uses the **additive** form (components are summed, not multiplied — verified in `SalesForecastService` lines ~179-194):

1. **Level (α = 0.3)** — the current baseline value of the series, analogous to a smoothed average
2. **Trend (β = 0.1)** — the direction and magnitude of change over time (upward/downward slope)
3. **Seasonality (γ = 0.1)** — repeating patterns over a fixed seasonal period (12 months for monthly sales data)

**Parameters used in this system:**
- α = 0.3 (level smoothing factor) — initial seed, optimized per-call via grid search
- β = 0.1 (trend smoothing factor) — initial seed, optimized per-call via grid search
- γ = 0.1 (seasonal smoothing factor) — initial seed, optimized per-call via grid search
- Season length (`m`) = 12 months
- **Optimization:** `optimizeHoltWinters()` performs a 125-combination grid search across α, β, γ ∈ `[0.1, 0.3, 0.5, 0.7, 0.9]` minimizing RMSE on in-sample data. The optimized parameters are used per-forecast call, not the hardcoded seeds above.

**Update equations (additive):**
```
L_t = α × (Y_t − S_{t-m}) + (1−α) × (L_{t-1} + b_{t-1})   [Level]
b_t = β × (L_t − L_{t-1}) + (1−β) × b_{t-1}                 [Trend]
S_t = γ × (Y_t − L_t) + (1−γ) × S_{t-m}                    [Seasonal index]

Prediction(t+h) = max(0, L_t + h × b_t + S_{t-m+h_mod_m})   [Additive]
```

Note the seasonal adjustment uses **subtraction** in the level/season update equations and **addition** in the prediction — this is the hallmark of the additive Holt-Winters variant, not the multiplicative one (`Y_t / S_{t-m}`).

**Data requirements:**
- Requires **at least 12 months** of historical sales data to calculate initial seasonal indices
- Uses **24 months** of data (fetched from DB) for best accuracy
- If fewer than 12 months are available: falls back to **Linear Regression**

**Linear Regression fallback:**
```
Y = a + b × X
where X = month index, Y = sales value
b = (n×ΣXY - ΣX×ΣY) / (n×ΣX² - (ΣX)²)
a = (ΣY - b×ΣX) / n
```

**Why Holt-Winters over simple moving average?** Moving averages cannot capture both trend and seasonality simultaneously. A retail business that sells more in December every year would have simple moving average underestimate December and overestimate January — Holt-Winters handles this correctly.

---

### Q9.2 — What are the risks of the Holt-Winters forecast in this implementation and how could it be improved?

**Answer:**

**Implemented improvements (as of May 2026):**

1. **Parameter optimization — DONE:** `optimizeHoltWinters()` performs a 125-combination grid search across α, β, γ ∈ `[0.1, 0.3, 0.5, 0.7, 0.9]`, minimizing RMSE on in-sample data. The optimized parameters are used per-forecast call, not hardcoded. MAPE is calculated and surfaced in the dashboard forecast card.

2. **MAPE metric — DONE:** Both the Holt-Winters path and the linear regression path calculate and return MAPE. Users see it in the dashboard forecast card so they can judge forecast reliability.

**Remaining risks:**

1. **Grid search resolution:** The optimizer uses 5-step resolution (0.1 increments). Optimal parameters may fall between these steps. Nelder-Mead continuous optimization or Bayesian optimization would find more precise parameters at some computational cost.

2. **No outlier detection:** Unusual months (COVID lockdowns, one-time bulk orders) distort seasonal indices and trend. Outlier-robust initialization (e.g., using median instead of mean for seasonal indices) would help.

3. **Initialization sensitivity:** Holt-Winters forecast quality depends heavily on how L₀, b₀, and S₀ are initialized. Poor initialization requires many periods to stabilize.

4. **No prediction intervals:** The system returns point forecasts only. A production-grade system should return confidence intervals (e.g., "forecast $10,000 ± $1,500") so users understand forecast uncertainty.

5. **24-month window is hardcoded:** The historical window is `subMonths(24)`. Some seasonal businesses need 36+ months to capture multi-year patterns reliably.

**A strong candidate answer notes:** The grid search (item 1) is already implemented, so "add grid search" is no longer a valid improvement suggestion — the interviewer is testing whether the candidate has actually read the code or is reciting generic Holt-Winters advice.

---

## 10. Authentication & Authorization

---

### Q10.1 — This system uses both Fortify and Sanctum. What does each handle and why are both needed?

**Answer:**

**Laravel Fortify** handles **web authentication:**
- Login with email/password (session-based)
- Registration
- Email verification
- Password reset
- Password confirmation
- Two-factor authentication (optional)

Fortify is a backend authentication implementation — it provides the authentication logic but no UI. The UI (login form, register form) is provided by custom Blade views.

**Custom Fortify response classes** override the default redirect behavior:
- `CustomLoginResponse` — redirects to the tenant dashboard after login
- `CustomRegisterResponse` — redirects to the setup wizard or dashboard
- `LogoutResponse` — redirects to the tenant's login page (not a generic URL)
- `PasswordResetResponse`, `VerifyEmailResponse` — similarly customized

**Laravel Sanctum** handles **API token authentication:**
- API clients (mobile apps, third-party integrations) authenticate with Bearer tokens
- Tokens are created via `POST /api/login` (public); token refresh via `POST /api/v1/refresh-token`
- The REST API is deliberately scoped to essential endpoints (auth, CMS, payment/webhooks, plan lookup, contact) — see `docs/TECHNICAL_SPECIFICATION.md` §5.2; four endpoints require `auth:sanctum` (`POST /api/payment/confirm`, `POST /api/v1/refresh-token`, `GET /api/v1/plans`, `GET /api/v1/plan/current`). Public plan lookup for the marketing site is served by `GET /api/plans` (`Api/PlanController`, no auth)
- Per-route rate limits are applied (e.g. `throttle:api` on the api group, `throttle:3,60` on registration, `throttle:5,60` on `/api/contact`)

**Why both?** Web users use session cookies (more secure for browser-based access — no token exposure in JavaScript). API clients use tokens (stateless, cross-origin compatible). Using a single auth mechanism for both would require either exposing tokens to the browser (XSS risk) or requiring cookies for APIs (CORS complexity).

---

### Q10.2 — Explain the RBAC system. How are roles and permissions structured?

**Answer:**

The system uses **`spatie/laravel-permission`** with a custom `Role` model that extends Spatie's.

**Structure:**
- There are **101 permissions** defined (the exact list is seeded via `PermissionSeeder`) — verified against both the seeder array and the live `permissions` table (DB count = 101; earlier docs citing 112 or 79 were stale)
- Permissions follow the pattern: `{action}-{resource}` (e.g., `create-products`, `view-accounting`, `delete-users`, `manage-roles`)
- Roles are created per-tenant and assigned permissions
- Users are assigned roles

**Default roles (seeded on tenant creation):**
- `superuser` — all 101 permissions within the tenant scope (note: this is a tenant-level Spatie role, NOT the same as the `SuperAdmin` model which is a separate platform-level entity)
- `staff` — limited permissions (view + basic CRUD, including `view-hr`)
- `pos` — POS-focused permissions (including `access-pos`)

There is **no** Manager, Accountant, or HR role seeded — `RoleSeeder`/`RolePermissionSeeder` create exactly `superuser`, `staff`, and `pos`. Permission sets are assigned per-role; any further granularity is created via custom role management (available to Enterprise plan tenants via permission-based access control).

**Permission checking:**
```php
// In middleware
Route::get('/users')->middleware('permission:view-users');

// In controller
$this->authorize('create-sales');

// In Blade
@can('delete-products')
  <button>Delete</button>
@endcan
```

**Key design decision:** Roles are per-tenant (they have `tenant_id` via the custom `Role` model extending `BelongsToTenant`). This means each tenant has their own role/permission set independent of other tenants.

---

## 11. Security Implementation

---

### Q11.1 — List all the security headers set by `SecurityHeaders` middleware and explain the purpose of each.

**Answer:**

```php
X-Frame-Options: SAMEORIGIN
```
Prevents clickjacking attacks — the site cannot be embedded in an `<iframe>` on a different domain.

```php
X-Content-Type-Options: nosniff
```
Prevents MIME type sniffing — browsers must use the declared `Content-Type`, not guess it. Prevents certain XSS attacks where an attacker uploads a file that browsers would execute as JavaScript.

```php
X-XSS-Protection: 1; mode=block
```
Enables the browser's built-in XSS filter (legacy header — modern browsers use CSP instead, but this provides defense-in-depth for older browsers).

```php
Referrer-Policy: strict-origin-when-cross-origin
```
Controls how much referrer information is sent with requests. Cross-origin requests only include the origin (no path/query string), preventing sensitive URL parameters from leaking to third parties.

```php
Permissions-Policy: geolocation=(), microphone=(), camera=()
```
Disables access to geolocation, microphone, and camera — APIs that the application has no business needing and that could be exploited by XSS.

```php
Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$nonce}' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://app.midtrans.com ...; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com ...; ...
```
The most powerful header — defines which sources are allowed for scripts, styles, fonts, images, and connections. The Midtrans payment domains are whitelisted in `frame-src` and `connect-src` since the POS payment flow embeds Midtrans forms.

**Per-request nonces (implemented May 2026):** `SecurityHeaders` middleware generates a cryptographically random nonce on each request via `base64_encode(random_bytes(16))` and shares it to all Blade views as `$cspNonce`. The nonce is included in the `script-src` directive. All inline `<script>` blocks in Blade use `nonce="{{ $cspNonce }}"`. In CSP Level 3 browsers, the nonce makes the `'unsafe-inline'` fallback inert for scripts.

**`'unsafe-inline'` (CSP Level 2 fallback):** Retained for browsers that do not support nonces. In CSP Level 3 compliant browsers, the presence of a nonce overrides `'unsafe-inline'`, so effectively inline scripts without the nonce are blocked on modern browsers.

**`'unsafe-eval'` (documented residual risk):** `eval()`, `new Function()`, `setTimeout(string)`, and `setInterval(string)` are permitted. This was historically an undocumented weakening; it is **now explicitly documented** with a rationale comment in `app/Http/Middleware/SecurityHeaders.php` (see ARCHITECTURE_ASSESSMENT §3.6): it is required by jQuery (`$.globalEval`, which internally calls `eval()`) and by Chart.js / ECharts for certain dynamic renderers. The directive is retained with the note that it can only be removed by replacing those libraries with CSP-compliant alternatives. A candidate who flags this and can articulate *why* it is retained (not just that it exists) is demonstrating genuine security depth.

---

### Q11.2 — How does the system prevent cross-tenant data access beyond just the Eloquent global scope?

**Answer:**

**Layer 1 — Eloquent global scope (TenantScope):**
All Eloquent queries automatically filter by `tenant_id`. This is the primary defense.

**Layer 2 — Middleware (NeedsTenant + HandleTenantFromPath):**
Every request resolves a specific tenant context. If the domain doesn't match a tenant, the request is rejected (404) before any controller logic runs.

**Layer 3 — Session domain isolation:**
`SESSION_DOMAIN=.127.0.0.1.nip.io` — sessions are scoped to the full domain including subdomain. Tenant A's session cookie at `a.127.0.0.1.nip.io` is not sent to `b.127.0.0.1.nip.io`.

**Layer 4 — Authorization checks in controllers:**
Controllers verify ownership before returning resources. For example, `SalesController::show()` fetches the sale via Eloquent (which applies tenant scope), so a tenant cannot access another tenant's sale ID even by guessing.

**Layer 5 — Raw query safety via `DB::tenantTable()` macro:**
`AppServiceProvider` registers a `DB::tenantTable(string $table)` macro that automatically appends `->where('tenant_id', $tenantId)` and throws a `RuntimeException` if called without a tenant context. Performance-critical aggregation queries (dashboard metrics, aging reports) that cannot use Eloquent now use this macro instead of bare `DB::table()`. Code review should verify any `DB::table()` call either uses `tenantTable()` or has an explicit `where('tenant_id', ...)` clause.

**Weakness:** The macro is a convention, not a compile-time guarantee. A developer who writes `DB::table('sales')->where(...)` without either the macro or a manual tenant filter will introduce a data leak. The global Eloquent scope does not protect raw queries. This remains the single most dangerous class of bug in the codebase, even with the macro in place.

---

## 12. Performance & Query Optimization

---

### Q12.1 — What N+1 query problems exist in a typical ERP like this, and how does this codebase address them?

**Answer:**

**Common N+1 patterns in ERP systems:**

1. **Sales list with customer name** — loading 100 sales and then querying `customers` for each → 101 queries
2. **Product list with category and supplier** — N+1 for each relationship
3. **Dashboard metrics** — loading all sales and computing totals in PHP instead of DB aggregation

**How invent-mag addresses them:**

**Eager loading:**
```php
// SalesController::index()
Sales::with(['customer', 'user', 'warehouse', 'items.product'])
    ->paginate(15);
```

**Disabling heavy accessors on lists:**
```php
// Prevents computed properties from running on every item in a collection
$sales->each->setAppends([]);
```
This is a Laravel trick: `setAppends([])` prevents Eloquent accessor methods from being called when serializing the model, which can invoke additional queries.

**Raw DB aggregates:**
```php
// DashboardService — uses DB::table() with SUM/COUNT instead of loading models
DB::table('sales')
    ->where('tenant_id', $tenantId)
    ->whereBetween('order_date', [$start, $end])
    ->selectRaw('SUM(total) as revenue, COUNT(*) as count')
    ->first();
```

**Database indexes** (added in dedicated migration `2026_03_11_000000`):
- `sales(customer_id)`, `sales(due_date)`, `sales(status, due_date)` — for aging reports and filtering
- `po_items(expiry_date)`, `po_items(po_id, expiry_date)` — critical for FEFO queries
- `product_warehouse(product_id, quantity)`, `(warehouse_id, quantity)` — for stock queries

**Batch stock validation before sale creation:**
```php
// Pre-load ALL product stock records in one query before processing items
$stockRecords = ProductWarehouse::whereIn('product_id', $productIds)
    ->where('warehouse_id', $warehouseId)
    ->get()
    ->keyBy('product_id');
// Then validate stock from the in-memory collection, not N individual queries
```

---

### Q12.2 — The cache and queue drivers — what are the scaling implications and what is the current configuration?

**Answer:**

**Current configuration (as of May 2026):** The config **defaults** are `database` (`config/cache.php` `CACHE_STORE`, `config/queue.php` `QUEUE_CONNECTION`). `.env.example` sets **Redis**, and production runs on Redis — so `config/` defaults alone do not tell the whole story. A candidate should distinguish the config fallback from the deployed environment.

**Why Redis matters for cache:**
- File cache works on a single server only. In a multi-node deployment, each server has its own file cache — cache invalidation on one server does NOT propagate to others, leading to stale cache bugs.
- Redis provides a shared cache across all nodes — one invalidation clears it for all servers.

**Why Redis matters for queues:**
- Database queue uses the `jobs` table in MySQL — every `Queue::push()` does an INSERT, every worker poll does a SELECT + UPDATE. Under high job volume, this creates significant database load.
- Redis queue (or Amazon SQS) handles high concurrency without database contention.

**Current assessment:** Redis as default is the correct production decision. For low-traffic single-server deployments (Railway, XAMPP), the file/database drivers were acceptable — they eliminated the operational dependency on a separate Redis instance. The switch to Redis as default means horizontal scaling is now architecturally possible.

**Interview insight:** A candidate who says "file cache is fine" without noting the multi-node limitation is missing a critical scaling consideration. A candidate who says "just use Redis" without acknowledging the operational trade-off (Redis is an additional infrastructure dependency to manage) lacks pragmatism. The ideal answer describes both the problem and the trade-off.

---

## 13. API Design & Sanctum

---

### Q13.1 — How does the API handle tenant identification? Does it use the same domain-based mechanism as the web app?

**Answer:**

This is a nuanced question. The API routes are mounted at `/api/` via `bootstrap/app.php`, wrapped in the `api` middleware group (which appends `throttle:api` + `SubstituteBindings`) with an `/api` prefix. The `api` group does **not** include the tenant-resolving middleware (`NeedsTenant`/`HandleTenantFromPath`) that the web group uses, so the public essential endpoints — auth (`/api/register`, `/api/login`, `/api/lookup-tenant`; `/api/v1/refresh-token` is Bearer-protected), CMS (`/api/cms`), plan lookup (`/api/plans` public marketing pricing; `/api/v1/plans` + `/api/v1/plan/current` Bearer-authenticated), contact (`/api/contact`), and the Midtrans webhooks — run on the API domain without tenant resolution.

However, there is a `/api/lookup-tenant` endpoint (public) that allows clients to discover a tenant's subdomain by shop name — this is used by the marketing-site login portal (and would be used by mobile apps) before authenticating.

**Authentication flow for API:**
1. Client POSTs to `/api/login` with `email` + `password`
2. Server validates credentials and returns a Sanctum `personal_access_token`
3. Client includes `Authorization: Bearer {token}` header on all subsequent requests
4. `auth:sanctum` middleware validates the token on each request. **Four routes are token-protected** (two middleware groups in `routes/api.php`): `POST /api/payment/confirm`, `POST /api/v1/refresh-token`, `GET /api/v1/plans`, and `GET /api/v1/plan/current`. The rest of the API is deliberately public (auth, CMS, marketing plan lookup via `GET /api/plans`, contact, webhooks).

**Rate limiting:** Per-route `throttle` middleware (e.g. `throttle:api` on the api group, `throttle:3,60` on registration, `throttle:10,1` on lookup-tenant, `throttle:5,60` on contact).

**API documentation:** Scribe generates OpenAPI YAML at `/public/docs/openapi.yaml` and an interactive HTML docs page at `/public/docs/`. Developers use the `php artisan scribe:generate` command to regenerate docs when APIs change.

---

### Q13.2 — What does the `HandlesApiErrors` trait do and where is it used?

**Answer:**

`HandlesApiErrors` (in `app/Traits/HandlesApiErrors.php`) is a reusable trait that standardizes API error response formatting.

**What it does:** It exposes a `handleApiException(\Throwable $e): JsonResponse` helper that maps exceptions to consistent JSON responses via a `match(true)`:

```php
trait HandlesApiErrors
{
    protected function handleApiException(\Throwable $e): JsonResponse
    {
        return match(true) {
            $e instanceof ValidationException => response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422),

            $e instanceof ModelNotFoundException => response()->json([
                'message' => 'Resource not found',
            ], 404),

            $e instanceof PlanLimitExceededException => response()->json([
                'message' => $e->getMessage(),
                'upgrade_required' => true,
            ], 403),

            default => response()->json([
                'message' => 'An error occurred',
            ], 500),
        };
    }
}
```

**Honest caveat (verified against the code):** `HandlesApiErrors` is currently **not used by any controller** — a grep for the trait finds only the trait file itself. API error handling in this codebase is centralized in the **global exception handler** in `bootstrap/app.php` (`shouldRenderJsonWhen` forces JSON for `/api/*` requests, plus explicit `render` callbacks for `ValidationException`, `ModelNotFoundException`, `NoCurrentTenant`, `ThrottleRequestsException`, etc.). The trait is available as a per-controller customization tool but was superseded by the global handler. A strong candidate will note the discrepancy rather than claim the trait is "applied to API controllers."

---

## 14. Service Layer Pattern

---

### Q14.1 — Why is there a dedicated `SalesService` instead of putting logic in `SalesController`? Give a concrete example of why this matters.

**Answer:**

**The Service Layer separates business logic from HTTP concerns.**

If `SalesController` contained all the logic:
1. **The POS flow also creates sales** (`POSController` + `PosService`) — with the logic in `SalesController`, there would be duplication or the POS controller would call the web controller (wrong)
2. **Console commands** (e.g., `ResetDemoTenant`) need to create sales without HTTP context — impossible if logic is in a controller
3. **Testing** — testing a controller requires an HTTP request context (kernel, middleware, session). Testing a service is just `new SalesService()` with mocked dependencies
4. **API controllers** — the `Api/SalesController` (if it exists) can reuse `SalesService` without duplicating logic

**Concrete example:** When a sale is created, `SalesService::createSale()` runs entirely inside a `DB::transaction()`:
1. Decodes and validates the product JSON payload (throws on empty/invalid data)
2. Validates stock availability across warehouses — on a shortage it deletes the just-created `sales` record and throws with the affected product names
3. Applies FEFO batch deduction (`po_items` ordered by `expiry_date ASC`) and computes COGS from the actual price of each consumed batch
4. Updates `product_warehouse.quantity` (decrement per line item)
5. Calculates totals (line discounts, order discount, tax/PPh, grand total)
6. Creates the `sales` header and `sales_items` line records
7. Applies customer credit if `customer_credit_amount > 0` (`applyCustomerCredit()` + status refresh)
8. Creates accounting journal entries via `AccountingService` — the sale entry, a separate PPh withholding entry, and a customer-credit-applied entry when applicable

Note: `createSale()` does **not** create payment records — payments are recorded separately via `SalesService::addPayment()` (and the POS flow in `PosService`), which is what keeps a single service method focused on one responsibility. There is also **no** notification dispatch in the sale pipeline.

This multi-step process would be in the controller if no service layer existed — untestable, unreusable, unmaintainable.

---

### Q14.2 — What is `TransactionDTO` and how is it used?

**Answer:**

`TransactionDTO` (in `app/DTOs/TransactionDTO.php`) is a **read-model DTO used to shape transaction-list rows for views**, not a write-side accounting structure.

It is a simple class with public, dynamically-assigned properties:
- `id`, `type`, `invoice`, `customer_supplier`, `contact_info`, `date`, `amount`, `paid_amount`, `due_amount`, `status`, `view_url`, `edit_url`

```php
public function __construct(array $data)
{
    foreach ($data as $key => $value) {
        $this->$key = $value;
    }
}
```

**How it's used:** The financial transaction lists (sales + purchases combined views) build an array of these DTOs so Blade templates render a uniform row layout — invoice number, customer/supplier, amounts, outstanding balance, and pre-computed view/edit URLs — without re-deriving display data in the view layer.

**Important correction:** There is **no** `TransactionType` enum in the codebase, and `TransactionDTO` is **not** passed into `AccountingService::postEntry()` — journal lines are plain arrays with `account_name`/`account_code`/`account_id`, `type` (`'debit'`/`'credit'`), and `amount` keys (see Q7.1). A candidate who claims `TransactionDTO` is a typed accounting transaction container is describing a different system.

---

## 15. Observer Pattern & Audit Logging

---

### Q15.1 — How does `ActivityObserver` work? What is its performance impact on write operations?

**Answer:**

`ActivityObserver` is registered for **28 models** in `AppServiceProvider::registerActivityObservers()`. For each model, it listens to:
- `created` event → logs "created" with the new attributes
- `updated` event → logs "updated" with changed attributes (before/after values)
- `deleted` event → logs "deleted" with the model's data

**How it writes:**
```php
public function created(Model $model): void
{
    ActivityLog::create([
        'tenant_id' => app('currentTenant')->id,
        'user_id' => auth()->id(),
        'module' => $this->getModuleName($model),
        'action' => 'created',
        'subject_type' => get_class($model),
        'subject_id' => $model->id,
        'new_values' => $model->toArray(),
        'ip_address' => request()->ip(),
    ]);
}
```

**Performance impact:**
Every write operation on any of the 28 observed models incurs an **additional INSERT** into `activity_logs`. For a sale with 10 line items:
- 1 INSERT for `sales` → triggers 1 observer INSERT → total 2 INSERTs
- 10 INSERTs for `sales_items` → each triggers 1 observer INSERT → 10 more INSERTs
- Payments, stock updates → more observer inserts

In a high-volume scenario, this nearly **doubles write operations**. The mitigation is that `activity_logs` is an append-only table with no complex indexes — inserts are fast.

**Optimization if needed:**
- Dispatch audit log writes to the queue: `ActivityLog::dispatch()` → `ActivityLog::create()` in a queued job
- But this means audit logs have eventual consistency (brief delay before they appear)

---

### Q15.2 — Why is `CustomerCreditObserver` needed? What does it observe?

**Answer:**

`CustomerCreditObserver` maintains the `customer_credits.available_amount` in sync with `used_amount`.

**The problem:** `customer_credits` has three amount columns:
- `amount` — total credit issued
- `used_amount` — how much has been applied to sales
- `available_amount` — should always equal `amount - used_amount`

`available_amount` is a **derived value** — it can always be computed from the other two. But storing it as a column enables efficient queries:
```sql
SELECT * FROM customer_credits WHERE available_amount > 0 AND status = 'active'
```
Without the `available_amount` column, this query requires a subquery or computed column.

**The observer ensures consistency:** On `creating` it seeds `available_amount` for a new credit; on `updating` it recomputes `available_amount` whenever `amount` or `used_amount` changes (the `updated()`/`created()` hooks are intentionally empty):

```php
public function creating(CustomerCredit $customerCredit): void
{
    if (! isset($customerCredit->available_amount)) {
        $customerCredit->available_amount = $customerCredit->amount - ($customerCredit->used_amount ?? 0);
    }
}

public function updating(CustomerCredit $customerCredit): void
{
    // Only update available_amount if amount or used_amount changed
    if ($customerCredit->isDirty(['amount', 'used_amount'])) {
        $customerCredit->available_amount = $customerCredit->amount - $customerCredit->used_amount;
    }
}
```

Because `available_amount` is set during the `updating` lifecycle hook, the change is written in the **same UPDATE** as the `used_amount` change — no separate write, no recursion risk (unlike an `updated()` + `updateQuietly()` approach).

---

## 16. Customer Credit System

---

### Q16.1 — Explain the full lifecycle of a customer credit, from creation to consumption.

**Answer:**

**Creation triggers:**
1. **Sales return** — in `SalesReturnService`, a `customer_credit` is created for the refund amount when the return is marked `Completed` and the original sale was actually paid (`$wasPaid`, derived from the real payment state, not the stored status). The credit links back via `sales_return_id`, and the return stores the resulting `customer_credit_id`.

There is currently **no** manual/standalone credit-issuing endpoint or UI — sales returns are the only source of `CustomerCredit` records (`CustomerCredit::create` appears only in `SalesReturnService`).

**`customer_credits` columns:**
- `customer_id` — the credited customer
- `amount` — original credit value
- `used_amount` — amount applied to sales (starts at 0)
- `available_amount` — `amount - used_amount` (maintained by observer)
- `reason` — free text explanation
- `status` — `active`, `used` (`expired` is a conceptual value; see the transition note below — code never writes it)
- `expires_at` — optional expiry date

**Consumption (FIFO):** When a new sale is being created and the customer has credit, `SalesService::applyCustomerCredit()` (and `useCustomerCredit()` when a payment is received — same loop, so no separate `CustomerCreditService` class) applies credits oldest-first:

```php
// SalesService::useCustomerCredit() uses FIFO — oldest credits first
$credits = CustomerCredit::forCustomer($customerId)->active()->orderBy('created_at', 'asc')->get();

$remainingAmount = $amount;

foreach ($credits as $credit) {
    if ($remainingAmount <= 0) {
        break;
    }

    $availableOnThisCredit = $credit->available_amount;
    $creditToUse = min($availableOnThisCredit, $remainingAmount);

    if ($creditToUse > 0) {
        $credit->increment('used_amount', $creditToUse);
        $credit->decrement('available_amount', $creditToUse);
        $remainingAmount -= $creditToUse;

        // Check if credit is fully used
        if ($credit->used_amount >= $credit->amount) {
            $credit->update(['status' => 'used']);
        }
    }
}
```

The `active()` scope is what enforces expiry at query time: `status = 'active'`, `expires_at IS NULL OR expires_at >= today`, and `amount > used_amount`.

**Status transitions:**
- `active` → `used` — set by the consumption loop when `used_amount >= amount` (i.e., fully consumed)
- expiry is **not** a stored transition — no code writes `status = 'expired'` and there is **no scheduled command** for it. Expired credits are simply excluded at consumption/aggregation time by the `active()` scope (`expires_at >= today`).

**Accounting impact:** When credit is applied to a sale:
```
DEBIT:  Customer Credit Liability   $300  [reduce the liability]
CREDIT: Accounts Receivable         $300  [reduce what customer owes]
```

---

## 17. CRM & AR/AP Aging

---

### Q17.1 — How are AR/AP aging buckets calculated in `DashboardService`?

**Answer:**

Accounts Receivable (AR) aging shows how long outstanding invoices have been unpaid, categorized into time buckets relative to their `due_date`.

**Aging buckets** (verified in `DashboardService::getAgingData()` / the shared aging query — `$diff` = days overdue = `DATEDIFF(NOW(), due_date)`):
| Bucket | Definition |
|---|---|
| Current (`current_amount`) | `diff < 0` (not yet due) |
| 1-30 days (`overdue_1_30`) | `diff >= 0 AND diff <= 30` |
| 31-60 days (`overdue_31_60`) | `diff > 30 AND diff <= 60` |
| 61-90 days (`overdue_61_90`) | `diff > 60 AND diff <= 90` |
| 90+ days (`overdue_90_plus`) | `diff > 90` |
| Total overdue | `diff >= 0` |

**Query approach:**
```php
// $diffSql is 'julianday('now') - julianday(due_date)' on SQLite,
// 'DATEDIFF(NOW(), due_date)' on MySQL
$agingData = DB::table('sales')
    ->where('tenant_id', $tenantId)
    ->whereIn('status', ['Unpaid', 'Partial'])  // only outstanding
    ->whereNotNull('due_date')
    ->selectRaw("SUM(CASE WHEN $diffSql < 0 THEN total ELSE 0 END) as current_amount")
    ->selectRaw("SUM(CASE WHEN $diffSql >= 0 AND $diffSql <= 30 THEN total ELSE 0 END) as overdue_1_30")
    ->selectRaw("SUM(CASE WHEN $diffSql > 30 AND $diffSql <= 60 THEN total ELSE 0 END) as overdue_31_60")
    ->selectRaw("SUM(CASE WHEN $diffSql > 60 AND $diffSql <= 90 THEN total ELSE 0 END) as overdue_61_90")
    ->selectRaw("SUM(CASE WHEN $diffSql > 90 THEN total ELSE 0 END) as overdue_90_plus")
    ->selectRaw("SUM(CASE WHEN $diffSql >= 0 THEN total ELSE 0 END) as total_overdue")
    ->first();
```

**Important implementation detail:** the aging buckets sum `sales.total` directly — there is **no** subtraction of `payments.amount` or `customer_credit_used` in the aging query. An invoice appears in a bucket by its full `total` for as long as its `status` is `Unpaid`/`Partial` (i.e., until a payment flips the stored `status` to `Paid`). The `(status, due_date)` indexes on `sales` and `po` are specifically for making these queries fast.

**AP aging** is identical but queries the `po` (purchase orders) table instead of `sales`.

**Business importance:** AR aging is one of the most critical management reports in an ERP. High balances in the 90+ bucket indicate bad debt risk. The indexes on `(status, due_date)` on both `sales` and `po` tables are specifically for making these queries fast.

---

## 18. Middleware Chain

---

### Q18.1 — In what order does the middleware chain execute for a standard authenticated web request, and what does each middleware do?

**Answer:**

For a request to `GET /accounting/journal-entries` (an authenticated, plan-gated page):

**Global middleware (always runs):**
1. `TrustProxies` — handles reverse proxy headers (X-Forwarded-For, etc.)
2. `HandleCors` — CORS headers for API compatibility
3. `PreventRequestsDuringMaintenance` — returns 503 if maintenance mode is on
4. `ValidatePostSize` — rejects oversized POST bodies
5. `TrimStrings` — trims whitespace from request inputs
6. `ConvertEmptyStringsToNull` — normalizes empty strings

**Web middleware group:**
7. `EncryptCookies` — encrypts/decrypts cookie values
8. `AddQueuedCookiesToResponse` — attaches queued cookies to response
9. `StartSession` — initializes the session
10. `ShareErrorsFromSession` — makes `$errors` available in Blade
11. `VerifyCsrfToken` — validates CSRF token on POST/PUT/PATCH/DELETE
12. `SubstituteBindings` — resolves route model bindings

**Custom web middleware (wired in `bootstrap/app.php`):**
- **Prepended first:** `SetSessionCookieName` — sets the session cookie name per-tenant (runs before the session starts so the right cookie is used)
- **Appended (in order):**
  - `NeedsTenant` — verifies tenant context exists (throws `NoCurrentTenant` if not)
  - `HandleTenantFromPath` — resolves and sets current tenant
  - `SetLocale` — sets application locale from user/session preferences
  - `DebugModeMiddleware` — enables/disables Laravel Telescope or debug bar
  - `SecurityHeaders` — sets all security HTTP headers on the response
  - `TurboMiddleware` — handles Hotwire Turbo request headers
  - `CaptureImpersonation` — captures the impersonated admin identity from session

**Route-specific middleware:**
- `auth` (Fortify) — redirects to login if not authenticated
- `verified` — redirects to email verification if not verified
- `subscription.active` — redirects to plan upgrade if subscription expired
- `plan.feature:accounting` — redirects to upgrade if plan lacks `accounting` feature
- `permission:view-journal` — 403 if user lacks the permission (note: the actual permission slug is `view-journal`, not `view-journal-entries`)

**Controller executes.**

---

## 19. Frontend Architecture

---

### Q19.1 — How does Hotwire Turbo change the traditional request/response cycle? What are the implications for Alpine.js state?

**Answer:**

**Traditional (without Turbo):**
```
User clicks link → Full HTTP request → Full HTML page returned → Browser replaces entire DOM
```
This destroys all JavaScript state (Alpine.js component state, scroll position, etc.).

**With Turbo Drive:**
```
User clicks link → Turbo intercepts click → Background AJAX request → 
New HTML returned → Turbo merges <body> into current DOM, preserves <head>
```
The page update is partial — only the `<body>` is replaced. The browser does not do a full reload. This is fast (no re-parsing CSS/JS) and feels SPA-like.

**Turbo Frames:** Even more targeted updates — only a specific `<turbo-frame id="...">` is replaced, not the whole body.

**Implications for Alpine.js:**
- Alpine.js components inside a Turbo Frame that gets replaced will be **destroyed and re-initialized** — transient state is lost
- Alpine.js components outside the frame (e.g., a sidebar notification badge) **retain state** across frame updates
- **Problem:** If a modal is opened and Turbo replaces the page content, the modal state could be inconsistent
- **Solution:** Use `x-data` on persistent elements outside Turbo Frames for state that must survive navigation

**`@persist` attribute (Turbo):** Can mark elements to be persisted across full Turbo Drive navigations, preventing re-initialization of Alpine components.

---

### Q19.2 — How is Vite configured for this project and what does it compile?

**Answer:**

Vite (`vite.config.js`) is configured with the `laravel-vite-plugin`:

**Input files compiled:** the `laravel` plugin's `input` array lists **65 entries** — `resources/css/app.css` plus one JS entry per admin area (`resources/js/app.js`, `resources/js/admin/*.js` for accounting, POS, products, customers, payroll, journal entries, backup, and so on). Each admin page references only the entry it needs via `@vite([...])`, so unused page bundles are not loaded.

**Output:** Versioned files in `public/build/` with a `manifest.json` for cache-busting.

**In Blade templates:**
```blade
@vite(['resources/css/app.css', 'resources/js/app.js'])
```
This directive reads `manifest.json` in production and injects the correct versioned URLs. In development, it injects the Vite HMR script instead.

**Static assets (not compiled by Vite):**
- Tabler UI CSS/JS → `/public/tabler/` — included directly in Blade layouts via `<link>` tags
- This means Tabler is not tree-shaken or optimized by Vite

**Vitest** is configured for testing JavaScript utility functions. It uses the same Vite config, so tests run in the same environment as the application.

---

## 20. DevOps & Deployment

---

### Q20.1 — Describe the CI/CD pipeline. What does the GitHub Actions workflow check?

**Answer:**

**`.github/workflows/ci.yml`** runs on every push/PR to `main` and `develop`:

1. **Setup PHP 8.2** (`shivammathur/setup-php` with bcmath, ctype, fileinfo, mbstring, openssl, pdo, tokenizer, xml)
2. **Copy `.env`** from `.env.example`
3. **Composer install** (`ramsey/composer-install`, locked versions, `--ignore-platform-reqs`)
4. **Generate app key** (`php artisan key:generate`)
5. **Setup Node 20 + `npm install`**
6. **Build frontend assets** (`npm run build`)
7. **Run PHP tests** — `php artisan test`
8. **Prepare + upload artifact** — rsyncs the project (excluding `.git`, `.github`, `node_modules`, `.env`, storage runtime dirs) into a `build-artifact` uploaded as a CI artifact

Note: `ci.yml` does **not** run PHPStan/lint or `npx vitest run` — those are not steps in the current workflow; it runs the PHP test suite and builds assets.

**`.github/workflows/deploy.yml`** runs on **version tags (`v*`)** and via `workflow_dispatch`, with two jobs:
1. **Build & push image** — checks out, logs into **GHCR** (`ghcr.io`, `GITHUB_TOKEN`), extracts semver/latest tags, builds the Docker image and pushes it to the container registry
2. **Deploy to server** — `appleboy/ssh-action` connects to the VPS (`SERVER_HOST`/`SERVER_USER`/`SSH_PRIVATE_KEY` secrets) and runs `docker-compose pull`, `docker-compose up -d`, `php artisan migrate --force`, and `optimize:clear` + `config:cache`/`route:cache`/`view:cache`

**Current production is Railway (PaaS).** The GHCR + VPS pipeline is the repository's release/deployment automation path (self-hosted deployment target), and Railway remains the live production host. See `docs/DEPLOYMENT_CHECKLIST.md` for the current release notes.

**Docker support:**
- `Dockerfile` builds the PHP-FPM + Nginx image
- `docker/nginx.conf` configures the web server
- `docker-compose.yml` orchestrates the app container + MySQL + (optionally) Redis

**Release deploy specifics:** the current production release (Aug 2026 API/contact) includes a **breaking change** — the Midtrans webhook URL must move from `/webhooks/midtrans` to `/api/webhooks/midtrans`, and `MAIL_*`/`CONTACT_EMAIL` env must be verified for the contact form. See `docs/DEPLOYMENT_CHECKLIST.md`.

---

### Q20.2 — What does the `BackupTenant` Artisan command do and how does it work?

**Answer:**

`BackupTenant` (`app/Console/Commands/BackupTenant.php`, signature `backup:tenant {tenant_id} {type} {filename}`) is a **custom backup implementation** — it does **not** use `spatie/laravel-backup` (that package is installed but unused; there is no spatie backup config for this command).

**Process:**
1. Reads the MySQL connection config and the `mysqldump` binary path (`database.connections.mysql.dump.dump_binary_path`, defaulting to `C:/xampp/mysql/bin`)
2. Builds a temp directory and calls `mysqldump` per-table via Symfony `Process`, with:
   - `--where "tenant_id={id}"` on every tenant-scoped table (35 tables listed in `$tablesWithTenantId`)
   - A `--where "child_fk IN (SELECT id FROM parent WHERE tenant_id={id})"` strategy for child tables without a `tenant_id` (e.g. `stock_opname_items` via `stock_opnames`)
   - `--single-transaction --skip-lock-tables --quick` for a consistent, non-blocking dump
3. Concatenates all table dumps into one `backup.sql` wrapped in `SET FOREIGN_KEY_CHECKS = 0/1`
4. Zips it into `storage/app/backups/{filename}` with `ZipArchive`

**What it does NOT do (correcting the older doc):**
- No encryption — the zip is **not** password-protected, and the `backup_password` / `backup_encrypted` columns on `tenants` are **not** used by this command
- No notification sent to the tenant admin on completion
- No use of `spatie/laravel-backup`

The signature still takes a `type` argument (`db_only` or `full`) but the current `handle()` implementation dumps the same tenant-scoped table set regardless of `type`. A candidate who claims this uses spatie/laravel-backup or encrypts per-tenant backups is describing the docs rather than the code.

---

## 21. Quotation Module & Billing Consistency (v1.9)

---

### Q21.1 — The v1.9 Quotation module introduces a three-entity pipeline: Opportunity → Quotation → Sales Order. What are the two conversion paths and how does the data model support both?

**Answer:**

The `SalesOpportunity` model supports two distinct conversion paths:

**Path A — Direct conversion (informal):**
```
SalesOpportunity → Sales (via sales_id FK on opportunities)
```
Used when a deal is won without a formal quote — the opportunity is converted directly to a sales order. `SalesOpportunity::sales_id` is populated, `status` is set to `converted`.

**Path B — Formal quoting:**
```
SalesOpportunity → Quotation → Sales (via quotation.converted_to_sales_id on conversion)
```
Used when a formal PDF quote is issued. The `SalesOpportunity::quotations()` hasMany relationship links opportunities to multiple quotations (e.g., a revised quote after negotiation). When a quotation status transitions to `Accepted` and is "Converted" (`QuotationController::convert()` — only `accepted` quotations qualify), a `Sales` record is created and the quotation is set to `status = converted` with `converted_to_sales_id` pointing at the new sales order (`Quotation::convertedToSales()` relationship).

**Why both paths are needed:** Some deals are won verbally and then billed directly (no document trail needed). Others require a formal written quote for procurement approval on the customer's side. Forcing all deals through the quotation path would add unnecessary friction to fast-moving sales. Both paths eventually produce a `Sales` record, which is the canonical financial document.

**Data integrity:** The `SalesOpportunityItem` model uses `withDefault(['name' => 'Unknown Product', 'selling_price' => 0])` on its `product()` relationship. This is a defensive pattern — if a product is deleted after an opportunity was created, the system returns a graceful default instead of a null-pointer failure.

---

### Q21.2 — There was an inconsistency between how the billing period was initialized and how `BillingService` computed it. Describe it and explain the potential impact. Is it still open?

**Answer:**

**The inconsistency (resolved June 2026):**

`BillingService::getCurrentBillingPeriod(?Plan $plan)` computes a **rolling window from today** using the plan's billing cycle length (default **30 days** when no plan/`getBillingPeriodDays()` falls back):
```php
'start' => now()->toDateString(),
'end'   => now()->copy()->addDays($days)->toDateString(),
```
`getNextBillingPeriod()` does the same relative to the next cycle start.

Historically, the plan-assignment flow that initialized a tenant's `billing_period_start`/`billing_period_end` used **calendar-month** dates (`startOfMonth`/`endOfMonth`). Since the initialization logic now lives in `BillingService::initializeBillingPeriodIfEmpty()` — which simply calls `getCurrentBillingPeriod($plan)` — both paths are **consistent rolling-30-day** computation, matching the v1.7 changelog commitment. `Tenant::getCurrentBillingPeriod()` reads the stored start/end and derives `days_remaining`.

**When this mattered:** on a mid-cycle plan assignment (upgrade/downgrade), a calendar-month `billing_period_end` (e.g. June 30 for a June 12 upgrade) diverged from the rolling computation (`July 12`). The `ProcessScheduledPlanChanges` command schedules downgrades via `pending_plan_effective_at` derived from `getNextBillingPeriod()`, so the effective date and the stored `billing_period_end` could disagree — the UI "renews on [date]" could differ from when plan-lifecycle commands actually fire.

**Why this is a senior-level finding:** the inconsistency was subtle — no crash or obvious error, just a discrepancy between displayed renewal date and the actual period lifecycle.

---

## 22. Super Admin Panel (SaaS Management)

---

### Q22.1 — What is the difference between the `SuperAdmin` model and the tenant-level "superuser" role? Why are both needed?

**Answer:**

This is a critical architectural distinction that candidates often confuse.

**`SuperAdmin` model (`app/Models/SuperAdmin.php`):**
- Lives in the `super_admins` table (NOT the `users` table)
- Has its own authentication guard (`super_admin`) and provider (`super_admins`) in `config/auth.php`
- Runs on the **root domain** (`invent-mag.web.id`) — not on tenant subdomains
- Has **no tenant context** — it manages tenants, plans, subscriptions, and billing across the entire platform
- Has no Spatie roles — full access by design (there is only one or very few super admin users)
- Uses `PlatformCurrencySetting` only as a display preference for its own overview screens (via `CurrencyHelper::formatPlatform()`) — it is not a separate currency system and never affects tenant formatting
- Routes in `routes/superadmin.php`, protected by `auth:super_admin` middleware

**Tenant-level "superuser" role (`spatie/laravel-permission`):**
- A Spatie `Role` assigned to `User` records within a tenant
- Lives in the `roles` table with `guard_name = 'web'`
- Has all 101 permissions within the tenant scope
- Runs on **tenant subdomains** (`tenant.invent-mag.web.id`)
- Uses `CurrencySetting` for monetary formatting (per-tenant)
- Routes in `routes/admin/*.php`, protected by `auth`, `verified`, `subscription.active` middleware

**Why both are needed:** A SaaS platform needs two levels of access:
1. **Platform operator** — manages the business side (tenants, plans, billing, system config). This is the `SuperAdmin`.
2. **Tenant administrator** — manages their own business data (sales, purchases, accounting, HR). This is the `superuser` role on the `User` model.

Collapsing these into one model would either (a) expose platform management to tenant users, or (b) require complex permission checks to prevent tenants from accessing other tenants' data. The separate model + guard approach eliminates both risks at the framework level.

---

### Q22.2 — How does the super admin panel authenticate users? What middleware protects it?

**Answer:**

The super admin panel uses Laravel's built-in auth middleware with a dedicated guard:

```php
// routes/superadmin.php
Route::middleware('guest:super_admin')->group(function () {
    Route::get('/login', [LoginController::class, 'create']);
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth:super_admin')->group(function () {
    // All super admin routes here
});
```

The `auth:super_admin` middleware tells Laravel to resolve the user from the `super_admin` guard (which uses the `super_admins` Eloquent provider → `SuperAdmin` model). This is a completely separate authentication path from the tenant `web` guard.

**Rate limiting:** The `LoginController::store()` method uses `RateLimiter::tooManyAttempts()` with a composite key of `superadmin_login|{email}|{ip}`, allowing 5 attempts per minute. This is independent from the tenant login rate limiter.

**Session isolation:** The `SESSION_DOMAIN` setting ensures super admin sessions (on the root domain) are never sent to tenant subdomains.

---

### Q22.3 — How does the currency setting system work?

**Answer:**

There is **one currency setting system**: the tenant `CurrencySetting`.

**`CurrencySetting` (tenant — the single system):**
- Model: `App\Models\CurrencySetting` — uses `BelongsToTenant` trait
- Table: `currency_settings` — **one row per tenant**
- Used by: `CurrencyHelper::format($amount)` — drives every monetary display in the tenant admin panel (invoice totals, POS receipts, sales amounts, accounting reports)
- Configurable at: Tenant admin → Settings → Currency

**Super admin display preference (not a second system):**
The super admin panel has a `PlatformCurrencySetting` model (`platform_currency_settings`, one global row, seeded USD) used only by `CurrencyHelper::formatPlatform($amount)` on super-admin overview screens (e.g., the payment-overview dashboard). It is a **display preference for the platform's own screens** — not a currency system that affects tenants.

**Key property:** Tenant transactions are always displayed in the tenant's own currency via `CurrencySetting`; the super-admin display preference has **zero impact** on tenant formatting. The `currency_code` column on `plans` and `subscription_orders` records the currency at time of creation, preserving historical accuracy.

---

### Q22.4 — What does the super admin panel manage? List all major functional areas.

**Answer:**

The super admin panel (16 controllers, 30 Blade views, ~55 routes) manages:

1. **Tenants** — list, view, toggle active/inactive, edit domain, impersonate (implemented: `POST /tenants/{tenant}/impersonate` + `CaptureImpersonation` middleware)
2. **Plans** — full CRUD with feature toggles, pricing, usage limits, trial days
3. **Subscriptions** — terminate, reactivate, view stats (active/trialing/expired/terminated)
4. **Features** — assign feature slugs per plan tier via matrix view
5. **Billing** — subscription order listing, detail, void
6. **Payment Overview** — revenue statistics and gateway breakdown
7. **Transactions** — searchable/filterable transaction history
8. **Pending Payments** — approve or reject incoming subscription payments
9. **Invoices** — paid invoice listing and detail
10. **Backups** — system-wide and per-tenant backup, restore, download, delete
11. **Permissions** — view-only listing of all tenant roles and permissions
12. **Audit Log** — platform-level activity log with module/action filtering
13. **Settings** — .env editor, user preferences, platform currency config
14. **CMS** — landing page content editor (hero, features, pricing, contact)

All operations are audit-logged via the **custom** `ActivityLog`/`AccountingAuditLog` system (see Q15.1) — note this is NOT Spatie Activity Log: `spatie/laravel-activitylog` is not installed, there is no `activity()` helper, and `activity_logs` is written by `ActivityLogService`.

---

## 23. Recent Production Incidents & Decisions (Aug 2026)

---

### Q23.1 — There was a production bug where the public API returned 404s for `/api/register` and `/api/lookup-tenant` on the deployed site, even though the routes existed locally. What was the root cause and the fix?

**Answer:**

The routes were registered **without the `/api/` prefix and without the `api` middleware group** in production. In `bootstrap/app.php`, Laravel 11's automatic `/api` prefixing is applied by the framework's default route registration — but this project used a custom `using:` closure:

```php
// BEFORE (buggy)
Route::using(base_path('routes/api.php')); // custom closure bypasses the automatic
                                           // '/api' prefix + 'api' middleware group
```

That bypassed two framework conventions at once:

1. **No `/api` prefix** — every route in `routes/api.php` was registered at the domain root (e.g. `POST /register` instead of `POST /api/register`). The FE marketing site calls `POST /api/register`, so production requests 404'd.
2. **No `api` middleware group** — `throttle:api`, `SubstituteBindings`, and `EnsureFrontendRequestsAreStateful` were missing from these routes.

The diagnostic signals were Scribe generating docs with **zero routes**, and the exception handler's `$request->is('api/*')` checks never firing (because requests were not hitting `/api/*`).

**The fix (Aug 2026):**

```php
// AFTER (fixed)
Route::middleware('api')->prefix('api')->group(base_path('routes/api.php'));
```

`php artisan route:list` confirmed all routes now live under `/api/*`; Scribe regenerated docs for all 12 endpoints; tests (`MidtransWebhookTest`, `TenantLookupControllerTest`) were updated to the `/api/...` paths; full suite passed (1186 tests).

**Senior lesson:** Framework conventions are easy to bypass silently. When a custom route registration deviates from the platform default, every downstream assumption (middleware, prefixes, exception-handler path checks, doc generation) must be re-verified. A candidate who recognizes that "Scribe matching zero routes" is the first clue that routing itself is broken — rather than the docs being out of date — has good diagnostic instincts.

---

### Q23.2 — Why was the contact form moved from Web3Forms to the backend `POST /api/contact`?

**Answer:**

The marketing-site contact form (`invent-mag-fe` Astro pages) originally posted to **Web3Forms** (`https://api.web3forms.com/submit`) with a client-side `access_key`. The backend `Api\ContactController` existed but was **dead code** — it referenced a non-existent `App\Mail\ContactFormMail` and was never called.

**Why the change was made:**
1. **Exposed access key** — the Web3Forms access key was hardcoded in the public JS bundle. A client-side key is public by design, but it means anyone can spam the account's submission quota.
2. **Third-party dependency with no value** — the FE already had a backend with an email pipeline (Resend); routing contact through a paid third party was unnecessary.
3. **Dead backend code** — the endpoint existed in the API but was unreachable/unwired.

**The implementation (`app/Http/Controllers/Api/ContactController.php`, `app/Mail/ContactFormMail.php`, `resources/views/emails/contact-form.blade.php`):**
- Validates `name`, `email`, `message`, and a `topic` enum (`sales|support|demo|partnership|other`)
- A **honeypot** field (`botcheck`) — if submitted, the handler returns a fake success without sending mail, silently discarding bots
- Sends a **queued** mailable (`ShouldQueue`) to `config('app.contact_email')` (`env('CONTACT_EMAIL')`) — queuing keeps the request fast and retries SMTP failures
- Route `POST /api/contact` with `throttle:5,60` (5 attempts/minute/IP)
- Tests use `Mail::assertQueued` (not `assertSent`) because the mailable is queued — a subtle gotcha

**Why queued?** SMTP is slow and can fail; blocking the HTTP response on it turns a form submission into a 5-30s request and a hard failure mode. Queuing decouples the user-facing response from the delivery pipeline.

---

### Q23.3 — A secret-scanning alert flagged commit `e76c52d` (the contact-form cleanup) for a "generic high entropy secret." The commit actually *removed* the Web3Forms access key. How was this handled, and what is the correct order of operations for a leaked secret?

**Answer:**

Secret scanners flag **any commit whose content contains a high-entropy string — including deletion lines**. The commit that removed the key was itself flagged because the key UUID appeared in its diff.

**Handling order — rotate first, then purge history:**
1. **Revoke/rotate the credential** (the definitive fix). In this case the key was deleted in the Web3Forms dashboard. Because the key had shipped in a public client bundle for months, it was already compromised — history rewriting cannot un-leak it.
2. **Purge history** with `git filter-repo --replace-text '<secret>==>REDACTED'`, then re-add the remote (filter-repo strips it) and `git push --force-with-lease`. After `filter-repo`, the first `--force-with-lease` push fails with "stale info" because the remote-tracking ref was removed — run `git fetch` first, then `--force-with-lease` compares against the freshly-fetched ref.
3. **Verify** with `git log --all -S '<secret>' --oneline` (must be empty) on both the local repo and `origin/<branch>`, then dismiss the alert in the Security tab.

**Why `--force-with-lease` instead of `--force`?** `--force` blindly overwrites whatever is on the remote; `--force-with-lease` aborts if the remote has moved since your last fetch — protecting against clobbering a colleague's push. It should be the default for any force-push.

**Interview insight:** A strong candidate distinguishes between a *server credential* (rotation is non-negotiable) and a *public client-side key* (rotation still recommended, but the exposure is by design). They also know that rewriting a public repo's history is hygiene, not security — anyone who already cloned it retains the secret.

---

## 24. Indonesian Tax Compliance — PPN, e-Faktur & PPh (Implemented)

---

### Q24.1 — What is the universal tax model, and why was the separate PPN toggle removed?

**Answer:**

> "PPN is no longer a toggle. It's a property of the selected tax."

There is a single `taxes` table; a tax is categorized by two booleans — `is_ppn` (VAT/compliance) and `is_pph` (withholding). The legacy v1 design had a **separate PPN on/off switch** next to the universal tax dropdown on the Sales form, which produced five correctness problems (see `docs/PPN_EVOLUTION.md` §"Problems We Found"):

1. **Double-counting** — PPN was computed on the post-general-tax total instead of the taxable base (DPP)
2. **Faktur Pajak divergence** — `EFakturService` recalculated PPN while the Sale stored a separately client-computed `ppn_amount`
3. **Line-item DPP errors** — item DPP ignored order-level discounts
4. **`grandTotal` ignored PPN** — the accessor returned `total` without the PPN added on top
5. **No mutual exclusion** — a user could apply both a general tax *and* PPN on one invoice, which Indonesian law forbids on a single Faktur Pajak

The v2 refactor collapsed everything into the single dropdown: selecting a tax with `is_ppn = true` activates PPN compliance (Faktur Pajak, NPWP, e-Faktur); selecting a general/none tax does not. PPN and general taxes are **mutually exclusive by construction** (one `tax_id` per document). **PPh is an independent parallel dimension** — a document can carry PPN *and* PPh snapshots simultaneously, because PPh uses its own flag and its own accounts (1170 receivable / 2150 payable) and never conflicts with the exclusivity rule.

`ppn_enabled/ppn_rate/ppn_amount` and `pph_*` on `sales`/`po`/`quotations` are **denormalized snapshots** derived from the selected tax at creation time — never user input. Helpers `Sales::isPpn()`/`isPph()` **always read the stored snapshot** (`return (bool) $this->ppn_enabled;` / `$this->pph_enabled`) — they never consult the live `Tax` relationship, by design. Toggling a Tax record's flags or switching `tax_jurisdiction` **never retroactively changes past documents** (PPN_EVOLUTION.md Decision 15 — the snapshot is the source of truth for history).

---

### Q24.2 — How is Indonesian compliance gated? Walk through the layering.

**Answer:**

Compliance is a four-layer chain (PPN_EVOLUTION.md Decision 4); all layers must be true for a feature to fully function:

```
tax_jurisdiction = 'ID'    ← master switch (Tenant model)
    ↓
pkp_status = true          ← company registration status (Tenant model)
    ↓
hasFeature('ppn')          ← billing/plan gate (Professional+)
    ↓
tax.is_ppn = true          ← per-tax flag (Tax model)
    ↓
sale.isPpn()               ← derived helper on Sale model
```

- **UI visibility** is gated by `isIndonesian() && hasFeature('ppn')`; **Faktur Pajak generation additionally requires `pkp_status = true`** (`EFakturService` throws `DomainException` otherwise)
- **Navigation filtering is cosmetic only:** `NavigationServiceProvider::tenantMatchesJurisdiction()` hides e-Faktur menu items for non-Indonesian tenants, but the routes remain reachable by direct URL (Decision 12 — protects deep links and bookmarks)
- **Auto-seed on switch:** switching `tax_jurisdiction` to `'ID'` (from the Settings → Tax Region card or the e-Faktur settings page) calls `TaxService::ensureIndonesianDefaults()` — idempotent creation of `PPN 11%`, `PPH 23 (2%)`, and `PPh Final 0.5%` (Phases 1C + 1E). An existing default is never overridden.
- **Switch-off safeguard:** `Tenant::canDisableIndonesianJurisdiction()` blocks ID→null when draft Faktur Pajak exist or PPN sales have no Faktur Pajak yet. It uses `whereDoesntHave('fakturPajak')` — a prior bug queried a non-existent `sales.faktur_pajak_id` column and 500'd. Both entry points (`SettingsController` and `EFakturController`) call the same consolidated method.

---

### Q24.3 — Walk through the e-Faktur lifecycle end-to-end, from sale creation to SPT Masa PPN.

**Answer:**

1. **Sale creation — PPN recognized at creation, not at Faktur issuance (Phase 1E).** When the tenant `isIndonesian()`, the selected tax has `is_ppn = true`, and `ppn_keluaran_account_id` is configured, `SalesService::createSale()` posts the **5-leg VAT-split journal**: `Dr AR gross / Cr Revenue net / Cr PPN Keluaran / Dr COGS / Cr Inventory`. Revenue is booked **net of VAT** (this resolved the "revenue booked gross" audit caveat). If the PPN Keluaran account is missing it falls back to the legacy 4-leg gross journal — no crash. `PosService` applies the identical split for Indonesian PPN sales.
2. **Faktur Pajak generation is compliance-only** — since Phase 1E it posts **no journal** (`postPpnKeluaranJournalEntry()` was deleted). `EFakturService::generateFakturPajak()` validates `pkp_status`, `isPpn()`, and no existing FP; allocates an NSFP; computes `DPP = sale.total - sale.total_tax` and `PPN = sale.total_tax` (no independent recalculation → no FP/sale divergence); creates `FakturPajak` + `FakturPajakItem` records.
3. **Draft → submit:** the admin reviews on the e-Faktur index by tax period (masa pajak), then exports the **DJP CSV** (FK header row + OF item rows, UTF-8 BOM for Excel/Indonesian locale, amounts in **integer cents** per the DJP spec) and uploads it to the DJP desktop app — or, in Phase 2, submits via the PJAP API. Status moves to `submitted`.
4. **Cancel (draft only):** the NSFP is marked `cancelled` (returned to the pool) and any *legacy* FakturPajak-sourced PPN journal is reversed (backward compatibility only).
5. **SPT Masa PPN:** `EFakturService::getSptMasaSummary()` rolls **PPN Keluaran** (from issued Fakturs) against **PPN Masukan** (from `po.ppn_amount` where `ppn_enabled`) and returns `total_ppn_masukan`, `net_ppn_payable` (Keluaran − Masukan, floored at 0), and `ppn_credit` (Lebih Bayar when Masukan exceeds).

**PPN Masukan (input VAT) on purchases** (Phase 1D): `Dr Inventory net + Dr Input VAT (1160) / Cr AP / Cr PPh Payable`. PPN Masukan is a **debit to an asset** (1160) — it does not reduce the payable to the supplier (unlike PPh). The PO create form deliberately has **no tax fields**; input VAT is recognized at the purchase-invoice stage, following the Accurate Online / Indonesian standard (Decision 14). Sales returns reverse PPN **proportionally whenever the original sale `isPpn()`** — a Faktur Pajak is no longer required for the reversal.

---

### Q24.4 — How does the NSFP pool work? Why FIFO with `lockForUpdate()`?

**Answer:**

NSFP are DJP-allocated 17-digit serial numbers that must be used **sequentially** — reusing a number or skipping ahead is a legal violation. Under concurrent requests, two users generating Faktur Pajak simultaneously could both read the "lowest available" NSFP and produce two Fakturs with the same number. The fix is **pessimistic locking**: NSFP allocation happens inside `DB::transaction()` with `lockForUpdate()`, so only one request can hold the row at a time. A unique constraint on the `nsfp` column is belt-and-suspenders. Cancelled Fakturs return their NSFP to the pool (marked `cancelled`).

The DJP CSV export converts amounts to **integer cents** (`(int) round($amount * 100)`) to match the DJP spec exactly, and prepends a **UTF-8 BOM** because the DJP e-Faktur desktop app opens files in Microsoft Excel under an Indonesian locale, which requires the BOM for correct UTF-8 detection. A candidate who knows *why* the BOM is there — not just that it is — understands the real-world pain of DJP integration.

---

### Q24.5 — How do the PPh types work? PPh 23, PPh Final 0.5%, PPh 21, PPh 25?

**Answer:**

All withholding taxes run on the **universal `is_pph` mechanism**: a `Tax` flagged `is_pph = true` appears in the PPh dropdown on Sales, PO, POS, and Quotation forms and posts through **one shared journal path** with **different legs per side**:

- **Sale side** (`SalesService`, PPh withholding on sale): `Dr PPh Receivable (1170) / Cr Accounts Receivable` — the seller records the tax it will claim back from DJP, reducing the amount the customer owes.
- **Purchase side** (`PurchaseService`): `Cr PPh Payable (2150)` added alongside `Dr Inventory (net) + Dr Input VAT (1160) / Cr Accounts Payable` — the buyer records the withholding it must remit to DJP.

- **PPh 23 (2%)** and **PPh Final 0.5%** (PP 23/2018, as revised by PP 20/2026) are seeded. `PPh Final 0.5%` **replaced `PPH 22` in place** via backfill migration `2026_08_05_000002` — same row, renamed, rate changed — so existing `pph_tax_id` references stay valid and per-document `pph_rate`/`pph_amount` snapshots are untouched. `PPH 22` was dropped because it is narrowly applicable (goods purchases from importers) and rarely the tax an UMKM books; it remains *creatable* from the Tax page for the minority who need it.
- **PPh 21** is planned as a flat **"Payroll Tax" picker** on the payroll screen (reusing `is_pph`) — deliberately **not** a TER/PTKP/1721-II engine, since the UMKM target does not need a salary-tax engine.
- **PPh 25** is **deferred** to a manual journal entry.

**Why no per-type engines?** Every PPh type posts to the same PPh Payable (2150). Separate engines for 21/22/23/25 would add complexity without improving accounting accuracy — distinct types would land indistinguishably on the same liability account. A single reconcilable liability beats five engines producing identical ledger lines. The trade-off is documented as Caveat #4 in `docs/TAXATION_SYSTEM.md` (PPh types are not separately reportable in the ledger).

**PPN is the exception** because it is a genuinely separate VAT obligation — the VAT-split journal at creation (Q7.2) plus the e-Faktur data model, not the `is_pph` path.

---

### Q24.6 — What is planned for e-Faktur Phase 2 (PJAP API), and why is it Enterprise-gated?

**Answer:**

Phase 2 (planned, ~3-4 weeks) adds **direct DJP submission** — a "Submit ke DJP" button on the Faktur Pajak detail page, real-time approval/rejection status, and bulk month-end submission — eliminating the manual CSV download and the DJP desktop app. Phase 1 (CSV + PDF) is **Zahir parity**; Phase 2 is the differentiator.

- **PJAP choice:** **Mekari Klikpajak** (best REST docs, full sandbox, most widely used), abstracted behind an `App\Contracts\PjapInterface` contract (`submit`, `submitBatch`, `checkStatus`, `cancel`, `testConnection`) so the provider can be swapped without touching business logic. `KlikpajakService` binds per-tenant (each tenant has its own credentials).
- **Idempotency:** submissions carry an `X-Idempotency-Key` (`FP-{id}-{nsfp}`) with `retry(3)`, so a retried request can never create a duplicate Faktur; `checkStatus()` normalizes to `pending|approved|rejected`.
- **Queue isolation:** submission/status jobs dispatch to a dedicated **`tax-submissions`** queue so DJP jobs are never starved by other background work.
- **Gating:** the `efaktur_api` feature slug is **Enterprise-only** (planned, not yet live); `ppn` (Phase 1) is already on Professional & Enterprise. This matches the market split: Phase 1 is the compliance baseline for every Indonesian PKP tenant, Phase 2 is premium automation for the largest tenants.

---

## 25. Bank Reconciliation (CSV-Based)

---

### Q25.1 — Why is bank reconciliation CSV-based rather than using bank APIs (BCA/Bni/Mandiri)?

**Answer:**

Bank Reconciliation shipped in Aug 2026 as a **CSV-import-based** feature (Professional+; `bank_reconciliation` feature slug, ROADMAP §5.1 / §5.7):

- `BankStatementParser` interface + `AbstractBankStatementParser` (delimiter sniffing, Indonesian/US number normalization, date parsing) with per-bank subclasses (BCA, Mandiri, BNI, BRI) and a factory
- `BankReconciliationService` — import, auto-match, manual match/unmatch, complete, delete, totals recalc, journal-entry candidate listing, and the `isJournalEntryReconciled()` lock check
- Opening-balance prefill from the account's Opening Balance (adjustable to the statement), statement closing + computed closing balance + variance

**Why CSV and not bank APIs:** Each Indonesian bank has a different API with its own authentication flow (OAuth, IP whitelists, per-bank SDKs) — roughly 2-3 weeks per bank. CSV import covers the UMKM workflow at **zero per-bank integration cost**, and all four banks provide CSV statement exports. The bank-API path remains an optional future automation layer (ROADMAP §5.7) *on top of* the CSV engine, not a replacement.

**Senior angle:** This is a deliberate scoping decision — matching the payment-ease-of-use of a bank API against the cost of maintaining N vendor integrations for a product whose users primarily operate on exported statements. Ask whether the candidate can articulate the trade-off rather than reflexively demanding "real" API integration.

---

### Q25.2 — Describe the matching pipeline and the variance gate.

**Answer:**

After import, `BankReconciliationService` matches statement lines to journal entries:

- **Auto-match** pairs statement lines with journal entries by **exact amount within a ±5-day window** — purposefully conservative to avoid false positives
- **Manual match/unmatch** covers everything auto-match misses; unmatched statement lines and unmatched journal entries stay visible for investigation
- **Variance gate:** `complete()` throws a `DomainException` when `abs(variance) > 0.005` — a reconciliation **cannot be completed out of balance**. The Complete button is disabled in the UI when variance ≠ 0 (the 0.005 tolerance absorbs float arithmetic). Balance = opening + matched credits − matched debits vs. the statement closing balance.
- Status workflow: `open` → `completed`, with a reconciliation history list.

**Why the gate matters:** the entire point of reconciliation is proving the books match the bank. Allowing a non-zero variance to be "completed" would legitimize a discrepancy and defeat the control.

---

### Q25.3 — How does the reconciliation lock protect journal entries?

**Answer:**

Matched journal entries become **immutable** once a reconciliation completes. `assertNotReconciled()` is wired into `AccountingService::reverseJournalEntry / voidJournalEntry / unpostJournalEntry`, so reversing, voiding, or unposting a reconciled entry throws.

- "Terekonsiliasi" badges appear in Journal, Ledger, and manual-entry lists via the `withReconciledStatus()` eager-loading scope — one query, no N+1
- This is the same immutability principle as *posted* journal entries (Q7.3): once a control point is passed, history cannot be rewritten — the statement of account stays trustworthy after the fact

**Senior angle:** the lock is what makes the bank statement trustworthy post-completion. A candidate who connects it to the double-entry immutability philosophy (void vs. reverse, Q7.3) understands the accounting mindset behind the feature.

---

## Scoring Guide for Interviewers

| Score | Description |
|---|---|
| **5/5** | Gives complete, accurate answer including edge cases, trade-offs, and real code references |
| **4/5** | Correct answer with minor gaps; understands the "why" not just the "what" |
| **3/5** | Mostly correct but misses important nuances (e.g., knows FEFO but doesn't connect it to COGS) |
| **2/5** | Partially correct; understands the concept but cannot explain the implementation |
| **1/5** | Incorrect or confused; cannot connect concepts to the actual codebase |
| **0/5** | No knowledge of the topic |

## Red Flags

- Cannot explain why `BelongsToTenant` doesn't protect raw `DB::table()` queries
- Does not know the difference between `void` and `reverse` for journal entries
- Cannot explain FEFO's connection to COGS calculation
- Does not understand why Fortify and Sanctum serve different purposes
- Cannot articulate why the Service Layer exists
- Does not mention the `proration_credit` mechanism for plan upgrades
- Cannot explain why `available_amount` on `customer_credits` needs an observer to stay in sync
- Claims "Holt-Winters parameters are hardcoded" when the `optimizeHoltWinters()` grid search has been in production since May 2026 — this is a tell that the candidate is reciting documentation rather than reading code
- Claims "CSP has no nonces" without noting the nonce implementation or that `'unsafe-eval'` is documented with rationale in `SecurityHeaders.php`
- Proposes "adding grid search to Holt-Winters" or "replacing Web3Forms with a backend endpoint" as *new* ideas when both are already shipped — signals stale knowledge
- Believes PPN is still a separate toggle on the Sales form — "PPN is no longer a toggle. It's a property of the selected tax." (universal tax refactor, July 2026)
- Cannot explain why Faktur Pajak generation no longer posts a PPN journal — since Phase 1E (Aug 2026) PPN is recognized at sale creation via the 5-leg VAT split
- Proposes "adding per-bank PPh engines" or "integrating bank APIs" as *new* ideas — the universal `is_pph` mechanism and the CSV-based bank reconciliation already shipped

## Green Flags

- Proactively identifies the `DB::table()` raw query risk AND notes that `DB::tenantTable()` macro now mitigates it, but is not a compile-time guarantee
- Identifies `'unsafe-eval'` in the CSP `script-src` as a security concern and can articulate why it's retained (jQuery `$.globalEval`, Chart.js dynamic renderers — documented in `SecurityHeaders.php`) — or suggests alternative libraries that are CSP-compliant
- Explains the Aug 2026 `/api` prefix bug: that a custom `using:` closure in `bootstrap/app.php` bypassed Laravel's automatic `/api` prefix + `api` middleware group, and that "Scribe matching zero routes" was the diagnostic signal
- Knows the contact form was rewired from Web3Forms to `POST /api/contact` and can explain the honeypot, the queued `ShouldQueue` mailable (hence `assertQueued`, not `assertSent`), and `throttle:5,60`
- Can describe the correct leaked-secret playbook: rotate first, then `git filter-repo` + `--force-with-lease`, and knows history rewriting doesn't un-leak an already-public client key
- Explains why bank reconciliation is CSV-based (per-bank API cost vs. UMKM workflow) and can name the variance gate (0.005) and the reconciliation lock wired into `AccountingService`
- Can articulate why PPh uses one universal `is_pph` mechanism (single PPh Payable liability) instead of per-type calculation engines
- Knows the billing period inconsistency between `Tenant::initializeBillingPeriod()` and `BillingService` was resolved in June 2026 and can describe the pre-fix impact on scheduled downgrades
- Notes that `remaining_quantity` on `po_items` exists separately from `product_warehouse.quantity` for FEFO batch tracking
- Can trace a complete sales transaction from HTTP request → service → FEFO → journal entries → observer
- Identifies that the `SalesOpportunity` model supports two conversion paths (direct to `Sales` or via `Quotation`) and explains why this is the correct CRM design for a system that needs both informal and formal quoting workflows
- Understands the distinction between the `SuperAdmin` model (platform-level, separate auth guard) and the `superuser` Spatie role (tenant-level, all permissions within a tenant) — and can explain why both are architecturally correct
- Can walk the full e-Faktur lifecycle: 5-leg VAT split at sale creation → compliance-only Faktur Pajak generation → NSFP FIFO with `lockForUpdate()` → DJP CSV (FK/OF, UTF-8 BOM, integer cents) → SPT Masa PPN (Keluaran − Masukan, Lebih Bayar)
- Knows why `PPh 22` was replaced by `PPh Final 0.5%` *in place* (existing `pph_tax_id` references stay valid) and can articulate the single-PPh-Payable (2150) rationale
- Explains that e-Faktur Phase 2 (Klikpajak PJAP behind `PjapInterface`, idempotency keys, dedicated `tax-submissions` queue) is planned and Enterprise-gated via the `efaktur_api` slug
