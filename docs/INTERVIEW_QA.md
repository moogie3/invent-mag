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
- ~~The `file` cache driver does not scale horizontally~~ — **Redis is now the default cache and queue driver (May 2026)**; this is no longer an open issue
- A candidate who still says "file cache doesn't scale" without knowing it's been fixed is demonstrating they haven't read the current code

---

### Q1.2 — What tech stack does this project use? List both backend and frontend.

**Answer:**

**Backend:**
- PHP 8.2 with Laravel 11.31
- `spatie/laravel-multitenancy` ^4.0 — domain-based multi-tenancy
- `spatie/laravel-permission` ^6.20 — RBAC (roles + permissions)
- `spatie/laravel-backup` ^9.3 — encrypted per-tenant backups
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

**Infrastructure:** Docker, Railway (PaaS), GitHub Actions CI/CD.

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
- `NeedsTenant` — enforces that a valid tenant context exists; aborts 404 if tenant not found
- `HandleTenantFromPath` — custom middleware that resolves tenant on each request

**Critical weakness a senior engineer should identify:**
Raw `DB::table()` queries bypass the global scope and do NOT automatically filter by `tenant_id`. Developers must manually add `->where('tenant_id', $tenantId)` on raw queries. The codebase does this deliberately for performance-critical aggregation queries (e.g., dashboard metrics), but it is a potential source of data leakage if a developer forgets.

---

### Q2.2 — What happens when a tenant is not found? What is the fallback behavior?

**Answer:**

When `DomainTenantFinder` cannot match the HTTP `Host` header to any tenant record in the `tenants` table, `NeedsTenant` middleware aborts the request with a **404 HTTP response**. There is no fallback to a "default" tenant.

This design is intentional: failing closed (404) is safer than accidentally serving another tenant's data or an unauthenticated dashboard.

**Edge case:** The main marketing/landing page domain (non-tenant domain) must be excluded from the `NeedsTenant` middleware group. API routes for registration and tenant lookup (`/api/tenant-lookup`) are also excluded and run on the landlord application context.

---

### Q2.3 — What are the `tenants` table columns and what do the billing-related columns do?

**Answer:**

The `tenants` table (landlord migration) contains:

| Column | Purpose |
|---|---|
| `id` | Primary key |
| `name` | Tenant/company name |
| `domain` | Subdomain (e.g., `acme.127.0.0.1.nip.io`) |
| `plan_id` | Current active plan FK |
| `plan_status` | `active`, `trial`, `expired` |
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
- `id, description, date, status (draft/posted/void), entry_type (standard/opening/reversal)`
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
- `void` → entry is cancelled; a voiding entry is created with reversed amounts
- `reversal` — a correcting entry that negates a previously posted entry

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

**Plan features per tier:**

| Feature Slug | Starter | Professional | Enterprise |
|---|---|---|---|
| `products, inventory, sales, purchases, invoices, dashboard, basic_reports, customers, suppliers` | ✓ | ✓ | ✓ |
| `pos, stock_opname, accounting, crm, sales_pipeline, financial_reports, multi_currency, sales_returns, purchase_returns` | ✗ | ✓ | ✓ |
| `sales_forecasting, api_access, audit_compliance` | ✗ | ✗ | ✓ |

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

