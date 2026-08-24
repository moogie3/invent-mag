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

---

## 26. Purchasing & Accounts Payable (AP)

### Q26.1 — Walk through the Purchase Order lifecycle from creation to payment, including stock and accounting effects.

**`PurchaseService::createPurchase()`** runs entirely inside a `DB::transaction()`:

1. Decodes `products` JSON; throws on empty/invalid data
2. For each line item: creates a `POItem` record (stores `expiry_date` per batch, sets `remaining_quantity = quantity`), increments `ProductWarehouse.quantity` via `firstOrCreate` + `increment` (creates the pivot row if the product has never been stocked in that warehouse)
3. Calculates totals: `PurchaseHelper::calculateDiscount()` (order discount) → `afterDiscount` → `PurchaseHelper::calculateTaxAmount()` → `finalTotal`; for inclusive tax `finalTotal = afterDiscount`; for exclusive `finalTotal = afterDiscount + taxAmount`
4. Subtracts PPh from `finalTotal` (`max(0, finalTotal - pphAmount)`)
5. Updates the Purchase header with `total`, `total_tax`, `pph_enabled/rate/amount/tax_id`, `ppn_enabled/rate/amount`
6. Creates accounting journal entry — **throws if `inventory_account_id` or `accounts_payable_account_id` is not configured** (unlike sales which silently skips). Standard entry:
   - `Dr Inventory` (gross or net-of-PPN amount)
   - `Cr Accounts Payable` (`finalTotal` — net of PPh)
   - `Dr PPN Masukan` (input VAT asset, code `1160`) — only if Indonesian + `is_ppn` tax + `ppn_masukan_account_id` configured; when present, Inventory is debited at the pre-tax (`afterDiscount`) amount instead of gross
   - `Cr PPh Payable` (code `2150`) — only if Indonesian + `is_pph` + `pph_payable_account_id` configured

**Payment status machine** (`updatePurchaseStatus()`):
- `Unpaid` → `Partial` (some payments, total < grand total)
- `Partial` → `Paid` (total payments ≥ grand total, within `round(..., 2)`)

**`addPayment()`** — separate from creation; creates a `Payment` morph record and journals `Dr AP / Cr Cash`. **Throws** if `accounts_payable_account_id` or `cash_account_id` is missing (no silent skip).

**`deletePurchase()`** — reverses all sourced `posted` journal entries via `AccountingService::reverseJournalEntry()`, decrements stock per line item, then deletes.

**Dashboard aggregates** — `getPurchaseIndexData()` uses a single raw SQL aggregate join instead of hydrating all records, tracking domestic (`IN`) vs. foreign (`OUT`) supplier split and monthly totals.

---

### Q26.2 — How do Purchase Returns work and what is the accounting treatment?

**`PurchaseReturnService::createPurchaseReturn()`**:

1. Decodes `items` JSON, filters for `returned_quantity > 0`
2. Calculates `totalReturnAmount` server-side (return_price × returned_qty per line)
3. Inherits tax from the original Purchase (`tax_rate`, `tax_type`, `tax_id`)
4. Creates `PurchaseReturn` + `PurchaseReturnItem` records
5. Decrements `ProductWarehouse.quantity` (from the PO's warehouse, falls back to main warehouse)
6. Decrements `POItem.remaining_quantity` (FIFO across batch lines via `decrementPOItemRemaining()`)
7. Updates original PO status: total returned qty ≥ total purchased qty → `Returned`; partial → `Partial`
8. Creates a 4-leg journal entry:
   - `Dr AP` (reduce what is owed) by `totalReturnAmount`
   - `Cr Purchase Returns` (contra-COGS, account code `5120-{tenantId}`) by `totalReturnAmount`
   - `Dr COGS/Purchases` by `proportionalPurchase` (= `originalInventoryDebit × returnRatio`, derived by reading the original PO journal entry's inventory debit line)
   - `Cr Inventory` by `proportionalPurchase`

**Status values:** `Pending`, `Completed`, `Canceled`

**Cancel** (`cancelPurchaseReturn()`) — if cancelling a `Completed` return: restores stock, restores `remaining_quantity` via `incrementPOItemRemaining()`, reverses the return journal entry via `AccountingService::reverseJournalEntry()`, then sets status `Canceled`.

**No "SupplierCredit" model exists** — purchase returns directly reduce AP via the journal entry (no credit note record analogous to `CustomerCredit`).

---

## 27. Point of Sale (POS)

### Q27.1 — How does the POS module differ from a regular sale, and what does `PosService::createSale()` do?

POS is a **walk-up, always-paid, single-warehouse** sale path distinct from the regular `SalesService::createSale()`.

**Key differences:**

| Aspect | Regular Sale (`SalesService`) | POS (`PosService`) |
|---|---|---|
| Payment at creation | No — `addPayment()` called separately | Yes — `Payment` record created immediately |
| Warehouse | Any warehouse on the order | Main warehouse only (`is_main = true`) |
| Invoice format | User-supplied | Auto-generated `POS-0001` (sequential) |
| Status at creation | `Pending` or `Unpaid` | Always `Paid` |
| COGS source | FEFO batch (`po_items` cost) | `product.cost_price` × qty (no FEFO) |
| Stock decrement | Via FEFO deduction loop | Direct `ProductWarehouse.decrement()` |
| Sale type | `Sales::TYPE_SALE` | `Sales::TYPE_POS` |

**`PosService::createSale()` steps:**

1. Decodes `products` JSON; throws on empty/invalid
2. Resolves tax: `data['tax_id']` → falls back to `Tax::default()` → `Tax::active()->first()`
3. Calculates: `taxable = subtotal − orderDiscount`; `taxAmount = SalesHelper::calculateTaxAmount(...)`; `grandTotal = inclusive ? taxable : taxable + taxAmount`; then **subtracts PPh**: `grandTotal = max(0, grandTotal − pphAmount)`
4. Cash payment guard: if `payment_method === 'CASH'` and `amount_received < grandTotal` → throws
5. Resolves invoice number from last `POS-%` invoice (`str_pad` to 4 digits)
6. Verifies main warehouse exists; throws if not
7. Validates stock per product in main warehouse — throws `'Insufficient stock for: ...'` if any line is short
8. Inside `DB::transaction()`:
   - Creates `Sales` record (`status='Paid'`, `type=TYPE_POS`, `change_amount = max(0, received − grandTotal)`, stores `pph_*` fields only if Indonesian + `is_pph`)
   - Creates one `Payment` record (`amount = grandTotal`, `payment_date = now()`)
   - Creates `SalesItem` lines; decrements `ProductWarehouse` per line
   - Computes `totalCostOfGoods` using `product.cost_price` × qty (not FEFO)
   - **Accounting** (only if all 4 required `accounting_settings` keys present):
     - Sale entry: `Dr AR / Cr Revenue / Dr COGS / Cr Inventory`
     - VAT split (Indonesian + `is_ppn` + `ppn_keluaran_account_id`): adds `Cr PPN Keluaran`; reduces revenue credit to `grandTotal − taxAmount`
     - Payment entry (if `cash_account_id` set): `Dr Cash / Cr AR`
     - PPh entry (if Indonesian + `is_pph` + `pph_receivable_account_id`): `Dr PPh Receivable / Cr AR`
   - Returns the created `Sales` model

**`getReceiptData(Sales $sale)`** recomputes all totals from the sale record for the receipt view (line discounts, order discount, tax, PPh, change).

---

## 28. Financial Reporting Suite

### Q28.1 — What financial reports does the system produce and how are they generated?

**Reports available** (`ReportController`, `routes/admin/reports.php`):

| Report | Service | Route | Plan gate |
|---|---|---|---|
| Income Statement (P&L) | `FinancialReportingService::generateIncomeStatement()` | `admin.reports.income-statement` | `plan.feature:financial_reports` |
| Balance Sheet | `FinancialReportingService::generateBalanceSheet()` | `admin.reports.balance-sheet` | `plan.feature:financial_reports` |
| Cash Flow Statement | `CashFlowService::generateCashFlowStatement()` | `admin.reports.cash-flow` | `plan.feature:financial_reports` |
| Aged Receivables | `ReportController::agedReceivables()` | `admin.reports.aged-receivables` | `plan.feature:financial_reports` |
| Aged Payables | `ReportController::agedPayables()` | `admin.reports.aged-payables` | `plan.feature:financial_reports` |
| Adjustment Log | `ReportController::adjustmentLog()` | `admin.reports.adjustment-log` | All plans |
| Recent Transactions | `ReportController::recentTransactions()` | `admin.reports.recent-transactions` | All plans |
| General Ledger | `GeneralLedgerService::getGeneralLedger()` | (via AccountingController) | `plan.feature:accounting` |
| Journal Register | `GeneralLedgerService::getJournalEntries()` | (via AccountingController) | `plan.feature:accounting` |

All financial reports require `can:view-financial-reports` permission AND the `plan.feature:financial_reports` middleware.

---

### Q28.2 — Explain the Income Statement and Balance Sheet calculation logic in detail.

**`FinancialReportingService::generateIncomeStatement(startDate, endDate)`:**

- Revenue accounts: for each `type = 'revenue'` account, `balance = credits − debits` within the date range (only `status = posted` entries, tenant-scoped)
- Expense accounts: for each `type = 'expense'` account, `balance = debits − credits`
- Uses `withSum()` on the `transactions` relationship with nested `whereHas('journalEntry', ...)` constraints — two `withSum` calls per account type
- `net_income = total_revenue − total_expenses`

**`FinancialReportingService::generateBalanceSheet(endDate)`:**

- Loads ALL account types with cumulative transaction sums up to `endDate` (all posted entries `date <= endDate`)
- Normal balance rule: `asset` + `expense` → `debits − credits`; `liability` + `equity` + `revenue` → `credits − debits`
- Accounts with `abs(balance) <= 0.001` are excluded
- Revenue and expense balances roll into equity: revenue adds, expense subtracts from `totalEquity`
- Returns `equation_balanced` boolean: `abs(totalAssets − (totalLiabilities + totalEquity)) < 0.001`

**`GeneralLedgerService::calculateOpeningBalance(accountId, startDate)`:**

- Opening balance = `account.opening_balance` (stored field) + net of all transactions **before** `startDate`
- Uses raw SQL aggregate (`SUM(CASE WHEN type='debit' THEN amount ELSE 0 END)`) — **does not hydrate historical `Transaction` models into RAM** for performance
- `period_change = sum(debit − credit)` for transactions in range; `closing = opening + period_change`

---

### Q28.3 — How is the Cash Flow Statement built?

**`CashFlowService::generateCashFlowStatement(startDate, endDate)`** uses the **indirect method**:

1. **Net income** — from `FinancialReportingService::generateIncomeStatement()`
2. **Non-cash items** — depreciation/amortisation: expense accounts in code range `6200–6299` with period activity > 0.001 (Admin & General Expenses group; `Depreciation Expense` is `6240`)
3. **Working capital changes** — current asset changes (code `1100–1199` excluding cash `1110–1120`) have a **negative** cash impact when they increase; current liability changes (`2100–2199`) have a **positive** impact. Delta = end balance − start balance.
4. **Operating cash flow** = net income + non-cash total + working capital total
5. **Investing activities** — fixed asset accounts (`1200–1299`), excluding contra accounts and level < 2, `cfImpact = −delta`
6. **Financing activities** — long-term liabilities (`2200–2299`) + equity (`3000–3999`), excluding retained earnings (`3200–3299`) and level < 1, `cfImpact = +delta`
7. **Cash balance** — accounts with code `1110–1120` (Cash + Bank)
8. Returns `opening_cash_balance`, `closing_cash_balance`, `net_change_in_cash`

**Note:** Account code ranges are compared by the integer prefix before the first `-` (e.g., `1110-{tenantId}` → prefix `1110`).

---

## 29. Chart of Accounts

### Q29.1 — Describe the Chart of Accounts structure, the account model, and how it is managed.

**`Account` model** fields: `parent_id`, `name`, `code` (e.g. `1130-{tenantId}`), `type`, `level`, `is_active`, `is_contra`, `normal_balance` (`debit`/`credit`), `opening_balance`, `opening_balance_date`, `currency`, `allow_manual_entry`, `tax_type`.

**Default COA seeded by `AccountSeeder`** (runs at tenant creation via `TenantSetupService`):

| Range | Type | Level | Examples |
|---|---|---|---|
| `1000` | asset | 0 | Assets (root) |
| `1100–1170` | asset | 1–2 | Current assets: Cash (1110), Bank (1120), AR (1130), Allowance for Doubtful (1135, contra), Inventory (1140), Prepaid (1150), PPN Masukan/Input VAT (1160), PPh Receivable (1170) |
| `1200–1235` | asset | 1–2 | Fixed assets: Land (1210), Buildings (1220), Accum. Deprec. Buildings (1225, contra), Equipment (1230), Accum. Deprec. Equipment (1235, contra) |
| `2100–2150` | liability | 1–2 | Current liabilities: AP (2110), Accrued Expenses (2120), PPN Keluaran/Output VAT (2130), Salary Payable (2140), PPh Payable (2150) |
| `2200–2210` | liability | 1–2 | Long-term: Bank Loans (2210) |
| `3000–3200` | equity | 0–1 | Opening Balance Equity (3010), Owner's Equity (3100), Retained Earnings (3200) |
| `4000–4120` | revenue | 0–1 | Sales Revenue (4100), Sales Returns (4110, contra), Sales Discounts (4120, contra) |
| `5000–5200` | expense | 0–1 | COGS group: Purchases (5100), Freight-in (5110), Purchase Returns (5120, contra), Purchase Discounts (5130, contra), COGS (5200) |
| `6000–6300` | expense | 0–2 | Operating expenses: Selling (6100–6120), Admin/General (6200–6240 incl. Depreciation 6240), Salary Expense (6300) |

Account codes are tenant-scoped: `{base}-{tenantId}` (e.g., `1130-5`).

**`ChartOfAccountsService::resetToDefault()`** — destructive reset: disables FK checks, deletes all `Transaction` + `JournalEntry` + `Account` records for the tenant, re-enables FK checks, then calls `Artisan::call('db:seed', ['--class' => 'AccountSeeder'])`. Also auto-configures `accounting_settings` (maps canonical codes to account IDs).

**Accounting settings** stored as JSON in `tenants.accounting_settings`. Required keys (6 mandatory, 4 optional):
- Required: `sales_revenue_account_id` (4100), `accounts_receivable_account_id` (1130), `cost_of_goods_sold_account_id` (5200), `inventory_account_id` (1140), `accounts_payable_account_id` (2110), `cash_account_id` (1110)
- Optional: `ppn_keluaran_account_id` (2130), `ppn_masukan_account_id` (1160), `pph_receivable_account_id` (1170), `pph_payable_account_id` (2150)

**`canBeDeleted()`** — account can only be deleted if it has no transactions, no child accounts, and `opening_balance == 0`.

---

## 30. Stock Transfers, Opname & Adjustments

### Q30.1 — How does stock transfer between warehouses work, and what audit trail does it leave?

**`StockTransferController::store()`** — validates `from_warehouse_id != to_warehouse_id`, qty ≥ 1, then:

1. Checks source `ProductWarehouse.quantity` — redirects back with error if insufficient (no exception thrown; it's a web controller)
2. Inside `DB::transaction()`:
   - Decrements source `ProductWarehouse.quantity`
   - `firstOrCreate` destination pivot (with `tenant_id`), increments quantity
   - Creates **two `StockAdjustment` records** (`adjustment_type = 'transfer'`):
     - Source: `quantity_before` → `quantity_after = before − transferred`, `reason = "Transfer to {toWarehouse.name}"`
     - Destination: `quantity_before` → `quantity_after = before + transferred`, `reason = "Transfer from {fromWarehouse.name}"`
3. Logs via `ActivityLogService::logCreate(ActivityLog::MODULE_STOCK_TRANSFER, "...")` inside a `try/catch` — logging failure never blocks the transfer

`StockAdjustment` types: `increase`, `decrease`, `transfer` (set by the calling code).

---

### Q30.2 — Explain the Stock Opname (physical count) lifecycle.

**`StockOpnameService`** manages a 3-status lifecycle: `Draft` → `In Progress` → `Completed`

**`createOpname(warehouseId, scheduledDate, notes)`:**
- Reference number: `SO-YYMMDD-0001` (tenant-scoped sequential, 4-digit)
- Snapshots ALL `ProductWarehouse` rows for the warehouse (expected_qty = current quantity)
- Includes products with **no stock record** (expected_qty = 0) so new products aren't missed
- Bulk-inserts `StockOpnameItem` rows in chunks of 500

**`updateCounts(opname, counts)`:**
- For each product with a non-null `counted_qty`: sets `variance = counted − expected`, `variance_value = abs(variance) × product.cost`
- Transitions `Draft` → `In Progress` on first count save

**`completeOpname(opname)`:**
- Throws if already `Completed`
- For each item with `variance != 0`:
  1. Sets `ProductWarehouse.quantity = counted_qty`
  2. Creates a `StockAdjustment` (`increase` or `decrease`, references opname number)
  3. Accumulates `totalShrinkageValue` and `totalExcessValue` (abs variance × `product.price`)
- **Accounting** (if `tenant.hasFeature('accounting')` and settings have `inventory_account_id` + `cost_of_goods_sold_account_id`):
  - Shrinkage: `Dr COGS/Expense / Cr Inventory`
  - Excess: `Dr Inventory / Cr COGS/Expense`
  - Single consolidated journal entry per opname; silently skips if accounts not configured
- Sets `status = Completed`, `completed_at = now()`

---

## 31. Opening Balances & Recurring Journal Entries

### Q31.1 — How are opening balances entered and stored?

**`OpeningBalanceService::saveOpeningBalances(balances, balanceDate, notes)`:**

1. Validates: no negative amounts; `totalDebit − totalCredit < 0.01` (must balance within 1 cent)
2. Accepts both `'debit'/'credit'` keys (form) and `'amount'/'type'` keys (API) — same method handles both callers
3. Inside `DB::transaction()`:
   - Deletes any existing `OpeningBalance` records for `balanceDate`
   - Deletes any `JournalEntry` with `entry_type = ENTRY_TYPE_OPENING` and `date = balanceDate` (idempotent re-save)
   - For each non-zero account balance:
     - Creates an `OpeningBalance` record (`debit_amount`, `credit_amount`, `balance`, `balance_date`)
     - Updates `accounts.opening_balance` + `accounts.opening_balance_date` directly
   - Adds an automatic balancing line to the **Opening Balance Equity** account (3010, found by name `%Opening Balance Equity%` OR `%Ekuitas Saldo Awal%`) — `balancingType = debit if totalCredit > totalDebit`
   - Calls `AccountingService::createOpeningBalanceEntry()` which posts a single `ENTRY_TYPE_OPENING` journal entry

**`getAccountsForSetup()`** — returns `asset/liability/equity` accounts, level ≥ 1, active, excluding the Opening Balance Equity account itself (to prevent a circular entry).

**`deleteOpeningBalances(date)`** — reverses all three side effects: deletes journal entry, deletes `OpeningBalance` records, resets `accounts.opening_balance = 0` and `opening_balance_date = null`.

---

### Q31.2 — How do recurring journal entries work and are they triggered automatically?

**`RecurringJournalEntry` model** — stored as a template with:
- `name`, `description`, `frequency` (`daily`/`weekly`/`monthly`/`quarterly`/`yearly`), `interval` (multiplier)
- `start_date`, `end_date` (optional), `next_generation_date`, `last_generated_at`, `is_active`
- `transactions` — JSON array of `{account_code, type, amount}` line items
- `template_account_code` — optional; used to append the account name to the description

**`isDueForGeneration()`** — `is_active` AND `end_date` not past AND `now() >= next_generation_date`

**`generateJournalEntry()`** — calls `AccountingService::createJournalEntry()` with the template transactions; updates `last_generated_at` and `next_generation_date` via `calculateNextGenerationDate()` (quarterly = `addMonths(interval × 3)`)

**`ManualJournalEntryService::processRecurringEntries()`** — loops `RecurringJournalEntry::dueForGeneration()->get()`, runs each in a `DB::transaction()`, increments a counter, logs errors without re-throwing.

**⚠ Not wired to the scheduler.** `routes/console.php` only schedules `billing:check-expired` (01:30), `billing:process-scheduled-changes` (01:00), and `tenant:reset-demo` (00:00). `app/Console/Commands` contains only `BackupTenant`, `CheckExpiredSubscriptions`, `ProcessScheduledPlanChanges`, `ResetDemoTenant`. The recurring-entry infrastructure is complete and tested, but automatic triggering requires a future artisan command + schedule entry.

---

## 32. Notification System

### Q32.1 — What is the difference between `NotificationService` and `SystemNotificationService`?

The system has **two complementary notification services** with different scopes, combined by `NotificationController`:

**`NotificationService`** — operational/transactional notifications:
- Due purchase orders (due ≤ 7 days, limit 20, `type = 'purchase'`)
- Due sales invoices (due ≤ 7 days, limit 20, `type = 'sales'`)
- Low stock alerts (`ProductWarehouse.quantity <= product.low_stock_threshold`, `type = 'product'`)
- Expiring product batches via `POItem::getExpiringSoonItems()` (`type = 'product'`)
- Show/hide logic: unpaid/partial OR payment made today; sorted by `days_remaining` asc
- Urgency: ≤ 2 days → `high`; ≤ 5 days → `medium`; else → `low`

**`SystemNotificationService`** — platform/billing/setup notifications:
- **Trial status** — days remaining (critical ≤ 3, high ≤ 7); trial expired (always critical)
- **Plan expiry** — 14-day warning window only; skips if on trial; expired = separate message
- **Usage limits** — fires only when **at or over** the limit (users ≥ `max_users`, warehouses ≥ `max_warehouses`, products ≥ `max_products`)
- **Accounting setup incomplete** — checks 6 required `accounting_settings` keys; count missing → description
- **Profile incomplete** — `shopname`, `address`, or `avatar` not set
- **Scheduled downgrade** — shows `pending_plan_effective_at` and `proration_credit`
- **Recent upgrade** — within 24 hours of `plan_changed_at`
- **Product expiry (30-day)** — total count within 30 days; critical if any expire within 7 days
- **Backup status** — no backups → warning; last backup within 24h → success confirmation; >7 days → overdue (>30 days = high urgency). Checks per-tenant zip files in `storage/app/backups/` by `{domain}-` prefix.

**Dismissal** — both services respect `user.system_settings['dismissed_notifications']` (JSON array of IDs). `markAsRead()` / `clearAll()` in `NotificationController` write to this user JSON column — **not** to Laravel's `notifications` table. Dismissed IDs persist until manually cleared.

**`NotificationController::count()`** — returns `{count: operational + system}` as JSON; returns `{count: 0}` on any exception (never errors the UI).

---

## 33. Tenant Onboarding & Superadmin Tutorial System

### Q33.1 — What happens when a new tenant is created, and what data is seeded automatically?

**`TenantSetupService::setup(Tenant $tenant, ?string $planSlug)`** is called on tenant creation:

1. `$tenant->makeCurrent()` — establishes the tenant context for all subsequent operations
2. `assignPlan()` — resolves slug (uses `$planSlug` arg, falls back to `config('plans.default_plan', 'starter')`); gracefully skips if no plans exist yet. Calls `PlanService::assignPlanToTenant()`; starts trial if `$plan->hasTrial()`
3. `runEssentialSeeders()` — runs 5 seeders in order, each in try/catch (failure of one doesn't stop others):
   - `AccountSeeder` — full Chart of Accounts + auto-configures `accounting_settings`
   - `CategorySeeder` — default product categories
   - `UnitSeeder` — units of measurement
   - `CurrencySeeder` — currency settings
   - `TaxSeeder` — tax configurations
4. `createDefaultWarehouse()` — `Warehouse::create(['name' => 'Main Warehouse', 'is_main' => true, 'address' => '{tenant.name} Main Location'])`
5. `createDefaultCustomer()` — `Customer::create(['name' => 'Walk-in Customer', 'email' => 'cash@{tenant.domain}', ...])`
6. `Tenant::forgetCurrent()` — clears tenant context

**Result after onboarding:** tenant has a full COA, default categories/units/currency/taxes, one main warehouse, one Walk-in Customer for cash/POS sales, and a plan (with or without trial).

---

### Q33.2 — What is the Superadmin Tutorial System?

The superadmin panel has a context-sensitive help system backed by `config/superadmin-tutorial.php` and `TutorialService`.

**`TutorialService::getContextForRoute(?string $routeName)`:**
- Looks up `config('tutorial.contexts')` (loaded from the config file) using `Str::is($pattern, $routeName)` for wildcard matching
- Falls back to a generic "Help & Tutorial" context with 3 general steps

**`config/superadmin-tutorial.php`** — maps 18 superadmin route names to structured help context arrays, each with:
- `title`, `icon`, `description`
- `steps` — array of HTML strings (rendered in the help sidebar)
- Optional `alert`, `env_table`, `env_key`, `docs_file`, `env_note` (CMS page only)

**Routes covered:** `superadmin.dashboard`, `superadmin.tenants.{index,show}`, `superadmin.plans.{index,create,edit}`, `superadmin.subscriptions.index`, `superadmin.features.index`, `superadmin.billing.{index,show}`, `superadmin.transactions.index`, `superadmin.pending-payments.index`, `superadmin.payment-overview.index`, `superadmin.invoices.{index,show}`, `superadmin.permissions.index`, `superadmin.settings.{index,platform-currency,billing-rate,preferences}`, `superadmin.audit-log.{index,show}`, `superadmin.backups.index`, `superadmin.cms.index`

**Note:** The tenant-facing admin panel has a separate `config/tutorial.php` (not `superadmin-tutorial.php`) that powers the same route-aware help sidebar for tenant users.

---

## 34. Audit Logging

### Q34.1 — How does the custom audit logging system work?

The system uses a **custom `ActivityLogService`** + `ActivityLog` model (no `spatie/laravel-activitylog` — not in `composer.lock`).

**`ActivityLogService::log()`** writes to the `activity_logs` table with:
- `tenant_id`, `user_id`, `user_name`, `user_role` (first Spatie role name)
- `module` — one of the `MODULE_*` constants
- `action` — one of the `ACTION_*` constants
- `entity_type` (FQCN), `entity_id`, `entity_label` (human name)
- `description` (free text), `old_values`, `new_values` (JSON columns)
- `ip_address`, `user_agent`

**Convenience shortcuts on `ActivityLogService`:**
- `logCreate(module, description, $model, $newValues)` — `ACTION_CREATE`, captures `$model->toArray()` as new values
- `logUpdate(module, description, $model, $oldValues, $newValues)` — `ACTION_UPDATE`
- `logDelete(module, description, $model, $oldValues)` — `ACTION_DELETE`

**`ActivityLog::MODULE_*` constants (30 modules):**
`Dashboard`, `Product`, `Sales`, `Sales Return`, `Purchase Order`, `Purchase Return`, `POS`, `Customer`, `Supplier`, `Warehouse`, `Stock Adjustment`, `Stock Transfer`, `Stock Opname`, `Category`, `Unit`, `Tax`, `Currency`, `User`, `Settings`, `Accounting`, `Bank Reconciliation`, `Journal Entry`, `Sales Pipeline`, `CRM`, `Supplier Management` (SRM), `Employee`, `Attendance`, `Payroll`, `Report`, `Payment`

**`ActivityLog::ACTION_*` constants (15 actions):**
`create`, `update`, `delete`, `login`, `logout`, `export`, `import`, `approve`, `reject`, `transfer`, `adjust`, `payment`, `post`, `void`, `reverse`

**Observer pattern** — 28 models registered in `AppServiceProvider::registerActivityObservers()` (verified in prior session). Observers call `ActivityLogService::logCreate/logUpdate/logDelete` in `created`/`updated`/`deleted` hooks. Logging calls are always wrapped in `try/catch` so a log failure never breaks a business transaction.

**Retention** — no pruning command; logs accumulate. Each log links back to the entity via `entity_type` + `entity_id` (not a morph relationship — plain string columns).

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

---

## §35 — HR & Payroll Module

### Q35.1 Walk through the full HR & Payroll lifecycle from an employee record through to a paid payslip.

**Scope:** Professional + Enterprise only (feature slug: `hr`). Four controllers in `app/Http/Controllers/Admin/Hr/`: `EmployeeController`, `AttendanceController`, `PayrollController`, `PayslipController`. Two services: `EmployeeService`, `PayrollService`. Routes in `routes/admin/hr.php`.

#### Employee Management (`Employee` model)

Key fields on the `employees` table (verified from migrations and model):
- `tenant_id`, `name`, `employee_code`, `department`, `position`, `employment_type`
- `base_salary` (decimal)
- `bank_account`, `bank_name`, `bank_number` — for payslip
- `status` (`active` / `resigned` / etc.)
- `default_allowances` (JSON array — `[{label, amount}, …]`) — auto-populated on each payroll run
- `default_deductions` (JSON array — same shape) — added on each payroll run (absence deduction appended dynamically)
- Migration `2026_05_12_035444_add_allowances_deductions_to_employees_table` added the allowances/deductions columns

#### Attendance Period (`AttendancePeriod` model)

- One row per month/year per tenant: `year`, `month`, `status` (`open` / `closed`), `working_days` (configurable — migration `2026_05_12_045741_add_working_days_to_attendance_periods_table`)
- `working_days` defaults to 21 if null; the period manager can set 22–26 to accommodate Mon–Sat work weeks

#### Attendance Record (`AttendanceRecord` model)

- `period_id`, `employee_id`, `date`, `status` (`present` / `absent` / `sick` / `leave` / `holiday` / `half_day`), `overtime_hours`
- Entered via the daily grid in `AttendanceController` — supports individual entry, bulk fill, and custom skip-day selection

#### Payroll Run — `PayrollService::runPayroll(AttendancePeriod $period)`

1. **Guard:** checks no existing `PayrollRun` for the period+tenant. Throws `InvalidArgumentException` if one already exists.
2. **Fetches** all `active` employees for the tenant.
3. **Creates** a `PayrollRun` record (`status = 'draft'`, `run_date = now()`).
4. **For each employee:** calls `createPayrollItem()`:
   - Fetches `AttendanceRecord`s for the period+employee
   - `presentDays` = count of `present`, `sick`, `leave`, `holiday` (all treated as paid)
   - `halfDays` = count of `half_day`
   - `overtimeHours` = sum of `overtime_hours`
   - `workingDays` = `period->working_days ?? 21`
   - `overtimePay` = `overtimeHours × (base_salary / 176)` — 176-hour divisor (monthly standard)
   - `unpaidDays` = `workingDays − (presentDays + halfDays×0.5)` — unrecorded days count as absent
   - `absenceDeductionAmount` = `(base_salary / workingDays) × unpaidDays` (added as a deduction item)
   - Creates `PayrollItem` with `allowances = employee.default_allowances`, `deductions = employee.default_deductions + [absence deduction if any]`, `gross_pay = calculateGross()`, `net_pay = calculateNet()`
5. Returns the `PayrollRun` in `draft` status for review and manual editing.

#### PayrollItem calculations

- `calculateGross()` = `base_salary + sum(allowances.amount) + overtime_pay`
- `calculateNet()` = `gross_pay − sum(deductions.amount)`
- Draft items can be edited via `PayrollService::updateItem()` — adjusts allowances/deductions arrays and recalculates gross/net in place

#### Two-step accounting flow

**Step 1 — Approve (`PayrollService::approve()`):**
- Transitions `PayrollRun` status: `draft` → `processed`
- Calls `postApprovalJournalEntry()`:
  - **Dr** `6300-{tenant_id}` (Salary Expense) — `total net_pay` of all items
  - **Cr** `2140-{tenant_id}` (Salary Payable) — same amount
  - Source document: `PayrollRun`

**Step 2 — Mark Paid (`PayrollService::markPaid(PayrollRun, accountId)`):**
- Transitions `PayrollRun` status: `processed` → `paid`, sets `paid_date = now()`
- Calls `postPaymentJournalEntry()`:
  - **Dr** `2140-{tenant_id}` (Salary Payable) — clears the liability
  - **Cr** selected bank account (`account_id` chosen at runtime) — actual cash outflow
  - Source document: `PayrollRun`

Both journal entries go through `AccountingService::createJournalEntry()` — never directly.

#### Export

`PayrollService::exportRunItems()` supports **PDF** (landscape A4 via Dompdf → `admin.hr.payroll.export-run-pdf`) and **CSV** (headers: Employee, Code, Base Salary, Allowances, Deductions, Overtime, Gross Pay, Net Pay, Present/Working).

`PayrollService::exportRuns()` exports a list of runs (PDF or CSV) — used for multi-period reporting.

#### Payslip

`PayrollService::generatePayslip(PayrollItem)` sets `pdf_path` and `pdf_generated_at` on the item. The payslip blade is at `resources/views/admin/hr/payslip/print.blade.php`. `PayslipController` handles the print/download flow.

#### Blade views

`resources/views/admin/hr/`:
- `employee/{form,index}.blade.php`
- `attendance/{index,show,export-pdf,export-period-pdf}.blade.php`
- `payroll/{index,show,export-pdf,export-run-pdf}.blade.php`
- `payslip/print.blade.php`

---

### Q35.2 What does `AccountingAuditService` do and how does it differ from `ActivityLogService`?

**Two parallel audit systems coexist:**

| | `AccountingAuditService` | `ActivityLogService` |
|---|---|---|
| **Table** | `accounting_audit_logs` | `activity_logs` |
| **Scope** | Journal entries only | All business entities (30 modules) |
| **Trigger** | Called explicitly in `ManualJournalEntryService` | Observer pattern on 28 models |
| **Data** | Full transaction array (account code, name, type, amount per line) + old/new JE state | Module, action, entity reference, old/new values |
| **Actions tracked** | `create`, `update`, `post`, `unpost`, `void`, `reverse`, `delete`, `approve`, `reject` | `create`, `update`, `delete`, `login`, `logout`, `export`, `import`, `approve`, `reject`, `transfer`, `adjust`, `payment`, `post`, `void`, `reverse` |
| **Why separate** | Accounting audit needs the full T-account detail (debit/credit per line) which the generic system doesn't capture | Business operation audit needs module/action semantics across all entities |

**`AccountingAuditService` public methods:**
- `logCreate(JournalEntry, transactions[], description?)` — logs ACTION_CREATE
- `logUpdate(JournalEntry, oldValues[], newTransactions[])` — logs ACTION_UPDATE
- `logAction(JournalEntry, action, oldValues?, additionalData[])` — generic action logger (post/unpost/void/reverse/delete/approve/reject)
- `getEntryAuditLogs(JournalEntry)` — retrieves logs for a single JE (used on the JE show page sidebar — the "Audit Log" badge)
- `getTenantAuditLogs(filters[])` — paginated tenant-scoped log query (filterable by action, entity_type, user_id, date range)
- `getAuditSummary(days=30)` — action count breakdown + 10 most recent logs (used by `AuditLogController` for the dashboard cards)

**`AccountingAuditLog` model fields:** `tenant_id`, `action`, `entity_type` (FQCN), `entity_id`, `old_values` (JSON), `new_values` (JSON), `description`, `user_id`, `user_name`, `ip_address`, `user_agent`

---

## §36 — Dashboard Service

### Q36.1 What data does `DashboardService::getDashboardData()` return and how is it computed?

`DashboardService` is a large service (~955 lines) that powers the main dashboard. `getDashboardData()` accepts `dates` (start/end Carbon objects, defaults to current year), `reportType` (`all`/`sales`/`purchases`), and an optional `categoryId` filter.

**Return array keys and their sources:**

| Key | Source |
|---|---|
| `topCategories` | Top 5 categories by revenue in the date range (JOIN sales_items → products → categories, SUM) |
| `monthlyData` | Per-month revenue/expenses/profit/margin breakdown for the date range |
| `chartLabels` / `chartData` | Monthly sales totals for current year (by order_date) |
| `purchaseChartLabels` / `purchaseChartData` | Monthly purchase totals for current year |
| `customerAnalytics` | Total/active customers, retention rate, avg order value, customer LTV (total sales ÷ total customers), top 5 customers |
| `supplierAnalytics` | Total/active suppliers, payment performance %, avg purchase value, outstanding AP, top 5 suppliers |
| `recentTransactions` | Up to 20 most recent sales/purchases in the date range (merged and sorted by date) |
| `topSellingProducts` | Top 5 products by units_sold in the date range |
| `recentSales` / `recentPurchases` | Latest 3 sales/purchases (for the summary widget) |
| `lowStockCount` / `lowStockProducts` | Per-warehouse items where `quantity >= low_stock_threshold` (ProductWarehouse JOIN Product) |
| `expiringSoonItems` | `POItem::getExpiringSoonItems()` |
| `totalliability` | `Purchase::sum('total')` (all time) |
| `countliability` | `Purchase::where('status','Unpaid')->sum('total')` |
| `countRevenue` | `Sales::where('status','Unpaid')->sum('total')` |
| `countSales` | Current-month Paid sales sum |
| `paidDebtMonthly` | Current-month Paid sales updated_at sum |
| `liabilitypaymentMonthly` | Current-month Paid purchases updated_at sum |
| `avgDueDays` | Average days between due_date and last payment_date for Paid sales (JOIN payments) |
| `collectionRate` | `paidInvoices / totalInvoices × 100` (count-based, not amount-based) |
| `keyMetrics` | 4 cards: Remaining Liability, Accounts Receivable, Monthly Earnings, Payment Overdue count |
| `financialItems` | 8 cards: Total Liabilities, This Month Paid Liabilities, Total AR, This Month AR Paid, Operating Expenses, Net Profit, Gross Profit, Operating Income — each with MoM change % |
| `invoiceStatusData` | Total invoices, collection rate, out/in counts, AR/AP aging amounts |
| `customerInsights` | Avg due days, collection rate, payment terms breakdown |
| `arAging` / `apAging` | 5-bucket aging: current (DATEDIFF < 0), 1-30, 31-60, 61-90, 90+ — raw SQL CASE/SUM on sales/po |

**Additional methods:**
- `getChartData(period, type)` — AJAX chart refresh (7days/30days/3months/year)
- `getChartDataByDateRange(dateRange, type, start?, end?)` — same but with named ranges (today/this_week/this_month/this_year/custom)
- `calculateDateRange(dateRange, start?, end?)` — converts range name to `{start, end}` Carbon pair

**Note on aging:** Buckets use `DATEDIFF(NOW(), due_date)` (MySQL) / `julianday('now') - julianday(due_date)` (SQLite). "current" = DATEDIFF < 0 (not yet due). Queries filter `status IN ('Unpaid', 'Partial')` + `due_date IS NOT NULL`.

---

## §37 — Bank Reconciliation Deep Dive

### Q37.1 Walk through the full bank reconciliation workflow — import, auto-match, manual match, and completion.

**Overview:** Bank reconciliation is in `BankReconciliationService` + `BankStatementParserFactory` + four bank parsers. Feature slug: `bank_reconciliation` (Professional+). Routes: `routes/admin/bank-reconciliation.php`.

#### Step 1 — Statement Import (`BankReconciliationService::importStatement()`)

Parameters: `accountId`, `bank` (e.g. `'bca'`), `content` (raw file string), `openingBalance`, `closingBalance`, `periodStart?`, `periodEnd?`

1. `BankStatementParserFactory::make($bank)` returns the appropriate parser (`BcaStatementParser`, `BniStatementParser`, `BriStatementParser`, or `MandiriStatementParser`).
2. Parser calls `parse($content)` → returns array of `[date, description, reference, type (debit/credit), amount]` lines. Each parser handles its own delimiter, date format, and number format detection.
3. If the parsed lines array is empty, throws `InvalidArgumentException('The statement file contains no recognized transaction lines.')`.
4. Wraps in `DB::transaction`:
   - Creates `BankReconciliation` record: `account_id`, `period_start`/`period_end` (from parsed dates if not provided), `opening_balance`, `closing_balance`, `status = STATUS_OPEN`, `created_by = auth()->id()`
   - Creates one `BankReconciliationItem` per line: `line_date`, `description`, `reference`, `type`, `amount`, `status = STATUS_UNMATCHED`
5. Calls `recalculateTotals($reconciliation)` → updates `matched_amount`, `variance`
6. Calls `autoMatch($reconciliation)` → attempts automatic matching

#### Step 2 — Auto-match (`BankReconciliationService::autoMatch()`)

For each unmatched item:
- Looks for posted, non-reversal `JournalEntry` records where:
  - Date is within `±MATCH_DATE_WINDOW_DAYS` (= 5 days) of the statement line date
  - Has a transaction on the reconciliation's `account_id` with `amount BETWEEN (item_amount − 0.005) AND (item_amount + 0.005)` (the ±0.005 tolerance covers floating-point mismatches)
  - Not already matched to another reconciliation item (`whereDoesntHave('bankReconciliationItems')`)
- If a match is found: updates the item to `STATUS_MATCHED`, sets `journal_entry_id`, `matched_at = now()`
- Returns count of matched items

#### Step 3 — Manual match (`BankReconciliationService::manualMatch()`)

- Guard: throws if reconciliation is completed
- Validates the requested JE is: posted, not a reversal, has a transaction on the account_id with amount within 0.005, and is not already matched
- Updates item to `STATUS_MANUALLY_MATCHED` + sets `journal_entry_id` + `matched_at`

#### Unmatch (`BankReconciliationService::unmatch()`)

- Guard: throws if reconciliation is completed
- Resets item to `STATUS_UNMATCHED`, clears `journal_entry_id` and `matched_at`

#### Step 4 — Complete (`BankReconciliationService::complete()`)

1. Calls `recalculateTotals()` one final time
2. **Variance gate:** if `abs(reconciliation->variance) > 0.005`, throws `DomainException` — cannot complete while out of balance
3. Updates status to `STATUS_COMPLETED`

Once completed:
- `isJournalEntryReconciled(JournalEntry)` returns `true` for matched entries → `AccountingService` blocks void/reverse/unpost operations
- Reconciled entries show a "Terekonsiliasi" badge via `JournalEntry::withReconciledStatus()` scope (no N+1 — eager-loads the badge in the query)

#### Opening balance prefill

`getCreateData()` returns `accountOpeningBalances` — a map of `account_id → {amount, date}` from `account.opening_balance` and `account.opening_balance_date`. The import form pre-fills the `opening_balance` field from the selected account's system Opening Balance. Users can override it to match the actual statement opening balance before importing.

#### Journal entry candidates for manual match

`getJournalEntryCandidates($reconciliation)` returns posted, non-reversal JEs with a transaction on the account, dated within `±30 days` of the period, and not yet matched — up to 200 results, ordered by date.

#### Bank Statement Parsers

Located in `app/Services/BankStatement/`. The factory `BankStatementParserFactory::make($bank)` instantiates the right parser. Each parser extends `AbstractBankStatementParser` and implements `parse(string $content): array`. The abstract class provides helpers for number parsing (Indonesian thousands separator `.`, decimal separator `,`) and date normalization.

Supported banks: `BcaStatementParser`, `BniStatementParser`, `BriStatementParser`, `MandiriStatementParser`

`BankStatementParserFactory::supportedBanks()` returns the list of supported bank slugs — used to populate the import form's bank dropdown.

---

## §38 — Billing & Plan Service

### Q38.1 How does `BillingService` handle subscription billing cycles, and what is the proration credit mechanism?

`BillingService` manages the SaaS subscription lifecycle. Key behaviors (verified from the FEATURE_CHECKLIST and version history; for complete detail see `BillingServiceTest`):

**Rolling 30-day billing cycle:**
- Each subscription period is exactly 30 days from the payment date (not calendar month)
- `Tenant::initializeBillingPeriod()` sets `billing_starts_at` and `billing_ends_at`
- 7-day renewal grace period: `CheckExpiredSubscriptions` command extends tenants 7 days beyond `billing_ends_at` before marking them as expired

**Plan upgrade — proration credit:**
- When a tenant upgrades mid-cycle, unused days on the current plan are credited as `proration_credit` on the `SubscriptionOrder` model
- The next invoice deducts the proration credit: `new_plan_price − proration_credit`
- This prevents double-charging for unused time

**Scheduled downgrade:**
- A downgrade is not applied immediately — it is stored and applied at the end of the current billing period by `ProcessScheduledPlanChanges` command
- This prevents feature access loss in the middle of a paid period

**`CheckExpiredSubscriptions` command:**
- Runs on schedule; finds tenants where `billing_ends_at` has passed the 7-day grace window
- Updates `plan_status` → `expired` and revokes plan features

**`SubscriptionOrder` model:**
- Tracks each payment: `tenant_id`, `plan_id`, `amount_usd`, `amount_idr`, `currency_code`, `status` (`pending`/`paid`/`voided`), `proration_credit`, `billing_period_start`, `billing_period_end`
- `status` enum extended with `voided` (migration `2026_08_11_000002`) — supports superadmin void-order / terminate-subscription flows

**`BillingSetting` model:**
- Singleton table `billing_settings` (one row, seeded with default 16,000)
- `BillingSetting::getRate()` returns the current superadmin-managed USD→IDR rate
- Fallback: `config('billing.fallback_rate')` = 16,000 if no row exists
- `config('billing.tax_rate')` = 0.12 (12% — added to IDR price at checkout)

---

## §39 — Accounting Audit Log vs Activity Log vs General Accounting

### Q39.1 How many parallel logging systems exist and what does each one cover?

Three separate logging systems coexist, each serving a distinct purpose:

| System | Model | Table | Purpose | Scope |
|---|---|---|---|---|
| **`ActivityLogService`** | `ActivityLog` | `activity_logs` | Business operation audit trail | 30 modules, 15 action types, 28 model observers |
| **`AccountingAuditService`** | `AccountingAuditLog` | `accounting_audit_logs` | Accounting-specific change trail with full T-account detail | Journal entries only (create/update/post/unpost/void/reverse/delete/approve/reject) |
| **`SecurityLogger`** | (writes to Laravel log channel) | Log files | Security events | Auth events: login success/failure, password change, permission denial, suspicious activity |

**Why three systems?**

- `ActivityLogService` answers "who did what to which business object" — it records module/action/entity tuples for operational compliance. Insufficient for accounting because it doesn't capture the debit/credit line detail of a journal entry.
- `AccountingAuditService` answers "what was the exact financial state before and after" — it stores the full transaction array (account code, name, debit/credit, amount) in `old_values`/`new_values` JSON. Used on the JE show page sidebar to display an audit trail with T-account changes.
- `SecurityLogger` answers "what authentication and authorization events occurred" — logged separately for security incident response, independent of the business data trail.

**`AccountingAuditLog` fields:** `tenant_id`, `action`, `entity_type` (FQCN, e.g. `App\Models\JournalEntry`), `entity_id`, `old_values` (JSON — full JE + transactions), `new_values` (JSON — same), `description`, `user_id`, `user_name`, `ip_address`, `user_agent`

Both `ActivityLog` and `AccountingAuditLog` use plain string `entity_type` / `entity_id` columns — **not** Eloquent morph relationships (no `morphTo()`, no `morphable` polymorphism). This means you can query them with raw SQL without needing Eloquent morph maps.

---

## §40 — Statistics Reference (Verified Counts)

### Q40.1 What are the verified codebase statistics as of August 2026?

All counts verified by directory listing:

| Metric | Count | How verified |
|---|---|---|
| **Controllers** | 64 | `find app/Http/Controllers -name "*.php"` (Admin×38 + SuperAdmin×16 + Api×8 + Auth/LoginController + Controller.php) |
| **Services** | 54 | `find app/Services -name "*.php"` (47 top-level + 7 BankStatement/ parsers) |
| **Models** | 54 | `ls app/Models/*.php` (top-level only, excluding Concerns/BelongsToTenant.php) |
| **API Resources** | 26 | `ls app/Http/Resources/` |
| **Admin route files** | 16 | `ls routes/admin/` |
| **Superadmin blade views** | 30 | `find resources/views/superadmin -name "*.blade.php"` |
| **Migrations** | 108 | `find database/migrations -name "*.php"` |
| **Seeders** | 36 | `find database/seeders -name "*.php"` |
| **ActivityLog MODULE_* constants** | 30 | `grep MODULE_ app/Models/ActivityLog.php` |
| **ActivityLog ACTION_* constants** | 15 | `grep ACTION_ app/Models/ActivityLog.php` |
| **AccountingAuditLog actions** | 9 | create/update/post/unpost/void/reverse/delete/approve/reject |
| **PHP test methods** | 1186 | PHPUnit output (3715 assertions) |
| **JS test files** | 169 | `tests/js/` |
| **Permissions** | 101 | `PermissionSeeder` (verified in prior session) |
| **Languages** | 2 | `lang/en/`, `lang/id/` |

---

## §41 — CRM Service Deep Dive

### Q41.1 What does `CrmService` cover and how is Customer Lifetime Value calculated?

`CrmService` powers the CRM modal panels for both customers (`CustomerCrmController`) and suppliers (`SupplierCrmController`). It does **not** handle pipeline management — that is `SalesPipelineService`.

#### Customer CRM (`getCustomerCrmData($id, $page)`)

Returns a comprehensive profile for one customer:

| Key | Calculation |
|---|---|
| `lifetimeValue` | Sum of `sale->getAdjustedTotal()` for ALL historical sales (returns-adjusted grand totals) |
| `totalSalesCount` | Count of all sales records for this customer |
| `averageOrderValue` | `lifetimeValue / totalSalesCount` (0 if no sales) |
| `favoriteCategory` | Category with highest total adjusted quantity across all sales items (arsort on categoryCounts array) |
| `mostPurchasedProduct` | Product with highest total adjusted quantity (arsort on productQuantities array) |
| `totalProductsPurchased` | Sum of all adjusted quantities across all sales items |
| `lastPurchaseDate` | `allSales->max('created_at')` |
| `lastInteractionDate` | Max `interaction_date` from `CustomerInteraction` records |
| `customerCredits` | Latest 20 `CustomerCredit` records: id, amount, used_amount, available_amount, reason, status, expires_at |
| `totalAvailableCredits` | Sum of `available_amount` for active (non-expired, not fully-used) credits |
| `sales` | Paginated (10/page) sales with: returned_amount, summary totals, paid amounts, balance per invoice |

**Return quantity adjustment:** For each `SalesItem`, `returnedQty = sale->getReturnedQuantityForProduct(product_id)`. Items with `adjustedQty <= 0` are excluded from category/product counting — this prevents fully-returned items from inflating metrics.

**LTV note:** `getAdjustedTotal()` accounts for returns — it subtracts the total returned amount from the sale total. So LTV truly reflects net revenue, not gross billed.

#### Supplier CRM (`getSupplierCrmData($id, $page)`)

Mirror structure to customer CRM but for suppliers/purchases:
- `lifetimeValue` = sum of `purchase->getAdjustedTotal()` (net of purchase returns)
- `averagePurchaseValue` = `lifetimeValue / totalPurchasesCount`
- Same favoriteCategory and mostPurchasedProduct logic using purchase items

#### Interaction Logging

`storeCustomerInteraction(data, customerId)` and `storeSupplierInteraction(data, supplierId)` create `CustomerInteraction` / `SupplierInteraction` records with fields: `customer_id`/`supplier_id`, `user_id` (Auth::id()), `type`, `notes`, `interaction_date`. Returns the interaction loaded with its `user` relationship.

Interaction `type` values come from the frontend — no enum constraint in the model (flexible).

#### Historical Purchase Views

`getHistoricalPurchases(Customer)` — flat list of all sales items for a customer, adjusted for returns, with `customer_latest_price` appended (the most recent price paid for each product, looked up by `order_date` comparison). Used by the CRM modal's "Price History" tab.

`getCustomerProductHistory($id)` — groups the same data by `product_name`, showing a per-product timeline of purchases with returned/adjusted quantities. Used by the "Product History" tab.

`getSupplierHistoricalPurchases($id)` and `getSupplierProductHistory($id)` — equivalent methods for suppliers, using purchase data.

---

## §42 — Accounting Reports: Three-Service Architecture

### Q42.1 What does each accounting report service cover and how do they differ?

Three services handle accounting reports. They operate on distinct report types and data scopes:

| Service | Reports Covered | Data Source | Scope |
|---|---|---|---|
| `AccountingReportService` | Trial Balance, Chart of Accounts | `Account` model + `Transaction` model | Balance-to-date (cumulative from inception to `endDate`) |
| `FinancialReportingService` | Income Statement (P&L), Balance Sheet | `Account` model + `Transaction` model | Period-based (startDate–endDate for P&L; inception-to-endDate for Balance Sheet) |
| `GeneralLedgerService` | General Ledger, Journal Entries list | `Transaction` model + `JournalEntry` model | Per-account, paginated, opening balance calculated |

#### `AccountingReportService`

**`generateTrialBalance(endDate)`:**
- For each `Account`, computes `total_debits` and `total_credits` from posted journal entries up to `endDate` using `withSum()`
- Normalizes to debit/credit balance column: asset/expense accounts → positive balance goes to debit column; liability/equity/revenue accounts → positive balance goes to credit column
- Skips zero-balance accounts
- Returns `report_data[]`, `total_debits`, `total_credits`, `is_balanced` (bool), `end_date`

**`getChartOfAccounts()`:** Returns parent accounts (`whereNull('parent_id')`) with `children` eager-loaded, ordered by `code`. Used by the Chart of Accounts view.

**`flattenAccountHierarchy(accounts, level)`:** Recursive flattener for CSV/PDF export — prepends `str_repeat('-', level)` to names to show hierarchy depth.

#### `FinancialReportingService`

**`generateIncomeStatement(startDate, endDate)`:**
- Revenue accounts: credits − debits in period (posted JEs only)
- Expense accounts: debits − credits in period (posted JEs only)
- `net_income = total_revenue − total_expenses`
- Both use `JournalEntry::STATUS_POSTED` filter — draft/unposted entries excluded

**`generateBalanceSheet(endDate)`:**
- Gets all accounts with cumulative balances up to `endDate` (all time, not period-limited)
- Assets: `calculateAccountBalance()` = debits − credits
- Liabilities/Equity: credits − debits
- Revenue and Expense type accounts: their net balances are added to `totalEquity` (retained earnings rolled in)
- `equation_balanced = abs(totalAssets − (totalLiabilities + totalEquity)) < 0.001`

#### `GeneralLedgerService`

**`getGeneralLedger(accountId, startDate, endDate, perPage=50)`:**
1. `calculateOpeningBalance(accountId, startDate)` — raw `DB::table()` aggregate (no Eloquent hydration) summing all debit/credit transactions with posted JEs **before** `startDate`. Adds `account->opening_balance` (seeded initial balance on the Account model)
2. Fetches paginated transactions in the period, eager-loading `journalEntry` with `withReconciledStatus()` scope
3. `calculatePeriodChange(transactions)` = sum of (debit amounts − credit amounts) for in-period transactions
4. `closingBalance = openingBalance + periodChange`

**Opening balance raw SQL pattern:** Uses `DB::table('transactions')->join('journal_entries', ...)` with `selectRaw("SUM(CASE WHEN type='debit' THEN amount ELSE 0 END) as total_debit, ...")` — avoids hydrating potentially thousands of historical Transaction models into RAM. This is a deliberate N+1 prevention optimization.

**`getJournalEntries(startDate, endDate, perPage=20)`:** Paginated JE list with `transactions.account` eager-loaded and `withReconciledStatus()` scope applied. Used by the Journal view (`admin.accounting.journal`).

---

## §43 — Recurring Journal Entries

### Q43.1 How do recurring journal entries work and are they scheduler-wired?

**Model:** `RecurringJournalEntry` — fields: `name`, `description`, `frequency`, `interval`, `start_date`, `end_date` (nullable), `last_generated_at`, `next_generation_date`, `transactions` (JSON array of `{account_code, type, amount}`), `template_account_code` (nullable), `is_active` (bool), `created_by`.

#### Frequencies (5 constants)
`FREQUENCY_DAILY`, `FREQUENCY_WEEKLY`, `FREQUENCY_MONTHLY`, `FREQUENCY_QUARTERLY`, `FREQUENCY_YEARLY`

#### `isDueForGeneration()` — three conditions, ALL must pass:
1. `is_active` must be true
2. If `end_date` is set, `now()` must not be after `end_date`
3. `now()` must be on or after `next_generation_date` (same day counts via `isSameDay()`)

#### `calculateNextGenerationDate()` — uses `interval` multiplier:
- Daily: `+$interval days`
- Weekly: `+$interval weeks`
- Monthly: `+$interval months`
- Quarterly: `+($interval × 3) months`
- Yearly: `+$interval years`

#### generateJournalEntry() -- called on the model itself:
1. Calls isDueForGeneration() -- returns null if not due
2. Calls AccountingService::createJournalEntry(description, now(), transactions[], model) -- passes the RecurringJournalEntry as the sourceable polymorphic model
3. Updates last_generated_at = now() and next_generation_date = calculateNextGenerationDate()

#### ManualJournalEntryService::processRecurringEntries(): int
- Fetches RecurringJournalEntry::dueForGeneration()->get() (scope: active + next_generation_date <= today + end_date not passed)
- Wraps each generateJournalEntry() in DB::transaction()
- Logs errors and continues -- partial failures do not abort the batch
- Returns count of successfully processed entries

#### SCHEDULER NOT WIRED -- CRITICAL FACT
processRecurringEntries() exists in the service but is **not called by any scheduled command** in routes/console.php. The three scheduled commands are:
1. tenant:reset-demo -- daily at 00:00
2. billing:process-scheduled-changes -- daily at 01:00
3. billing:check-expired -- daily at 01:30

**Recurring journal entries must be triggered manually** -- they do NOT auto-process on a schedule. This is a known gap for a future roadmap item.

#### scopeDueForGeneration scope:
Active entries where next_generation_date <= today AND (end_date IS NULL OR end_date >= today).

---

## §44 — SecurityLogger

### Q44.1 What does SecurityLogger log and where does it write?

SecurityLogger is a pure static utility class (no constructor, no DI). It writes exclusively to the `security` log channel via `Log::channel('security')`.

**5 static methods:**

| Method | Log Level | Fields Logged |
|---|---|---|
| `logPasswordChange($user)` | `info` | user_id, email, ip, user_agent, timestamp |
| `logFailedLogin($email, $reason)` | `warning` | email, reason, ip, user_agent, timestamp |
| `logSuccessfulLogin($user)` | `info` | user_id, email, ip, user_agent, timestamp |
| `logPermissionDenied($user, $permission)` | `warning` | user_id, email, permission, route, ip, timestamp |
| `logSuspiciousActivity($description, $data=[])` | `alert` | description, user_id, ip, user_agent, timestamp + merged extra data |

**Key distinctions from ActivityLogService:**
- SecurityLogger writes to log **files** only (security channel) — no DB table
- ActivityLogService writes to the `activity_logs` **database table**
- Log files are append-only and tamper-resistant; DB records are queryable but mutable
- `logSuspiciousActivity` accepts arbitrary extra context via `array_merge` — the only method that does
- Security events are NOT queryable via Eloquent

---

## §45 — TransactionService

### Q45.1 What does TransactionService actually do?

TransactionService is the full implementation of the "Recent Transactions" unified view — merging Sales and Purchases into one paginated list. Injected with SalesService and PurchaseService via constructor.

**getTransactions(filters, perPage=25): LengthAwarePaginator**
- Named date ranges: today / this_week / this_month / last_month / custom
- Builds separate Sales and Purchase Eloquent queries
- Maps each to TransactionDTO (id, type, invoice, customer_supplier, contact_info, date, amount, paid_amount, due_amount, status, view_url, edit_url)
- Merges and sorts in-memory, paginates with LengthAwarePaginator manual slice

**getTransactionsSummary(filters): array**
- Two `selectRaw()` aggregate queries
- Returns: total/sales/purchases count+amounts, paid/unpaid breakdowns

**bulkMarkAsPaid(transactions[]): int**
- Composite ID format: `sale_123` / `purchase_456` (split on underscore)
- Delegates to SalesService::bulkMarkPaid() and PurchaseService::bulkMarkPaid()

**markTransactionAsPaid(id, type): array**
- Balance > 0: calls addPayment with [amount=balance, payment_method='Unknown']
- Balance = 0: directly sets status='Paid', payment_date=now()

**bulkExportTransactions(filters, ids, exportOption)**
- PDF: renders `admin.reports.recent-transactions-bulk-export-pdf` via Dompdf, landscape A4
- CSV: Type, Invoice, Customer/Supplier, Date, Amount, Status

**calculatePaidAmount logic:**
- status='Paid' returns total
- Sales with status='Partial' returns amount_received
- Otherwise returns 0

---

## §46 — TenantSetupService

### Q46.1 Walk through TenantSetupService::setup() step by step.

Called whenever a new tenant is provisioned. Steps in order:

1. `set_time_limit(0)` — prevents PHP timeout
2. `$tenant->makeCurrent()` — sets tenant DB context
3. **assignPlan($tenant, $planSlug)**:
   - Gracefully skips if `Plan::count() === 0` (no plans seeded yet)
   - Resolves slug: `$planSlug ?? config('plans.default_plan', 'starter')`
   - Checks `$plan->hasTrial()` — Professional and Enterprise offer trials; Starter does not
   - Calls `PlanService::assignPlanToTenant($tenant, $slug, $startTrial)`
4. **runEssentialSeeders()** — 5 seeders in order (each individually try/caught):
   - AccountSeeder — Chart of Accounts
   - CategorySeeder — default product categories
   - UnitSeeder — units of measurement
   - CurrencySeeder — default currency settings (IDR)
   - TaxSeeder — default tax configurations
   - Seeder failures are logged and skipped; setup continues
5. **createDefaultWarehouse($tenant)** — name='Main Warehouse', is_main=true
6. **createDefaultCustomer($tenant)** — name='Walk-in Customer', email='cash@{tenant->domain}', phone='-', address='-' (POS default)
7. `Tenant::forgetCurrent()` — clears tenant context

**Called from:** FortifyServiceProvider (Fortify registration) and Artisan commands for manual provisioning.

---

## §47 — AccountingExportService vs ReportExporter

### Q47.1 What is the difference between the two export services?

Both have identical base `exportToPdf()` and `exportToCsv()` methods. The distinction is domain scope and default PDF orientation:

| | AccountingExportService | ReportExporter |
|---|---|---|
| Default PDF orientation | landscape | portrait |
| Domain | Accounting module only (JE, CoA, Trial Balance, GL) | All other reports (P&L, Balance Sheet, Aging, Adj Log, Recent Transactions, Cash Flow) |
| Used by | AccountingController, JournalEntryController | ReportController, TransactionService |

**AccountingExportService specific methods:**
- exportChartOfAccountsToPdf/ToCsv — CoA with hierarchy, CSV headers: Code, Name, Type, Level
- exportJournalToPdf/ToCsv — date/description only on first transaction row per JE
- exportTrialBalanceToPdf/ToCsv — Code, Account, Debit, Credit + totals row
- exportGeneralLedgerToPdf/ToCsv — running balance per row, opening/closing balance rows

**ReportExporter specific methods:**
- exportIncomeStatementToCsv — Revenue + Expense sections + Net Income
- exportBalanceSheetToCsv — Assets + Liabilities + Equity + Total L&E
- exportAgingToCsv(aging, filename, reportType) — 'receivables'/'payables'; per-bucket with contact, invoice, due_date, days_overdue, amount
- exportAdjustmentLogToCsv — Date, Product, Type, Qty Before/After, Change, Reason, Adjusted By
- exportRecentTransactionsToCsv — Type, Invoice, Customer/Supplier, Date, Amount, Status
- exportCashFlowToCsv — Operating/Investing/Financing sections + Net Change + Opening/Closing Cash

---

## §48 — Sales Pipeline Service Deep Dive

### Q48.1 Walk through the full pipeline lifecycle from opportunity creation to sales order conversion.

SalesPipelineService manages the full CRM pipeline. Methods verified from actual source code.

#### Stage Reordering — Double-Write Pattern

`reorderStages(pipeline, stages[])` inside DB::transaction():
1. Pass 1: Set each stage position to `stageId + 1,000,000` (avoids UNIQUE constraint conflicts)
2. Pass 2: Set final target positions

Prevents duplicate key violations when swapping adjacent stages.

#### createOpportunity(data) inside DB::transaction():
1. Creates SalesOpportunity with amount=0
2. Creates all SalesOpportunityItem records (price rounded to 2dp)
3. Fetches default tax: `Tax::default()->first() ?? Tax::active()->first()`
4. Tax exclusive: grandTotal = subtotal + taxAmount; inclusive: grandTotal = subtotal
5. Updates opportunity amount = round(grandTotal, 2)

#### deleteOpportunity — preserves financial records
Does NOT delete linked Sales Order. FK is ON DELETE SET NULL — `sales_opportunity_id` on Sales becomes null.

#### generateQuotation(opportunityId) — Opportunity to Draft Quotation:
- Number: `QTN-{Ymd}-{zero-padded (tenant count+1)}`
- Copies opportunity items (discount=0, tax_type='none' per item)
- Applies default tax at header level
- valid_until = now() + 14 days, status = 'draft'
- Links via `sales_opportunity_id` on Quotation
- Does NOT mark opportunity as 'converted'

#### convertToSalesOrder(opportunity) — Won Opportunity to Sales Order:

Guards (throws on violation):
- status !== 'won' — "must be in a won status"
- Already converted or has sales_id — "already been converted"
- No items — "No products associated"
- No warehouse — "No warehouse available"
- Insufficient stock — "Insufficient stock for product: {name}. Available: {n}, Requested: {m}"

Execution inside DB::transaction():
1. Invoice: `PL-{zero-padded sequential}` (scans all `PL-%` invoices for max number)
2. Warehouse: `Warehouse::where('is_main', true)->first()` fallback to `Warehouse::first()`
3. Creates Sales (type=TYPE_REGULAR, status='Unpaid', copies notes/terms_conditions)
4. Creates SalesItem per item (discount=0, discount_type='fixed')
5. Decrements ProductWarehouse.quantity at main warehouse (throws if insufficient)
6. Sets opportunity: status='converted', sales_id=salesOrder->id

**Invoice prefix PL-** (Pipeline) distinguishes pipeline-converted orders from regular sales.
**moveOpportunity:** Simple update(['pipeline_stage_id' => $stageId]). Cross-pipeline moves allowed.

---

## §49 — invent-mag-fe Frontend Architecture Audit

### Q49.1 What does the Astro marketing site do and how does it connect to the backend?

**Framework:** Astro static site generator + Tailwind CSS + TypeScript. Deployed on Vercel. Build-time API calls pre-render content; fallback defaults ensure the build succeeds even when backend is offline.

#### Pages

| URL | File | Build-time API Calls |
|---|---|---|
| / | pages/index.astro | getCmsContent() + getPlans() |
| /pricing | pages/pricing.astro | getCmsContent() + getPlans() |
| /contact | pages/contact.astro | getCmsContent() |
| /login | pages/login.astro | None (fully static) |
| /signup | pages/signup.astro | None (fully static) |
| /id/* | Indonesian locale | Same as English counterparts |

#### API Integration (src/lib/)

**getPlans() — GET /api/plans:**
- 5-second timeout via AbortSignal.timeout(5000)
- Fallback PLAN_DEFAULTS: Starter $19, Professional $59, Enterprise $109 (all USD, monthly, 30-day trial)
- Limits (PlanSummary interface): Starter (3 users, 1 warehouse, 100 products), Professional (10/-1/500), Enterprise (-1/-1/-1)
- -1 means unlimited

**getCmsContent() — GET /api/cms:**
- 5-second timeout, merges: { ...CMS_DEFAULTS, ...data }
- 50+ content fields: hero, 11 feature cards (title+desc each), pricing badges, CTA, contact_email, footer_text
- billing_rate: 16000 — IDR/USD rate from superadmin BillingSetting; used for IDR price display on pricing page

#### Login Page (/login)
- noindex=true, nofollow=true — not crawled by search engines
- Single field: shop name (tenant subdomain)
- /js/login.js calls POST /api/lookup-tenant with JSON body { shopname } (throttle:10,1) — returns { tenant_domain }
- Redirects browser to https://{tenant_domain}/admin/login

#### Signup Page (/signup)
- noindex=true, nofollow=true
- Hidden input name="plan" value="professional" — all signups default to Professional trial regardless of pricing tier viewed
- Fields: name, shopname, email, password, password_confirmation, terms checkbox
- Two-step UX: submit form -> confirmation modal (shows name/shopname/email) -> confirmRegistration() -> POST /api/register via /js/register.js -> success modal with window.tempTenantLoginUrl -> "Go to Login" button
- Key finding: plan='professional' is hardcoded; the pricing page selection does not carry over

#### i18n
- Two locales: en and id (Indonesian)
- data-i18n="key" attributes on HTML elements
- Client-side JS swaps text based on localStorage or browser navigator.language
- Indonesian pages at /id/* are separate Astro files (not dynamic locale routing)

#### Environment Variable
- PUBLIC_BACKEND_URL — defaults to https://api.invent-mag.web.id in both plans.ts and cms.ts
- Set in Vercel environment for production

## §50 — invent-mag-fe Frontend Architecture Audit (Components & i18n)

### Component Architecture
The Astro frontend (static site) uses modular components that accept `cms` props to inject superadmin-controlled text.

#### Core Components
- **Hero.astro**: Uses glowing background effects (`bg-hero-glow`, `blur-[120px]`). CTA defaults to `/signup`, Demo defaults to `demo-pro.invent-mag.web.id`.
- **Pricing.astro**: 
  - Dynamic prices calculated via `priceFor(slug, fallback)`. 
  - The Enterprise tier explicitly does NOT link to `/signup`; its CTA links to `/contact?subject=sales` with text "Contact Sales". The Professional CTA also links to Contact Sales, not signup! (A likely UX bug vs starter tier).
- **Features.astro**: Displays 8 standard features and 3 enterprise features (Sales Forecasting AI, RESTful API, Audit Trail).
- **Testimonials.astro**: Uses Alpine.js (`x-data="{ currentTestimonial: 0 }"`) for an auto-rotating carousel every 5 seconds. Uses Holt-Winters and FIFO in the fake quotes.
  - **Bug**: Contains a stray duplicate `</figure>` closing tag at line 67.
- **LogoCloud.astro**: Inverts client logos using CSS (`brightness-0 invert`) and animates them sequentially using `/js/logo-animation.js` (IntersectionObserver).
- **Stats.astro**: 5 animated counters (Accounts, Permissions, Roles, Double-Entry %, Forecasting x). Animated via `/js/stats-animation.js` (IntersectionObserver + requestAnimationFrame with easeOutExpo).

#### Layout & SEO (BaseLayout.astro)
- Uses **astro-seo** package for comprehensive meta tags, OpenGraph, and Twitter Cards.
- Injects three JSON-LD Schema objects:
  - `SoftwareApplication`: Declares ERP Software category, lists all pricing tiers (19, 59, 109).
  - `Organization`: Links to GitHub and LinkedIn.
  - `WebSite`: Declares Sitelinks Search Box support.
- Hardcodes the IDR exchange rate via `PUBLIC_USD_TO_IDR` fallback if `cms.billing_rate` is missing (default 16000).
- Implements `hreflang` correctly (EN at root `/`, ID at `/id/`).

#### Client-Side i18n implementation
- **Static Translation**: Astro pages at `/id/*` for Indonesian route resolution.
- **Dynamic Translation**: `/src/components/i18n.astro` and `i18n.ts` inject a global `translations` object (en and id) containing ~100 keys.
- **DOM Updates**: `translatePage()` runs on load and `locale-changed` event. It finds all elements with `data-i18n="key"` and replaces `textContent`.
- **Price Formatting**: Elements with `data-i18n-price` are dynamically calculated to Rupiah if `locale === 'id'` using the backend `billing_rate`.

## §51 — invent-mag-fe Frontend Architecture Audit (Client JavaScript)

The site avoids heavy frameworks, relying exclusively on Alpine.js (`<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>`) and vanilla JS for interactivity.

#### Navigation & Mobile Menu (Navigation.astro)
- Alpine state `x-data="{ mobileMenuOpen: false }"`.
- Custom `LanguageSwitcher` inside Nav uses Alpine `x-show` for the dropdown, writes to `localStorage`, and dispatches a `locale-changed` CustomEvent.
- Live Demos dropdown links to both `demo-starter` and `demo-pro` subdomains.

#### Login Flow (/js/login.js)
- Vanilla JS event listener on `#lookupForm`.
- Prevents default, shows "Finding...".
- Calls **POST `/api/lookup-tenant`** with `{ shopname }`.
- On success (200), redirects directly to the tenant's admin panel: `https://${result.tenant_domain}/admin/login`.

#### Registration Flow (/js/register.js & /js/modal.js)
- **Modal System**: Custom `Modal` object handles `show()`, `hide()`, `showSuccess()`, `showError()`, and `showConfirmation()`. Esc key and click-outside listeners supported.
- **Step 1 (Intercept)**: Submit intercepts the form, populates the confirmation modal via `[data-confirm="..."]` elements, and calls `Modal.showConfirmation(data)`.
- **Step 2 (Submit)**: The "Confirm & Register" button triggers `confirmRegistration()`.
- Calls **POST `/api/register`**.
- **Error Handling**: Handles 422 (Validation, specifically checking for "taken" email to show custom modal), 429 (Too many attempts), and 500.
- **Success**: Shows success modal and sets `window.tempTenantLoginUrl = https://${result.tenant_domain}/admin/login` for the final redirect button.
## §52 — Inventory & POS Architecture (Product, Warehouse, POS Services)

### Inventory (ProductService)
The `ProductService` handles core catalog and stock management.
- **Stock Optimization**: When fetching product lists, it eager-loads `productWarehouses` and filters them. It manually injects `total_stock` and `main_warehouse_stock` into the collection mapping to avoid hitting heavy N+1 accessors during rendering.
- **Stock Adjustments**: `adjustProductStock()` and `bulkUpdateStock()` wrap stock changes in a DB transaction. They increment/decrement the pivot table `ProductWarehouse` and log an explicit `StockAdjustment` (adjustment_type: increase, decrease, or correction).
- **Exports**: Bulk export directly streams CSV/PDF payloads using Dompdf or native PHP `fputcsv`.

### Warehouse Service (Multi-Location)
- **Plan Limits**: Validates warehouse creation against the tenant's current SaaS plan via `PlanService->canAddWarehouse()`. (Backward compatibility: If PlanService fails because the table doesn't exist during early seeding, it catches the exception and allows creation).
- **Main Warehouse**: Ensures exactly one warehouse has `is_main = true`. Changing it uses a transaction to unset all others before setting the new one.
- **Stock Validation**: Most transactional systems (POS) default to the `main` warehouse if not specified.

### POS Service (Sales Flow)
The POS is tightly integrated with the `AccountingService` and localized tax logic.
- **Stock Validation**: `validateStockBeforeSale` verifies the requested quantities against the specific warehouse (usually main). Throws exceptions listing the specific products short on stock.
- **Accounting Integration**: A completed POS sale creates real-time Journal Entries using the tenant's mapped accounts:
  1. `Dr Accounts Receivable` / `Cr Sales Revenue`
  2. `Dr Cost of Goods Sold` / `Cr Inventory`
  3. `Dr Cash` / `Cr Accounts Receivable` (if paid immediately)
- **Indonesian Tax Localization**:
  - **PPN Keluaran (Output VAT)**: If PPN is enabled, it credits the PPN Keluaran liability account *at sale creation*, subtracting the tax amount from Sales Revenue. This prevents Accounts Receivable and Revenue overstatement.
  - **PPh (Withholding Tax)**: If PPh is enabled (e.g., PPh 23), it deducts the withheld amount from the grand total (reducing cash collected) and posts `Dr PPh Receivable` / `Cr Accounts Receivable`.

## §53 — Purchasing & e-Faktur (Localization)

### Purchase Service
Handles procurement, receiving, and accounts payable.
- **Index Optimization**: Instead of fetching all POs to calculate dashboard summaries, it runs a raw DB aggregate query using `SUM(CASE WHEN suppliers.location = 'IN' THEN ...)` to group unpaid local (IN) vs international (OUT) payables in one hit.
- **Stock & Accounting Integration**: When a PO is marked "Received", it increments the `ProductWarehouse` stock and posts journal entries (`Dr Inventory`, `Cr Accounts Payable`).

### EFakturService (Indonesian DJP Tax Integration)
Handles the highly specific regulatory requirements for Indonesian VAT (PPN) reporting.
- **NSFP Pool (Nomor Seri Faktur Pajak)**: Taxes require serial numbers issued by the government. The service imports these blocks (via `importNsfpPool`).
- **Pessimistic Locking**: `generateFakturPajak()` uses `NsfpPool::available()->lockForUpdate()->first()` inside a DB transaction to guarantee that two concurrent checkout requests never get assigned the exact same DJP serial number.
- **CSV Export (`exportCsvForDjp`)**: Generates the `FK` (Faktur Keluaran) and `OF` (Object Faktur) CSV lines required by the DJP desktop app. 
  - **BOM Injection**: Injects the UTF-8 Byte Order Mark (`\xEF\xBB\xBF`) to ensure Indonesian Windows/Excel environments open the CSV correctly without mangling characters.
  - DJP requires amounts formatted as integers (cents), avoiding floating-point rounding errors during import.
- **PKP Check**: Verifies the tenant's `pkp_status` (Pengusaha Kena Pajak / VAT-registered Entity) before allowing any tax invoice generation.## §54 — Controller & Routing Architecture

### Routing Strategy
The application eschews a monolithic `web.php` file in favor of heavily segmented routing:
- **`routes/api.php`**: Exclusively handles "central" API logic for the static frontend marketing site (Astro). Contains endpoints for CMS data, tenant lookup (`POST /api/lookup-tenant`), public marketing registration, and Midtrans webhooks.
- **`routes/superadmin.php`**: Handles the SaaS management layer (Billing, Tenant management, global CMS settings).
- **`routes/admin/*.php`**: The core ERP tenant routes are deeply modularized into 16 separate files (`accounting.php`, `hr.php`, `inventory.php`, `sales.php`, etc.), making the route definitions highly maintainable.

### Controller Hierarchy
Controllers live in three distinct namespaces corresponding to the routes:
- **`App\Http\Controllers\Api`**: Minimal, unauthenticated endpoints supporting the Astro frontend.
- **`App\Http\Controllers\SuperAdmin`**: SaaS backend. Includes `TenantController` for instance lifecycle and impersonation.
- **`App\Http\Controllers\Admin`**: The main tenant ERP logic (60+ controllers).

### Hybrid Request/Response Pattern (Admin Controllers)
Instead of building a separate `V1` API namespace for SPA or external consumption, the `Admin` controllers use a hybrid pattern. Inside controller methods (e.g., `store`, `update`, `destroy`), the code checks for `$request->header('X-Laravel-JSON')`:
- **If Present**: Returns a `JsonResponse` (useful for AJAX modals, headless API consumption, or Turbo drive).
- **If Absent**: Returns traditional Laravel `redirect()->route(...)` with flashed session data (`with('success', ...)`).
- **Delegation**: The controllers themselves are extremely thin, heavily relying on injection of `App\Services\*` to perform the actual business logic (e.g., `ProductController` delegates to `ProductService`).

### Cross-Domain Impersonation Flow
Because tenants reside on isolated subdomains (e.g., `acme.invent-mag.web.id`) while the superadmin panel is on the root or a different subdomain, session cookies cannot be natively shared. 
The system implements a clever token-based impersonation handoff in `SuperAdmin\TenantController::impersonate()`:
1. Generates an encrypted payload containing the `impersonating_tenant_id`, `superadmin_return_url`, and the target tenant user ID.
2. Redirects the superadmin to `https://{tenant.domain}/admin/dashboard?impersonation_token={encrypted_payload}`.
3. A middleware (`CaptureImpersonation`) intercepting the tenant domain decrypts this token, forcefully logs the superadmin into the `web` guard as that tenant's user, sets session variables, and drops the token from the URL.
4. To exit, a `POST /admin/stop-impersonating` route (defined in `web.php`) clears the session and redirects the user back across domains to the stored `superadmin_return_url`.