**Payment status is not stored** as a permanent column — it is **computed dynamically** based on `total`, payments, and credit used. This prevents inconsistency between the stored status and actual payment data.

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
public function validateTransactions(array $transactions): void
{
    $totalDebits = collect($transactions)
        ->where('type', 'debit')
        ->sum('amount');
    $totalCredits = collect($transactions)
        ->where('type', 'credit')
        ->sum('amount');

    if (abs($totalDebits - $totalCredits) > 0.001) {
        throw new UnbalancedJournalEntryException(
            "Debits ($totalDebits) do not equal credits ($totalCredits)"
        );
    }

    if (count($transactions) < 2) {
        throw new InvalidJournalEntryException("Minimum 2 transaction lines required");
    }
}
```

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

The `PayrollService::calculatePayrollItem()` uses this formula:

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

**Holt-Winters Triple Exponential Smoothing** (also called "Triple Exponential Smoothing") decomposes a time series into three components:

1. **Level (α = 0.3)** — the current baseline value of the series, analogous to a smoothed average
2. **Trend (β = 0.1)** — the direction and magnitude of change over time (upward/downward slope)
3. **Seasonality (γ = 0.1)** — repeating patterns over a fixed seasonal period (12 months for monthly sales data)

**Parameters used in this system:**
- α = 0.3 (level smoothing factor) — initial seed, optimized per-call via grid search
- β = 0.1 (trend smoothing factor) — initial seed, optimized per-call via grid search
- γ = 0.1 (seasonal smoothing factor) — initial seed, optimized per-call via grid search
- Season length (`m`) = 12 months
- **Optimization:** `optimizeHoltWinters()` performs a 125-combination grid search across α, β, γ ∈ `[0.1, 0.3, 0.5, 0.7, 0.9]` minimizing RMSE on in-sample data. The optimized parameters are used per-forecast call, not the hardcoded seeds above.

**Update equations:**
```
L_t = α × (Y_t / S_{t-m}) + (1-α) × (L_{t-1} + b_{t-1})   [Level]
b_t = β × (L_t - L_{t-1}) + (1-β) × b_{t-1}                 [Trend]
S_t = γ × (Y_t / L_t) + (1-γ) × S_{t-m}                    [Seasonal index]

Forecast(t+h) = (L_t + h × b_t) × S_{t-m+h_mod_m}          [Prediction]
```

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
- Tokens are created via `POST /api/auth/login`
- All `/api/` routes (except registration and webhook) require `auth:sanctum` middleware
- Rate limited to 60 requests/minute

**Why both?** Web users use session cookies (more secure for browser-based access — no token exposure in JavaScript). API clients use tokens (stateless, cross-origin compatible). Using a single auth mechanism for both would require either exposing tokens to the browser (XSS risk) or requiring cookies for APIs (CORS complexity).

---

### Q10.2 — Explain the RBAC system. How are roles and permissions structured?

**Answer:**

The system uses **`spatie/laravel-permission`** with a custom `Role` model that extends Spatie's.

**Structure:**
- There are **101 permissions** defined (the exact list is seeded via `PermissionSeeder`)
- Permissions follow the pattern: `{action}-{resource}` (e.g., `create-products`, `view-accounting`, `delete-users`, `manage-roles`)
- Roles are created per-tenant and assigned permissions
- Users are assigned roles

**Default roles (seeded on tenant creation):**
- `Superuser` — all permissions within the tenant scope (note: this is a tenant-level Spatie role, NOT the same as the `SuperAdmin` model which is a separate platform-level entity)
- `Manager` — most permissions except user/role management
- `Staff` — limited permissions (view + basic CRUD)
- `Accountant` — accounting-focused permissions
- `HR` — HR module permissions

Custom role management (creating new roles with custom permission sets) is available to Enterprise plan tenants via permission-based access control.

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

**`'unsafe-eval'` (open issue):** `eval()`, `new Function()`, `setTimeout(string)`, and `setInterval(string)` are permitted. This is almost certainly required by a charting or UI library used in the application. It is an undocumented security weakening — the library requiring it has not been explicitly identified or commented in the source. A candidate who flags this without being prompted is demonstrating genuine security depth.

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

**Current configuration (as of May 2026):** Redis is now the default cache and queue driver. The documentation and `config/cache.php` / `config/queue.php` reflect this.

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

This is a nuanced question. The API routes are mounted at `/api/` on the same domain-based tenant system. When an API client sends a request to `acme.127.0.0.1.nip.io/api/products`, the same `HandleTenantFromPath` middleware resolves the tenant from the subdomain.

However, there is a `/api/tenant-lookup` endpoint (served on the main domain or from the API auth controller) that allows clients to look up a tenant's domain by a lookup key (e.g., business name or email) — this is used by mobile apps to discover the correct tenant subdomain before authenticating.

**Authentication flow for API:**
1. Client POSTs to `{tenant_domain}/api/auth/login` with `email` + `password`
2. Server validates credentials and returns a Sanctum `personal_access_token`
3. Client includes `Authorization: Bearer {token}` header on all subsequent requests
4. `auth:sanctum` middleware validates the token on each request

**Rate limiting:** `ThrottleRequests` middleware applies `60` requests per minute per authenticated user (or per IP for unauthenticated routes).

**API documentation:** Scribe generates OpenAPI YAML at `/public/docs/openapi.yaml` and an interactive HTML docs page at `/public/docs/`. Developers use the `php artisan scribe:generate` command to regenerate docs when APIs change.

---

### Q13.2 — What does the `HandlesApiErrors` trait do and why is it used on API controllers?

**Answer:**

`HandlesApiErrors` is a reusable trait applied to API controllers that standardizes error response formatting.

**Why:** Laravel's default exception handler returns HTML error pages for validation errors, model not found exceptions, etc. API clients expect **JSON error responses** in a consistent format.

**What it does:**
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

**Why a trait instead of global exception handler?** The global exception handler in `bootstrap/app.php` could do this, but using a trait allows per-controller customization and makes the error handling behavior explicit and visible to developers reading the controller code. It also allows some controllers to handle errors differently from others.

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

**Concrete example:** When a sale is created, `SalesService::createSale()`:
1. Validates stock availability across warehouses
2. Applies FEFO batch deduction
3. Updates `product_warehouse.quantity`
4. Calculates totals (with discounts and taxes)
5. Creates `sales` and `sales_items` records
6. Creates payment records if `amount_received > 0`
7. Creates accounting journal entries via `AccountingService`
8. Dispatches notification if customer has outstanding balance
9. Logs the activity via `ActivityObserver`

This 9-step process would be in the controller if no service layer existed — untestable, unreusable, unmaintainable.

---

### Q14.2 — What is `TransactionDTO` and why use a DTO here instead of a plain array?

**Answer:**

`TransactionDTO` (in `app/DTOs/`) is a typed data container for passing transaction line data between the service layer and the accounting system.

**Without DTO:**
```php
// Plain array — no type safety, easy to make key typos, no IDE autocomplete
AccountingService::postEntry([
    ['account_id' => 5, 'type' => 'debit', 'ammount' => 1000],  // typo: 'ammount'
]);
```

**With DTO:**
```php
// Type-safe, IDE-autocomplete, constructor validation
$transaction = new TransactionDTO(
    accountId: 5,
    type: TransactionType::DEBIT,  // enum, not string — can't pass wrong value
    amount: 1000.00,               // float type enforced
);
AccountingService::postEntry([$transaction]);
```

**Benefits:**
1. **Type safety** — PHP 8.2 property types catch errors at construction
2. **IDE autocomplete** — developers see available properties without reading documentation
3. **Refactoring** — renaming a property is caught by IDE; renaming an array key is not
4. **Validation** — constructor can enforce business rules (e.g., amount must be positive)
5. **Self-documenting** — the DTO class is the specification of what data is needed

**In accounting specifically:** The debit/credit balance check is critical. Using a typed `TransactionType` enum (instead of `'debit'` strings) prevents the silent bug where someone passes `'Debit'` or `'DEBIT'` and the equality check fails.

---

## 15. Observer Pattern & Audit Logging

---

### Q15.1 — How does `ActivityObserver` work? What is its performance impact on write operations?

**Answer:**

`ActivityObserver` is registered for **16 models** in `AppServiceProvider::registerActivityObservers()`. For each model, it listens to:
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
Every write operation on any of the 16 observed models incurs an **additional INSERT** into `activity_logs`. For a sale with 10 line items:
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

**The observer ensures consistency:** Whenever `used_amount` changes (because a credit was applied to a sale), the observer recalculates and updates `available_amount`:
```php
public function updated(CustomerCredit $credit): void
{
    if ($credit->wasChanged('used_amount')) {
        $credit->updateQuietly([
            'available_amount' => $credit->amount - $credit->used_amount,
        ]);
    }
}
```

Note: `updateQuietly()` prevents re-triggering the observer in a loop.

---

## 16. Customer Credit System

---

### Q16.1 — Explain the full lifecycle of a customer credit, from creation to consumption.

**Answer:**

**Creation triggers:**
1. **Sales return** — when a customer returns goods, a `customer_credit` is created for the refund amount instead of (or in addition to) a cash refund
2. **Manual credit** — staff can manually issue a credit (e.g., as compensation for a service failure)

**`customer_credits` columns:**
- `customer_id` — the credited customer
- `amount` — original credit value
- `used_amount` — amount applied to sales (starts at 0)
- `available_amount` — `amount - used_amount` (maintained by observer)
- `reason` — free text explanation
- `status` — `active`, `used`, `expired`
- `expires_at` — optional expiry date

**Consumption (FIFO):** When a new sale is being created and the customer has credit:

```php
// CustomerCreditService uses FIFO — oldest credits first
$credits = CustomerCredit::where('customer_id', $customerId)
    ->where('status', 'active')
    ->where('available_amount', '>', 0)
    ->where(function($q) {
        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
    })
    ->orderBy('created_at', 'asc')  // FIFO: oldest first
    ->get();

$creditApplied = 0;
foreach ($credits as $credit) {
    $apply = min($credit->available_amount, $remainingBalance);
    $credit->increment('used_amount', $apply);
    $creditApplied += $apply;
    $remainingBalance -= $apply;
    if ($remainingBalance <= 0) break;
}

// Store on the sale record
$sale->customer_credit_used = $creditApplied;
```

**Status transitions:**
- `active` → `used` (when `available_amount = 0`)
- `active` → `expired` (when `expires_at` passes — handled by scheduled command or check at consumption time)

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

**Aging buckets:**
| Bucket | Definition |
|---|---|
| Current | `due_date >= today` (not yet due) |
| 1-30 days | `today - due_date` between 1 and 30 days |
| 31-60 days | `today - due_date` between 31 and 60 days |
| 61-90 days | `today - due_date` between 61 and 90 days |
| 90+ days | `today - due_date > 90 days` |

**Query approach:**
```php
$today = now()->toDateString();

$agingData = DB::table('sales')
    ->where('tenant_id', $tenantId)
    ->whereIn('status', ['Unpaid', 'Partial'])  // only outstanding
    ->select([
        DB::raw("SUM(CASE WHEN due_date >= '$today' THEN (total - COALESCE(paid, 0)) ELSE 0 END) as current"),
        DB::raw("SUM(CASE WHEN DATEDIFF('$today', due_date) BETWEEN 1 AND 30 THEN (total - COALESCE(paid, 0)) ELSE 0 END) as days_1_30"),
        // ... similar for other buckets
    ])
    ->first();
```

**Paid amount subquery:** The outstanding amount per invoice is `total - SUM(payments.amount) - customer_credit_used`. A correlated subquery or join to `payments` table is used to get this accurately.

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

**Custom appended middleware:**
13. `NeedsTenant` — verifies tenant context exists (404 if not)
14. `HandleTenantFromPath` — resolves and sets current tenant
15. `SetLocale` — sets application locale from user/session preferences
16. `DebugModeMiddleware` — enables/disables Laravel Telescope or debug bar
17. `SecurityHeaders` — sets all security HTTP headers on the response
18. `SetSessionCookieName` — sets session cookie name per-tenant
19. `TurboMiddleware` — handles Hotwire Turbo request headers

**Route-specific middleware:**
20. `auth` (Fortify) — redirects to login if not authenticated
21. `verified` — redirects to email verification if not verified
22. `subscription.active` — redirects to plan upgrade if subscription expired
23. `plan.feature:accounting` — redirects to upgrade if plan lacks `accounting` feature
24. `permission:view-journal-entries` — 403 if user lacks permission

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

**Input files compiled:**
- `resources/css/app.css` — Tailwind CSS entry point (purges unused classes based on Blade template scan)
- `resources/js/app.js` — main JavaScript entry (imports Alpine.js, Axios, Turbo)

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

**`.github/workflows/ci.yml`** runs on every push/PR:

1. **PHP lint** — checks for syntax errors
2. **PHPStan/Pint** — static analysis and code style enforcement
3. **Composer install** — installs PHP dependencies
4. **npm install + build** — installs Node dependencies and builds assets
5. **Run migrations** — against an in-memory SQLite database
6. **Run PHP tests** — `php artisan test` (PHPUnit/Pest)
7. **Run JS tests** — `npx vitest run`

**`.github/workflows/deploy.yml`** runs on push to `main`:
1. Runs CI checks first
2. Deploys to Railway via Railway CLI or webhook

**Railway branches:**
- `railway/code-change-_P3x3T` and `railway/code-change-DFBXMb` are Railway's automatically created branches for deployment environments (preview vs. production)

**Docker support:**
- `Dockerfile` builds the PHP-FPM + Nginx image
- `docker/nginx.conf` configures the web server
- `docker-compose.yml` orchestrates the app container + MySQL + (optionally) Redis

---

### Q20.2 — What does the `BackupTenant` Artisan command do and how does it work?

**Answer:**

`BackupTenant` uses `spatie/laravel-backup` to create an encrypted backup of tenant data.

**Process:**
1. Resolves the specific tenant to back up
2. Scopes the backup to only that tenant's data (using `tenant_id` filtering)
3. Creates a mysqldump of the relevant tables filtered by `tenant_id`
4. Compresses the dump into a ZIP archive
5. Encrypts the archive using the tenant's `backup_password` (stored on the `tenants` table)
6. Stores the backup in configured storage (local disk or S3-compatible)
7. Sends notification to the tenant admin on completion

**Tenant columns used:**
- `backup_password` — the encryption key for this tenant's backup, stored with Laravel's `encrypted` cast (encrypted via APP_KEY using AES-256-CBC before writing to the database)
- `backup_encrypted` — boolean flag indicating whether the backup is encrypted

**Security note:** `backup_password` is stored encrypted in the database via Laravel's `encrypted` cast (migration `2026_05_19_000002` widened the column to `TEXT` and re-encrypted all existing values). This means an attacker who obtains a database dump cannot directly read backup passwords without also having `APP_KEY`. However, if `APP_KEY` is also compromised (e.g., via a leaked `.env` file), both the password and the data it protects are exposed. The fundamental trade-off — convenience vs. requiring tenants to supply their own password at backup/restore time — is acknowledged. For most SMB tenants, the current approach is the right balance.

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
SalesOpportunity → Quotation → Sales (via quotation.sales_id on conversion)
```
Used when a formal PDF quote is issued. The `SalesOpportunity::quotations()` hasMany relationship links opportunities to multiple quotations (e.g., a revised quote after negotiation). When a quotation status transitions to `Accepted` and is "Converted", a `Sales` record is created.

**Why both paths are needed:** Some deals are won verbally and then billed directly (no document trail needed). Others require a formal written quote for procurement approval on the customer's side. Forcing all deals through the quotation path would add unnecessary friction to fast-moving sales. Both paths eventually produce a `Sales` record, which is the canonical financial document.

**Data integrity:** The `SalesOpportunityItem` model uses `withDefault(['name' => 'Unknown Product', 'selling_price' => 0])` on its `product()` relationship. This is a defensive pattern — if a product is deleted after an opportunity was created, the system returns a graceful default instead of a null-pointer failure.

---

### Q21.2 — There was an inconsistency between `Tenant::initializeBillingPeriod()` and `BillingService::getCurrentBillingPeriod()`. Describe it and explain the potential impact. Is it still open?

**Answer:**

**The inconsistency (now resolved in June 2026):**

`BillingService::getCurrentBillingPeriod()` — implements rolling 30-day billing as described in the v1.7 changelog:
```php
'end' => now()->copy()->addDays(30)->toDateString()
```

`Tenant::initializeBillingPeriod()` — originally used calendar month:
```php
'billing_period_start' => now()->startOfMonth()->toDateString(),
'billing_period_end'   => now()->endOfMonth()->toDateString(),
```

**When this mattered:**
`Tenant::initializeBillingPeriod()` is called on plan assignment (upgrade flow) if the billing period has not yet been set. If a tenant upgrades on June 12, `initializeBillingPeriod()` would set `billing_period_end = June 30` (calendar month). But `BillingService` would have computed `billing_period_end = July 12` (rolling 30 days). The `ProcessScheduledPlanChanges` command uses `pending_plan_effective_at` which is set from `BillingService::getNextBillingPeriod()` — so the scheduled downgrade fires on the BillingService-computed date. But the `billing_period_end` stored on the tenant reflects the Tenant model's calendar month — creating a mismatch between when the period ends and when the next period starts.

**The fix (applied June 2026):** `Tenant::initializeBillingPeriod()` now uses `addDays(30)` to match `BillingService`. `getNextBillingPeriod()` was also updated to use rolling 30-day periods. Both methods are now consistent with the v1.7 changelog commitment.

**Why this is a senior-level finding:** The inconsistency was subtle — it didn't cause an obvious crash or error. It manifested as a discrepancy between what the UI shows ("your plan renews on [date]") and when the plan lifecycle commands actually fire.

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

## Green Flags

- Proactively identifies the `DB::table()` raw query risk AND notes that `DB::tenantTable()` macro now mitigates it, but is not a compile-time guarantee
- Identifies `'unsafe-eval'` in the CSP `script-src` as a security concern and can articulate why it's needed (jQuery `$.globalEval`, Chart.js dynamic renderers) — or suggests alternative libraries that are CSP-compliant
- Knows the billing period inconsistency between `Tenant::initializeBillingPeriod()` and `BillingService` was resolved in June 2026 and can describe the pre-fix impact on scheduled downgrades
- Notes that `remaining_quantity` on `po_items` exists separately from `product_warehouse.quantity` for FEFO batch tracking
- Can trace a complete sales transaction from HTTP request → service → FEFO → journal entries → observer
- Identifies that the `SalesOpportunity` model supports two conversion paths (direct to `Sales` or via `Quotation`) and explains why this is the correct CRM design for a system that needs both informal and formal quoting workflows
- Understands the distinction between the `SuperAdmin` model (platform-level, separate auth guard) and the `superuser` Spatie role (tenant-level, all permissions within a tenant) — and can explain why both are architecturally correct

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
- Uses `PlatformCurrencySetting` for monetary formatting (independent from tenant currencies)
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

### Q22.3 — Explain the Platform Currency System. How does it differ from tenant currency?

**Answer:**

There are two completely independent currency systems:

**`PlatformCurrencySetting` (super admin):**
- Model: `App\Models\PlatformCurrencySetting` — no `BelongsToTenant` trait, no `tenant_id`
- Table: `platform_currency_settings` (single global row)
- Used by: `CurrencyHelper::formatPlatform($amount)`
- Affects: Plan prices, subscription order amounts, billing totals — anything displayed in the super admin panel
- Configurable at: Super admin → Settings → Platform Currency

**`CurrencySetting` (tenant):**
- Model: `App\Models\CurrencySetting` — uses `BelongsToTenant` trait
- Table: `currency_settings` (one row per tenant)
- Used by: `CurrencyHelper::format($amount)`
- Affects: Invoice totals, POS receipts, sales amounts — anything displayed in the tenant admin panel
- Configurable at: Tenant admin → Settings → Currency

**Key property:** Changing the platform currency (e.g., from USD to IDR) has **zero impact** on tenant currency settings. The `currency_code` column on `plans` and `subscription_orders` records the currency at time of creation, preserving historical accuracy even if the platform currency changes later.

---

### Q22.4 — What does the super admin panel manage? List all major functional areas.

**Answer:**

The super admin panel (16 controllers, 28 Blade views, 39 routes) manages:

1. **Tenants** — list, view, toggle active/inactive, edit domain, impersonate (planned)
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

All operations are audit-logged via `activity()` (Spatie Activity Log).
