# Invent-MAG: Technical Specification Document

**Document Version:** 2.5
**Last Updated:** Aug 2026
**System Version:** 2.5

---

## Executive Summary

Invent-MAG is a production-ready, enterprise-grade ERP system built on Laravel 11 with 1.5 years of continuous development. This document provides detailed technical specifications for potential buyers, integrators, and technical evaluators.

### Key Technical Highlights

- **Multi-tenant SaaS architecture** with row-level database isolation
- **1186 test methods** (3715 assertions) ensuring code reliability
- **527+ commits** demonstrating sustained development
- **Docker + CI/CD** for modern DevOps practices
- **Service-oriented architecture** with clean separation of concerns
- **Turbo Drive (Hotwire)** for SPA-like navigation without a frontend framework
- **Contextual tutorial system** with 50+ page-specific help modals, sub-topic drill-down navigation, and auto-show on sub-topic clicks

---

## 1. Architecture Overview

### 1.1 System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        PRESENTATION                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Blade      │  │    API       │  │   Assets     │      │
│  │   Views      │  │   (Sanctum)  │  │   (Vite)     │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└────────────────────┬────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────┐
│                      APPLICATION                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ Controllers  │  │   Services   │  │   Helpers    │      │
│  │              │  │              │  │   (Calculus) │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  Middleware  │  │   Policies   │  │   DTOs       │      │
│  │(Tenant,Auth) │  │              │  │              │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└────────────────────┬────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────┐
│                       DATA LAYER                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Models     │  │  Migrations  │  │   Seeders    │      │
│  │ (Eloquent)   │  │              │  │              │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│                                                              │
│  ┌──────────────┐  ┌──────────────┐                        │
│  │ MySQL/SQLite │  │    Redis     │                        │
│  │   Database   │  │  Cache/Queue │                        │
│  └──────────────┘  └──────────────┘                        │
└─────────────────────────────────────────────────────────────┘
```

### 1.2 Multi-tenancy Implementation

**Architecture:** Spatie Laravel Multitenancy  
**Isolation Level:** Row-level isolation (tenant_id column)  
**Tenant Resolution:** Domain-based automatic detection (Subdomain Architecture)

```php
// Tenant isolation via BelongsToTenant trait
class Product extends Model
{
    use BelongsToTenant;
    // Automatically scoped to current tenant
}
```

**Benefits:**

- True data isolation between tenants
- Single codebase serving multiple organizations
- Automatic query scoping
- Tenant-aware queues and jobs
- **Session Isolation:** Using subdomains (`tenant.invent-mag.web.id`) allows users to be logged into multiple different workspaces simultaneously in different browser tabs without session conflicts.

---

### 1.3 Subscription & Payment System

**Payment Gateway:** Midtrans (Indonesian market)  
**Pricing Model:** Three-tier SaaS subscription  
**Currency:** USD displayed, IDR charged at a superadmin-managed exchange rate — stored in the `billing_settings` table (fallback: `config/billing.php` = 16,000), read by `Admin/PlanController` via `BillingSetting::getRate()` and exposed to the marketing site as `billing_rate` on `GET /api/cms`. The Astro `BaseLayout` price formatter uses that rate, with `PUBLIC_USD_TO_IDR` as a fallback override. Rate is managed at Superadmin → Settings → Billing Rate. Plan USD prices are exposed to the marketing site via public `GET /api/plans` (`Api/PlanController`); `invent-mag-fe` `src/lib/plans.ts` fetches it at build time with 19/59/109 as offline defaults.

#### Subscription Tiers

| Tier         | Users     | Warehouses | Products  | Price  | Features                                   |
| ------------ | --------- | ---------- | ---------|--------| ------------------------------------------ |
| Starter      | 3         | 1          | 100      | $19/mo | Inventory, Sales/Purchases, POS, Returns, Stock Transfer |
| Professional | 10        | Unlimited  | 500      | $59/mo | + Stock Opname, Accounting, CRM, HR & Payroll, **e-Faktur PPN** |
| Enterprise   | Unlimited | Unlimited  | Unlimited | $109/mo| + Forecasting, API, Audit Trail, Custom Roles, **e-Faktur PPN** |

*(Updated: July 2026 - e-Faktur PPN added to Professional & Enterprise tiers)*

> **Note:** "Stock Transfer" is available on **all** plans — it is not a gated feature slug. "Custom Roles" is not an implemented feature — tenant roles are fixed (`superuser`, `staff`, `pos`) with 101 granular permissions.

#### Payment Flow

1. User selects plan on upgrade page
2. Invoice generated with USD price + IDR conversion
3. Midtrans Snap popup for payment
4. Frontend confirms via API → Plan updated immediately
5. Webhook serves as backup verification
6. Invoice email sent to user

#### Security Features

- **Signature Verification:** SHA-512 for webhook validation
- **Tenant Ownership:** Orders verified to belong to current tenant
- **Double-Processing Prevention:** Status check before processing
- **CSP Updated:** Midtrans domains whitelisted

#### Key Files

```
app/Http/Controllers/Api/PaymentController.php    # Frontend payment confirmation
app/Http/Controllers/Api/Webhook/MidtransController.php  # Webhook handler
app/Http/Middleware/CheckPlanFeature.php          # Feature gating
app/Http/Middleware/CheckPlanLimit.php            # Usage limits
app/Services/PlanService.php                      # Plan logic
app/Models/Plan.php                               # Plan model
app/Mail/SubscriptionInvoiceMail.php             # Invoice email
config/plans.php                                  # Plan configuration
```

### 1.4 Deployment & Infrastructure

**Strategy:** Containerized "Fat Container" Architecture  
**Platform:** Compatible with Railway, DigitalOcean App Platform, AWS ECS, or any VPS.

#### Deployment Architecture

```
Internet → [DNS: invent-mag.web.id]
               │
               ├─→ [Vercel] → Frontend Landing Page & Signup
               │
               └─→ [Railway] → *.invent-mag.web.id (Subdomain Tenants)
                         ↓
                    [Railway Load Balancer]
                         ↓
                    [Nginx (Container Port 80)]
                         ↓ (FastCGI)
                    [PHP-FPM (Port 9000)]
                         ↓
                    [Laravel Core]
```

#### Key Infrastructure Components

- **Web Server:** Nginx (built into the Docker container)
- **Application Server:** PHP-FPM 8.2
- **Database:** MySQL 8.0+
- **Frontend Hosting:** Vercel (Marketing site & Auth landing)
- **Domain DNS:** Domainesia with Wildcard (`*.invent-mag.web.id`) for automatic SSL on tenant subdomains.
- **Email Service:** Resend PHP SDK (API-based, bypassing SMTP port restrictions)
- **Storage:** local disk or AWS S3 (configured via filesystems.php)
- **PHP Extensions:** GD (with FreeType/WebP/JPEG support), BCMath, PCNTL, PDO MySQL, Zip, Exif, Mbstring

#### Deployment Workflow

1. **Build Stage:** Vite compiles assets (JS/CSS) in a Node.js environment.
2. **Production Stage:** PHP environment set up, Nginx installed, assets copied.
3. **Execution:** `start.sh` runs migrations, caches config, starts both PHP-FPM and Nginx.

**Current production:** Railway (PaaS). **Release automation:** `.github/workflows/deploy.yml` — on a `v*` tag or `workflow_dispatch`, GitHub Actions builds a Docker image to GHCR and SSHes into a VPS (`appleboy/ssh-action`) to `docker-compose pull` + `up -d` with `migrate --force`. Tests run on every push via `.github/workflows/ci.yml`.

### 1.5 Super Admin Panel (SaaS Management)

**Purpose:** A fully separate admin panel for platform operators to manage tenants, plans, subscriptions, billing, and system configuration.

#### Authentication & Isolation

| Component | Implementation |
|-----------|----------------|
| **Model** | `App\Models\SuperAdmin` — extends `Authenticatable`, table `super_admins` |
| **Guard** | `super_admin` (session-based, defined in `config/auth.php`) |
| **Provider** | `super_admins` Eloquent provider |
| **Domain** | Root domain (`invent-mag.web.id`) — not on tenant subdomains |
| **Routes** | `routes/superadmin.php` — 55 route definitions (guest login group + `auth:super_admin` group) |
| **Currency** | `PlatformCurrencySetting` — global display preference for super-admin screens only; tenant display uses the single per-tenant `CurrencySetting` system |

**Default credentials:** `superadmin@invent-mag.com` / `password` (seeded via `SuperAdminSeeder`)

#### Route Groups

```php
// routes/superadmin.php
Route::middleware('guest:super_admin')->group(function () {
    // Login only
});

Route::middleware('auth:super_admin')->group(function () {
    // Dashboard, Tenants, Plans, Subscriptions, Features,
    // Billing, PaymentOverview, Transactions, PendingPayments,
    // Invoices, Backups, AuditLog, Settings, Permissions, CMS
});
```

#### Controllers (16 files in `app/Http/Controllers/SuperAdmin/`)

| Controller | Responsibility |
|------------|----------------|
| `DashboardController` | Platform KPIs: tenant counts, plan distribution, growth chart |
| `Auth/LoginController` | Login/logout with rate limiting (5 attempts per email+IP) |
| `TenantController` | List, show, toggle active, update domain, impersonate (planned) |
| `PlanController` | Full CRUD for plans (slug, name, price, features, limits, trial days) |
| `SubscriptionController` | Terminate/reactivate subscriptions, view stats |
| `FeatureController` | Assign feature slugs per plan tier (view matrix + bulk update) |
| `BillingController` | Subscription order listing, detail, void |
| `PaymentOverviewController` | Revenue statistics and gateway breakdown |
| `TransactionController` | Searchable/filterable transaction history |
| `PendingPaymentController` | Approve or reject incoming subscription payments |
| `InvoiceController` | Paid invoice listing and detail |
| `BackupController` | System-wide and per-tenant backup, restore, download, delete |
| `PermissionController` | View-only listing of all tenant roles and permissions |
| `AuditLogController` | Platform-level activity log with module/action filtering |
| `SettingController` | .env editor, user preferences, platform currency config |
| `CmsController` | Landing page content editor (hero, features, pricing, contact) |

#### Platform Currency Display Preference

`PlatformCurrencySetting` is a standalone model (no `BelongsToTenant`) stored in `platform_currency_settings` (one global row, seeded USD). It is a **display preference for the super admin's own overview screens** (e.g. payment-overview) — not a second currency system. The currency system that matters is the single per-tenant `CurrencySetting` (one row per tenant, Settings → Currency), which drives `CurrencyHelper::format()` everywhere in the tenant admin panel.

```php
// Super admin panel
CurrencyHelper::formatPlatform($amount);     // USD, uses PlatformCurrencySetting
CurrencyHelper::formatPlatformIdr($amount);  // IDR (Rp, id-ID separators) — used for billing/subscription amounts

// Tenant admin panel
CurrencyHelper::format($amount);           // Uses CurrencySetting (per-tenant)
```

Changing the super-admin display preference has zero impact on tenant currencies — tenant transactions are always formatted with the tenant's own `CurrencySetting`. The `currency_code` column on `plans` and `subscription_orders` records the currency at time of creation for historical accuracy.

#### Views (30 Blade files in `resources/views/superadmin/`)

Layout: `base.blade.php` → `menu-sidebar.blade.php` + `navbar.blade.php` + content area

Sections: auth, dashboard, tenants, plans, subscriptions, billing, payment-overview, transactions, pending-payments, invoices, backups, audit-log, permissions, settings (incl. billing-rate), features, cms

Tutorial: `config/superadmin-tutorial.php` — 304 lines of route-specific tutorial content displayed via the "How It Works" modal in the super admin base layout.

#### Migrations

| Migration | Purpose |
|-----------|---------|
| `2026_06_04_000001_create_super_admins_table` | Core super_admins table (id, name, email, password, timestamps) |
| `2026_06_09_000002_add_system_settings_to_super_admins_table` | JSON `system_settings` column for user preferences |
| `2026_07_06_000001_create_platform_currency_settings_table` | Platform currency config (symbol, separators, position, code) |
| `2026_07_06_000002_add_currency_code_to_plans_table` | `currency_code` on plans for historical currency tracking |
| `2026_07_06_000003_add_currency_code_to_subscription_orders_table` | `currency_code` on subscription orders |
| `2026_08_11_000001_create_billing_settings_table` | Singleton `billing_settings` row: superadmin-managed USD→IDR billing rate (seeded default 16,000) |
| `2026_08_11_000002_update_subscription_orders_status_enum` | Add `voided` to `subscription_orders.status` enum (void order / terminate subscription flows) |

---

## 2. Code Quality & Testing

### 2.1 Test Coverage Statistics

| Category          | Count | Coverage                        |
| ----------------- | ----- | ------------------------------- |
| **Feature Tests** | 48+   | Controllers, Routes, End-to-end |
| **Unit Tests**    | 61+   | Services, Helpers, DTOs         |
| **Total Tests**   | 1186  | test methods (3715 assertions)  |

### 2.2 Test Structure

```
tests/
├── Feature/
│   ├── Admin/
│   │   ├── AccountingControllerTest.php
│   │   ├── ProductControllerTest.php
│   │   ├── SalesControllerTest.php
│   │   ├── PurchaseControllerTest.php
│   │   ├── POSControllerTest.php
│   │   ├── DashboardControllerTest.php
│   │   ├── AuditLogControllerTest.php
│   │   ├── StockOpnameControllerTest.php
│   │   ├── PlanControllerTest.php
│   │   └── ... (32+ more)
│   ├── Auth/
│   │   ├── AuthenticationTest.php
│   │   ├── PasswordResetTest.php
│   │   ├── EmailVerificationTest.php
│   │   └── PasswordUpdateTest.php
│   ├── Api/
│   │   ├── ContactControllerTest.php
│   │   └── TenantLookupControllerTest.php
│   ├── MultiTenancyTest.php
│   └── MidtransWebhookTest.php
└── Unit/
    ├── Services/
    │   ├── AccountingServiceTest.php
    │   ├── SalesServiceTest.php
    │   ├── DashboardServiceTest.php
    │   ├── ActivityLogServiceTest.php
    │   ├── SecurityLoggerTest.php
    │   ├── StockOpnameServiceTest.php
    │   ├── BillingServiceTest.php
    │   ├── SystemNotificationServiceTest.php
    │   └── ... (45+ more)
    ├── Helpers/
    │   ├── CurrencyHelperTest.php
    │   ├── SalesHelperTest.php
    │   └── ProductHelperTest.php
    ├── Http/
    │   └── Requests/
    └── Models/
        └── ProductStockTest.php
```

### 2.3 Code Architecture Patterns

#### Service Layer Pattern

```php
// Business logic separated from controllers
class SalesService
{
    public function createSale(array $data): Sales
    {
        DB::transaction(function () use ($data) {
            $sale = Sales::create($data);
            $this->createAccountingEntries($sale); // Dynamic FIFO COGS
            $this->updateInventory($sale);
            return $sale;
        });
    }

    public function deleteSale(Sales $sale): void
    {
        DB::transaction(function () use ($sale) {
            $this->accountingService->reverseJournalEntry($sale->journalEntry); // Strict Reversals
            $this->revertInventory($sale);
            $sale->delete();
        });
    }
}
```

**Benefits:**

- Testable business logic
- Reusable across controllers
- Clear separation of concerns
- Easy to maintain and extend

#### Repository Pattern (Implicit)

> **Note:** This is more accurately described as **Service Layer over Eloquent Active Record**. Eloquent models are not repositories in the strict sense — they do not provide a data-access abstraction that can be swapped for a different backing store. Business logic is correctly isolated in the service layer; the data layer is Eloquent Active Record with custom scopes and model methods.

Eloquent models act as Active Record objects with custom scopes and query methods.

#### Helper Layer Pattern

```php
// Complex calculations centralized for consistency across POS, Sales, CRM, and Reports
class SalesHelper
{
    public static function calculateInvoiceSummaryWithReturns($items, $discount, $type, $tax, $returns)
    {
        // Subtract returned items from original totals, calculate net discount and tax
        $netSubtotal = self::calculateNetSubtotal($items, $returns);
        $netDiscount = self::calculateNetDiscount($netSubtotal, $discount, $type);
        $netTax = self::calculateNetTax($netSubtotal - $netDiscount, $tax);

        return $netSubtotal - $netDiscount + $netTax;
    }
}
```

#### Model Helpers

```php
// Tenant model — jurisdiction-based feature visibility
$tenant->isIndonesian();  // Returns true if tax_jurisdiction === 'indonesia'

// Tax model — PPN (VAT) and PPh (Withholding Tax) identification
$tax->isPpn();  // Returns true if is_ppn === true
$tax->isPph();  // Returns true if is_pph === true
```

**Benefits:**

- Eliminates calculation discrepancies between different modules (e.g. Dashboard vs CRM vs POS)
- Simplifies controllers and services
- Easy to unit test mathematical operations

### 2.4 Route Architecture

In May 2026, the 850+ line `routes/web.php` file was split into domain-specific files for maintainability.

```
routes/
├── web.php                 # Orchestrator (bootstrapping & includes)
└── admin/                  # Domain-specific route files
    ├── accounting.php      # Chart of accounts, journal entries, ledger
    ├── auth.php            # Login, password reset, verification
    ├── bank-reconciliation.php # Bank reconciliation import, matching, reports
    ├── contacts.php        # Customers, suppliers, CRM/SRM details
    ├── crm.php             # Sales pipeline and opportunities
    ├── dashboard.php       # Dashboard and tutorial context
    ├── efaktur.php         # e-Faktur PPN (DJP tax invoice, NSFP, CSV export)
    ├── hr.php              # Employees, attendance, payroll, payslips
    ├── inventory.php       # Warehouses, POS, stock opname
    ├── notifications.php   # System notifications, users, audit log
    ├── products.php        # Product CRUD and stock API
    ├── purchases.php       # POs and purchase returns
    ├── quotations.php      # Quotation lifecycle
    ├── reports.php         # Financial and transaction reports
    ├── sales.php           # Sales orders and sales returns
    └── settings.php        # System, profile, and backup settings
```

---

## 3. Security Implementation

### 3.1 Authentication & Authorization

| Layer              | Implementation    | Details                          |
| ------------------ | ----------------- | -------------------------------- |
| **Authentication** | Laravel Fortify   | Email/password with verification |
| **API Auth**       | Laravel Sanctum   | Token-based API access           |
| **Authorization**  | Spatie Permission | 101 granular permissions          |
| **Passwords**      | Bcrypt            | Industry-standard hashing        |
| **Sessions**       | Encrypted cookies | Secure session management        |

### 3.2 Permission System

```php
// Example permissions defined
'view-products'
'create-products'
'edit-products'
'delete-products'
'import-products'
'view-accounting'
'edit-chart-of-accounts'
'post-manual-journal'
// ... 102 total permissions

// Usage in controller
public function store(Request $request)
{
    $this->authorize('create-products');
    // ...
}
```

### 3.3 Security Features

| Feature                | Implementation           | Status                  |
| ---------------------- | ------------------------ | ----------------------- |
| **CSRF Protection**    | Laravel built-in         | ✅ Active               |
| **XSS Prevention**     | Blade auto-escaping + CSP nonces | ✅ Active       |
| **SQL Injection**      | Query Builder/Eloquent   | ✅ Protected            |
| **Rate Limiting**      | Custom middleware        | ✅ 5 login attempts     |
| **Security Logging**   | SecurityLogger service   | ✅ Logs all auth events |
| **Input Sanitization** | sanitize.js + validation | ✅ XSS protected        |
| **Email Verification** | Fortify built-in         | ✅ Required             |
| **CSP Nonces**         | Per-request nonce via SecurityHeaders middleware | ✅ Implemented May 2026 |

### 3.4 Audit Trail

**Security Audit Logging:**

- Password changes (who, when, IP)
- Failed login attempts (user, IP, time)
- Successful logins (user, IP, time, user-agent)
- Permission denials (user, attempted action)
- Suspicious activity detection

**Accounting Audit Trail:**

- Journal entry creation/modification
- Transaction changes
- Account modifications
- User actions on financial data

**Audit Log UI (Activity Logs):**

- Dedicated audit log page (`/admin/audit-log`)
- Filterable by user, module, action, date range, search
- Summary cards (today, this week, this month, total events)
- Detail modal showing old/new values for changes
- Superuser-only access (bypasses plan limits)
- Multi-tenant isolation with tenant-specific data
- Module-specific actions based on navigation menu:
  - Product, Sales, Sales Return, Purchase Order, Purchase Return
  - Customer, Supplier, Warehouse, Category, Unit, Tax, User
  - Manual Journal Entry (create, update, delete, post, void, reverse, view)
  - POS (create, update, payment, view)

---

## 4. Database Architecture

### 4.1 Entity Relationship Overview

```
Tenant (1)
├── Users (N)
├── Products (N)
│   └── ProductWarehouses (N)
├── Categories (N)
├── Units (N)
├── Customers (N)
│   └── Interactions (N)
├── Suppliers (N)
│   └── Interactions (N)
├── Sales Orders (N)
│   └── SalesItems (N)
│   └── Payments (N)
├── Quotations (N)
│   └── QuotationItems (N)
├── Purchase Orders (N)
│   └── POItems (N)
│   └── Payments (N)
├── Sales Returns (N)
├── Purchase Returns (N)
├── Warehouses (N)
├── Accounts (N) [COA]
├── Journal Entries (N)
│   └── Transactions (N)
├── Stock Adjustments (N)
└── Sales Pipelines (N)
    └── Stages (N)
    └── Opportunities (N)
```

### 4.2 Key Database Tables

| Table                 | Purpose           | Records            |
| --------------------- | ----------------- | ------------------ |
| **users**             | Authentication    | Per tenant         |
| **tenants**           | Multi-tenancy + accounting settings + `tax_jurisdiction` | Master table |
| **products**          | Product catalog   | 1000s per tenant   |
| **product_warehouse** | Stock levels      | 1000s per tenant   |
| **sales**             | Sales orders      | 10000s per tenant  |
| **quotations**        | Quotation management (Draft/Sent/Accepted/Rejected/Expired/Converted) | Per tenant  |
| **po**               | Purchase orders   | 10000s per tenant  |
| **accounts**          | Chart of accounts | 35+ per tenant     |
| **journal_entries**   | Accounting        | 10000s per tenant  |
| **transactions**      | Accounting lines  | 100000s per tenant |
| **taxes**             | Tax rate/label definitions with `is_ppn` and `is_pph` flags | Per tenant |
| **faktur_pajak**      | e-Faktur PPN headers (NSFP, snapshotted seller/buyer data, DPP/PPN/PPnBM, DJP submission status) | Per tenant |
| **faktur_pajak_items**| e-Faktur line items from `sales_items` with BKP/JKP classification | Per tenant |
| **nsfp_pool**         | Available NSFP numbers imported from DJP, allocated FIFO | Per tenant |
| **bank_reconciliations** | Bank reconciliation headers (statement period, opening/closing balance, variance) | Per tenant |
| **bank_reconciliation_items** | Matched/unmatched statement lines with transaction linkage | Per tenant |

### 4.3 Performance Optimizations

| Optimization      | Implementation                        |
| ----------------- | ------------------------------------- |
| **Indexing**      | Tenant_id + foreign keys indexed      |
| **Eager Loading** | Consistent `with()` usage in services |
| **Pagination**    | 20-50 items per page                  |
| **Caching**       | Redis cache (24-hour permission cache) |
| **Transactions**  | DB::transaction for atomic operations |

---

## 5. API Architecture

### 5.1 API Implementation

**Framework:** Laravel Sanctum  
**Authentication:** Bearer tokens (`api/payment/confirm`, `api/v1/refresh-token`, `api/v1/plans`, `api/v1/plan/current`); the remaining endpoints are public (auth, CMS, marketing pricing via `api/plans`, contact, payment webhooks)  
**Documentation:** Scribe (auto-generated)  
**Base URL:** `{app_url}/api` (all routes are registered under the `api` middleware group with the `/api` prefix — see `bootstrap/app.php`)

> **Scope note:** The public REST API is intentionally limited to the **essential endpoints** below (authentication, CMS content, marketing plan pricing, payment confirmations/webhooks, and authenticated plan lookups). There is no product/customer/sales CRUD API — all CRUD operations happen in the session-authenticated web admin (`/admin/*`). A broader REST API is an Enterprise roadmap item (`api_access` feature slug).

### 5.2 API Endpoints

The complete, always-current endpoint list is generated by Scribe (`php artisan scribe:generate`) into `public/docs/`. Current endpoints:

#### Authentication

```http
POST /api/register             # User registration (public)
POST /api/login                # User login (public)
POST /api/lookup-tenant        # Tenant discovery by shop name (public)
POST /api/v1/refresh-token     # Token refresh (Bearer token, auth:sanctum)
```

#### CMS Content

```http
GET /api/cms                   # Public marketing-site CMS content
```

#### Payments

```http
POST /api/payment/confirm      # Confirm payment (Bearer token, auth:sanctum)
POST /api/webhooks/midtrans    # Midtrans payment notification webhook (HMAC-signed)
POST /api/webhook              # Midtrans webhook fallback alias (misconfigured dashboard)
```

#### Plans

```http
GET /api/plans                # Public marketing pricing data (no auth) — used by the Astro pricing cards
GET /api/v1/plans             # List active plans (Bearer token, auth:sanctum)
GET /api/v1/plan/current      # Current plan for a tenant (Bearer token, auth:sanctum)
```

#### Contact

```http
POST /api/contact              # Marketing-site contact form → emails config('app.contact_email') (public, rate-limited)
```

### 5.3 API Documentation

**Generated Documentation (Scribe, `type: static`):**

- **URL:** `{app_url}/docs/` (served from `public/docs/`)
- **Formats:** Interactive HTML (`index.html`), OpenAPI 3.0 (`openapi.yaml`), Postman Collection (`collection.json`)
- **Regenerate after any route change:** `php artisan scribe:generate`
- **Features:**
    - Interactive "Try It Out"
    - Code examples (cURL, JavaScript, PHP)
    - Request/response schemas
    - Authentication examples

---

## 6. DevOps & Deployment

### 6.1 Docker Configuration

**Docker Compose Services:**

```yaml
services:
    app: # PHP 8.2-FPM
    webserver: # Nginx
    database: # MySQL 8.0
    redis: # Redis (cache + queue default)
```

**Usage:**

```bash
docker-compose up -d          # Start all services
docker-compose exec app bash  # Access container
docker-compose logs -f        # View logs
```

### 6.2 CI/CD Pipeline

**GitHub Actions Workflows:**

- **ci.yml:** Automated testing on push
- **deploy.yml:** Deployment automation

**Pipeline Steps:**

1. Install dependencies (Composer, NPM)
2. Run PHPUnit tests
3. Run Vitest tests
4. Build production assets
5. Deploy (configurable)

### 6.3 Backup Strategy

**Spatie Laravel Backup:**

```bash
php artisan backup:run          # Create backup
php artisan backup:list         # List backups
php artisan backup:clean        # Clean old backups
```

**Backups Include:**

- Database dump
- Storage files
- Configuration

**Security:** Backup encryption passwords are encrypted at rest using Laravel's `encrypted` cast and the application's `APP_KEY`. The raw password is never stored in plaintext, preventing compromise via database leak alone.

---

## 7. Frontend Architecture

### 7.1 Technology Stack

| Layer          | Technology   | Purpose                   |
| -------------- | ------------ | ------------------------- |
| **Templating** | Blade        | Server-side rendering     |
| **Navigation** | Turbo Drive  | SPA-like page transitions |
| **CSS**        | Tailwind CSS | Utility-first styling     |
| **JavaScript** | Alpine.js    | Reactive components       |
| **Icons**      | Tabler Icons | Consistent iconography    |
| **Build**      | Vite         | Asset compilation         |

### 7.2 JavaScript Organization

```
resources/js/
├── admin/
│   ├── accounting.js           # Accounting UI
│   ├── bootstrap-init.js       # Unified Bootstrap init (Turbo-compatible)
│   ├── journal-entry.js        # Manual JE validation
│   ├── pos.js                  # POS functionality
│   ├── sales-pipeline.js       # Drag-drop kanban
│   ├── sales-order.js          # Dynamic line items
│   ├── sales-return.js         # Sales return page logic
│   └── layouts/                # Layout components
│       ├── receipt-page.js     # Receipt page loader handling
│       └── settings.js         # User settings & notifications
├── partials/
│   └── pos/
│       └── cart/
│           └── actions.js      # POS cart discount/tax reactivity
├── utils/
│   ├── currencyFormatter.js    # Currency formatting
│   ├── onPageLoad.js           # Turbo-compatible init helper
│   └── sanitize.js            # Input sanitization
└── app.js                      # Entry point
```

### 7.3 Turbo Drive Integration

Invent-MAG uses **Hotwire Turbo Drive** (`@hotwired/turbo-rails`) to deliver SPA-like navigation without the complexity of a JavaScript framework. All page-to-page navigations are handled via fetch, with the DOM updated in place.

**Key implementation details:**

- **`onPageLoad(callback)` utility** — a purpose-built helper (`resources/js/admin/utils/onPageLoad.js`) that fires on both the initial `DOMContentLoaded` and every subsequent `turbo:load` event, without double-firing. All page-specific JS modules use this instead of bare `DOMContentLoaded` listeners.
- **`bootstrap-init.js`** — unified Bootstrap component initializer that re-runs on every `turbo:load` to reattach tooltips, dropdowns, and popovers on freshly rendered DOM nodes.
- **`receipt-page.js`** — dedicated script for the POS receipt page that immediately dismisses the global page loader, which otherwise persists during Turbo navigations.
- **Turbo Frames** — used on the main content area (`<turbo-frame id="main-content">`) to scope navigation updates; settings and user management pages opt out to avoid modal conflicts.
- **Session notifications** — the `session-notifications` partial and `handleSessionNotifications()` JS function are re-triggered on every `turbo:load` to display flashed success/error modals after Turbo navigations.

### 7.4 Key Frontend Features

**Sales Forecasting Chart:**

- Interactive charts
- Historical vs predicted data
- Confidence intervals

**POS Interface:**

- Barcode scanning
- Touch-friendly
- Receipt printing
- Payment calculation
- Real-time discount and tax recalculation on input change
- Turbo-compatible receipt page (dedicated `receipt-page.js` loader handling)

**Sales Pipeline:**

- Drag-and-drop kanban
- Kanban/List view toggle
- Stage progression
- Opportunity cards with notes & terms conditions
- Currency-formatted amount display in list view

**Contextual Tutorial System:**

- Every page title includes a `?` tutorial button (`<a class="trigger-tutorial-modal">`) alongside the page icon
- Clicking opens `#tutorial-modal`, a multi-step Bootstrap modal with three steps:
  1. **Setup Workflow Overview** – shown only on first login (`tutorial_completed = false`)
   2. **Topic Grid** – 7 category tiles (Configuration, Master Data, Operations, HR & Payroll, Accounting, Reports, Administration) that drill into sub-topic lists
  3. **Page-Specific Quick Guide** – contextual steps driven by `Route::currentRouteName()` in `tutorial-modal.blade.php`
- **Sub-topic drill-down navigation** – clicking a topic card (e.g., "Accounting") shows a list of pages in that category (e.g., Chart of Accounts, General Ledger, Trial Balance...). Clicking one navigates to that page and auto-opens its contextual tutorial modal via `sessionStorage` flag and Turbo-aware fetch chain.
- **Click-outside to close** – removed `data-bs-backdrop="static"`, enabling backdrop click to dismiss the modal
- 50+ route contexts covered, including Sales Pipeline stages, Quotation lifecycle, Products, Sales Orders, HR (Employee, Attendance, Payroll) and all Accounting and Reports pages
- **Ordering rule:** More specific route patterns must come BEFORE wildcard catch-alls in `config/tutorial.php` — uses `Str::is()` for matching, not Blade conditional branching
- Page title convention: `<i class="ti ti-{icon} me-2"></i>` → title text → tutorial button (`ms-3`)

---

## 8. Business Intelligence

### 8.1 Sales Forecasting Algorithm

**Method:** Holt-Winters Exponential Smoothing + Linear Regression

**Implementation:**

```php
class SalesForecastService
{
    public function forecast(int $periods): array
    {
        // Holt-Winters for seasonal trends
        $holtWinters = $this->calculateHoltWinters($historicalData);

        // Linear regression for trend
        $linearTrend = $this->calculateLinearRegression($historicalData);

        // Combine forecasts
        return $this->combineForecasts($holtWinters, $linearTrend);
    }
}
```

**Accuracy:** Historical accuracy tracking and confidence intervals

### 8.2 AR/AP Aging

**Automatic Buckets:**

- Current (not yet due)
- 1-30 days
- 31-60 days
- 61-90 days
- 90+ days

**Calculation:** Automated based on invoice dates and payment terms

### 8.3 Customer Lifetime Value

**Formula:** Adjusted Total Purchases (Net of Returns) × Current Product Mix

**Implementation:** Automated calculation per customer. `CrmService` scans all historical transactions and actively deducts returned items and refunded amounts from the LTV and product category graphs, providing the true net contribution of each customer.

---

## 9. Integration Capabilities

### 9.1 Current Integrations

| System      | Integration    | Status                |
| ----------- | -------------- | --------------------- |
| **Payment** | Midtrans       | ✅ Subscriptions      |
| **Email**   | Laravel Mail   | ✅ Notifications      |
| **PDF**     | DomPDF         | ✅ Invoice generation |
| **Queue**   | Redis (default) | ✅ Job processing    |
| **Cache**   | Redis (default) | ✅ Performance       |
| **Storage** | Local/S3       | ✅ File uploads       |

### 9.2 API-Ready Architecture

**For Future Integrations:**

- RESTful API foundation
- Webhook support ready
- Event-driven architecture
- Queue-based processing

**Committed Future Enhancements:**

- **PJAP API (Klikpajak)** — Phase 2: Direct DJP submission (enterprise only)

### 9.3 e-Faktur PPN Compliance (Implemented July 2026)

Indonesia's DJP (Direktorat Jenderal Pajak) requires PKP businesses to issue electronic Faktur Pajak for every taxable sale. Invent-MAG implements Phase 1 compliance:

**Database Schema:**
- `faktur_pajak` — Faktur header: NSFP, seller/buyer data (snapshotted), DPP/PPN/PPnBM, DJP submission tracking, status lifecycle (draft → submitted → approved/rejected)
- `faktur_pajak_items` — Line items from `sales_items`, with BKP/JKP classification
- `nsfp_pool` — Available NSFP numbers imported from DJP, allocated FIFO with `lockForUpdate()` for race condition safety

**Service Layer (`EFakturService`):**
- `generateFakturPajak(Sales)` — Atomic, **compliance-only since Phase 1E (Aug 2026)**: NSFP allocation → FakturPajak + items. PPN Keluaran is no longer journaled here — it is recognized at **sale creation** by `SalesService::createSale()` / `PosService` (5-leg VAT split: `Dr AR gross / Cr Revenue net / Cr PPN Keluaran`, with a 4-leg gross fallback when `ppn_keluaran_account_id` is unconfigured)
- `exportCsvForDjp(masa, tahun)` — Streamed CSV with UTF-8 BOM, DJP FK/OF row format, amounts in cents
- `importNsfpPool(string)` — Idempotent import, format validation (`XXX.XXX-YY.XXXXXXXX`)
- `getSptMasaSummary(masa, tahun)` — SPT Masa PPN monthly totals (PPN Keluaran + PPN Masukan + net payable/credit)

**Plan Gating:**
- Feature slug: `ppn` (Professional & Enterprise)
- Route middleware: `plan.feature:ppn`
- Permission: `view-efaktur`, `create-efaktur`, `manage-efaktur-settings`
- Nav item filtered by `NavigationServiceProvider` plan feature check
- The e-Faktur system is jurisdiction-gated via NavigationServiceProvider. The `tax_jurisdiction` field on Tenant controls feature visibility. Routes remain accessible via direct URL for Professional+ tenants regardless of jurisdiction.

**Future (Phase 2):**
- PJAP API direct submission via Klikpajak (`PjapInterface` contract)
- Real-time approval/rejection status from DJP
- Enterprise-only premium automation

**PPN Masukan (Input VAT on Purchases):**
- `po` snapshot fields: `ppn_enabled` (bool), `ppn_rate` (nullable decimal), `ppn_amount` (decimal, default 0)
- Auto-derived from the selected `Tax.is_ppn` flag, gated by `isIndonesian()`
- Does **not** reduce payable to supplier; instead debited to asset account `1160 - Input VAT` (does not capitalize into inventory cost)
- `ppn_masukan_account_id` wired into `tenants.accounting_settings` (AccountSeeder + registration + backfill migration); selectable on the Accounting Settings page (from `asset` accounts)
- `getSptMasaSummary()` returns `total_ppn_masukan` (sum of `po.ppn_amount` where `ppn_enabled = true` for the period), `net_ppn_payable` (Keluaran − Masukan, floored at 0) and `ppn_credit` (Lebih Bayar when Masukan exceeds Keluaran)

### 9.4 PPh (Withholding Tax) Implementation

PPh (Pajak Penghasilan) is implemented as a second independent dropdown on sales transactions, separate from PPN. It follows the same universal tax system as PPN but operates as a distinct tax category.

**Tax Model Flag:**
- `Tax.is_pph` flags withholding tax records in the universal tax system, mirroring how `Tax.is_ppn` identifies PPN taxes
- PPh and PPN are independent dropdowns — selecting a PPN tax does not auto-select PPh and vice versa

**Snapshot Pattern on Sales:**
- `pph_enabled` — boolean, whether PPh was active on the sale
- `pph_rate` — decimal, the PPh rate at time of sale (e.g. 0.02 for PPh 23)
- `pph_amount` — computed PPh amount in cents
- `pph_tax_id` — foreign key to the `taxes` record used (snapshot for audit trail)

**Journal Entries:**
- PPh reduces the amount the customer pays. When a sale with PPh is posted:
  - **Dr** PPh Receivable (asset) — the withholding amount
  - **Cr** Accounts Receivable — reduces what the customer owes
- The net receivable from the customer is `grand_total - pph_amount`

**Feature Gating:**
- Gated by `isIndonesian()` (jurisdiction check on Tenant) + `hasFeature('ppn')` (plan feature slug)
- PPh is only available to tenants with the PPN feature enabled on Professional or Enterprise plans

**Chart of Accounts (auto-seeded):**
| Account              | Code              | Type     | Purpose                                      |
|----------------------|-------------------|----------|----------------------------------------------|
| PPh Receivable       | `1170-{tenant_id}` | Asset    | Tracks withholding tax receivable from customers |
| PPh Payable          | `2150-{tenant_id}` | Liability | Shared PPh liability to tax authorities (all PPh types — PPh 23, PPh Final 0.5%) |

---

## 10. Performance Characteristics

### 10.1 Benchmarks

| Metric               | Value           | Notes              |
| -------------------- | --------------- | ------------------ |
| **Page Load**        | < 2 seconds     | With caching       |
| **Database Queries** | 5-15 per page   | With eager loading |
| **Concurrent Users** | 100+ per tenant | Tested             |
| **Database Size**    | Scalable        | Proper indexing    |
| **File Uploads**     | Configurable    | Local or S3        |

### 10.2 Scalability

**Horizontal Scaling:**

- Stateless application (session in DB/cache)
- Database replication ready
- Load balancer compatible *(requires Redis for cache and queue — default since May 2026)*
- Queue workers scalable

**Vertical Scaling:**

- PHP 8.2+ optimized
- OPcache enabled
- Database query optimization
- Asset caching

---

## 11. Version History

| Version | Date          | Changes                                                                                                                                                                                                                                                                                                                                                                                            |
| ------- | ------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 2.5     | Aug 2026     | VAT accounting fix (Phase 1E): PPN Keluaran recognized at **sale creation** — `SalesService::createSale()` + `PosService` post a 5-leg VAT-split journal (`Dr AR gross / Cr Revenue net / Cr PPN Keluaran`) for Indonesian PPN sales, with a 4-leg gross-revenue fallback when `ppn_keluaran_account_id` is unconfigured. `EFakturService::generateFakturPajak()` is now compliance-only (PPN journal post + `postPpnKeluaranJournalEntry()` removed; `cancelFakturPajak()` still reverses legacy FP-sourced PPN journals). `SalesReturnService` PPN reversals no longer require a Faktur Pajak — they trigger whenever the sale `isPpn()`. Seed set: `PPH 22` (1.5%) replaced by **`PPh Final 0.5%`** (PP 23/2018) in `TaxService::ensureIndonesianDefaults()` + `TaxSeeder`; migration `2026_08_05_000002_backfill_pph_final_tax_record.php` converts existing rows in place (preserving `pph_tax_id` refs + historical snapshots). Tests: +3 new tax tests. |
| 2.4     | Aug 2026     | PPN Masukan (Input VAT on Purchases): `po` snapshot fields `ppn_enabled`/`ppn_rate`/`ppn_amount`; PurchaseService journal now splits `Dr Inventory (net)` + `Dr Input VAT (1160)` instead of capitalizing PPN into inventory (falls back to gross when the Input VAT account is unconfigured); `ppn_masukan_account_id` wired into `tenants.accounting_settings` (AccountSeeder + CreateNewUser + backfill migration) with an "Input VAT (PPN Masukan) Account" select on the Accounting Settings page; `EFakturService::getSptMasaSummary()` returns `total_ppn_masukan`, `net_ppn_payable` and `ppn_credit` from period PPN purchases; SPT Masa PPN summary cards show PPN Masukan + Kurang/Lebih Bayar. Tutorial steps `ppn_masukan_po` + `ppn_masukan_po_view` (EN/ID). Tests: `PurchaseServicePpnMasukanTest` (6) + `EFakturServiceSptMasukanTest` (3). |
| 2.3     | Aug 2026     | Tax Jurisdiction auto-seed & tutorial clarity: `TaxService::ensureIndonesianDefaults()` auto-creates PPN 11% + PPh 23 2% (idempotent, preserves existing default) whenever a tenant switches to Indonesia (Settings page + e-Faktur settings). 2 new tutorial steps (`tax_jurisdiction_vs_config` on System Settings, `tax_config_vs_jurisdiction` on Tax page) explaining jurisdiction vs tax-configuration; refreshed stale tax steps (`define_tax_rates`, `sales_application`, `tax_configuration_desc`). Bank Recon Import page tutorial context (`admin.bank-reconciliation.create`) + reworded "Statement Start vs System Opening Balance" step. Bug fixes: `bootstrap/app.php` ValidationException renderer preserves Fortify named error bag; `Tenant::canDisableIndonesianJurisdiction()` rewritten to `whereDoesntHave('fakturPajak')` (was querying nonexistent `sales.faktur_pajak_id`); Settings page JS hardened (419/401 reload + real error message); `pkp_date` now date-cast on `Tenant` and `Customer` models (fixes "Call to a member function format() on string" 500 on the e-Faktur Settings page and the identical latent bug in the Customers list when a PKP date is set). Tests: 1151 passing (3480 assertions). |
| 2.2     | Aug 2026     | Bank Reconciliation module: `BankReconciliationService` + `BankStatementParserFactory` with BCA/Mandiri/BNI/BRI CSV/TXT parsers (auto format detection), auto + manual matching, opening-balance prefill from account Opening Balance, variance gate (completion blocked when variance ≠ 0), entry locking from reverse/void/unpost, and reconciled status badges in Journal/Ledger via `JournalEntry::withReconciledStatus()` (no N+1). Plan feature `bank_reconciliation` gated to Professional+. Migration: `bank_reconciliations` & `bank_reconciliation_items` tables. Tutorial: Opening Balance vs Bank Reconciliation steps (EN/ID). Tests: 23 bank-recon/parser + 46 accounting/journal passing. |
| 2.1     | Jul 2026     | Tax UX improvements: per-item discount type selector (% vs Fixed) on quotations, tax auto-sync on sales create/edit page load, tax breakdown row on sales-returns show page, pipeline opportunity amount now tax-inclusive grand total. Migration: `notes` & `terms_conditions` columns on `sales` and `sales_opportunities`. Tutorial: 4 new step keys (`tax_auto_sync`, `tax_breakdown_ret`, `opportunity_amount_tax`, `item_discount_type_qt`) with EN/ID translations. Test suite: fixed 17 failing unit tests (528 passed, 0 failed). |
| 2.0     | Jul 2026     | Platform Currency Display Preference: added `PlatformCurrencySetting` model for super admin overview screens, `CurrencyHelper::formatPlatform()` method, `currency_code` columns on `plans` and `subscription_orders`. Display preference only — the single per-tenant `CurrencySetting` system remains the one currency system. |
| 1.9     | June 2026    | Sales Pipeline enhancements: Kanban/List view toggle, currency-formatted list view amounts, notes & terms conditions on opportunities, `onPageLoad` deduplication fix preventing double card rendering. Tutorial expansion: Added pipeline and quotation lifecycle tutorial contexts (50+ total contexts), orphaned lang key cleanup, missing Indonesian translations. |
| 1.8     | June 2026    | System Notifications: Added product expiry alerts (30/7-day windows), subscription plan expiry warnings (14-day), and backup status notifications. POS, Sales Returns & Purchase Returns now available on Starter plan. Architecture fixes: Split `web.php` into domain route files. Migrated `accounting_settings` to `tenants` table. Set Redis as default cache/queue. Implemented CSP nonces. Encrypted backup passwords. |
| 1.7     | May 2026     | Tutorial HR Expansion: Added HR & Payroll as step 4 in welcome setup workflow (now 6 steps). Added Employees, Attendance, Payroll to Master Data sub-topics. Master Data topic card description updated. |
| 1.6     | April 2026    | Tutorial System Enhancements: Topic grid sub-topic drill-down (click category → see all pages in that category → click to navigate). Click-outside closes modal (removed static backdrop). Sub-topic navigation auto-shows contextual tutorial on destination page via sessionStorage flag + Turbo-aware async fetch chain. Fixed sidebar stale nav-click closing bug. Added dashboard tutorial button and context. |
| 1.5     | April 2026    | UI/UX Polish & Tutorial System: Contextual tutorial button (?) added to every page title. 30+ page-specific help modals in `tutorial-modal.blade.php`. Consistent `ti-*` icon system across all page headers. "Partially Paid" status label standardized across Sales & Purchase Orders. Sales Return credit note condition documented. Purchase Order autofill info label. Journal entry audit log sidebar badge fix. |
| 1.4     | March 2026    | Financial & CRM Integrations: Full integration of Sales and Purchase Returns into GL, Dashboards, and CRM (Adjusted LTV). Extracted `SalesHelper`, `PurchaseHelper`, and `ProductStockController` for DRY calculations. Added Opening Balance Module. Added `ACCOUNTING_SETUP_GUIDE.md`. |
| 1.3     | March 2026    | Turbo Drive (Hotwire) integration: `onPageLoad` utility, unified `bootstrap-init.js`, `receipt-page.js`, POS discount/tax reactivity fix, `DashboardService` extraction, auth error handling fix (ValidationException bypass + session-notifications auth page detection).               |
| 1.2     | March 2026    | Full Production Architecture: Subdomain isolation (`*.invent-mag.web.id`), Resend API Email Integration, Advanced Rate Limiting, Midtrans Tenant-Aware Webhooks, CI/CD fixes.                                                                                                            |
| 1.2     | March 2026    | Implemented subscription & payment system with Midtrans, tiered plans (Starter/Professional/Enterprise), feature gating, usage limits, plan badges in sidebar, invoice emails                                                                                                            |
| 1.2     | February 2026 | Dark theme improvements, notification system enhancements                                                                                                                                                                                                                                |
| 1.2     | January 2026  | Initial release with full ERP features                                                                                                                                                                                                                                                   |

---

## 12. Development Team Requirements

### 12.1 To Maintain

| Role                   | Time Required  | Skills              |
| ---------------------- | -------------- | ------------------- |
| **Laravel Developer**  | 20-40 hrs/week | PHP 8.2, Laravel 11 |
| **Frontend Developer** | 10-20 hrs/week | Tailwind, Alpine.js |
| **DevOps**             | 5-10 hrs/week  | Docker, CI/CD       |

### 12.2 To Extend

Common extension points:

- **New Modules:** Add service + controller + views
- **API Endpoints:** Add to existing controllers
- **Reports:** Extend ReportController
- **Integrations:** Add to Services layer

---

## 13. Deployment Checklist

### Pre-deployment

- [✅] Environment variables configured
- [✅] Database credentials secured
- [✅] APP_KEY generated
- [✅] SSL certificate installed
- [✅] Email SMTP configured

### Deployment

- [✅] Run migrations
- [✅] Seed initial data
- [✅] Build production assets
- [✅] Configure queues
- [✅] Set up backups
- [✅] Configure monitoring

### Post-deployment

- [✅] Test all critical paths
- [✅] Verify email delivery
- [✅] Check error logging
- [✅] Monitor performance
- [✅] Set up alerts

---

## 14. Contact & Support

**Developer:** moogie3  
**Repository:** https://github.com/moogie3/invent-mag  
**Documentation:** Included in repository

**For Technical Questions:**

- GitHub Issues: Bug reports, feature requests
- Email: [Contact via GitHub profile]

---

## Document History

| Version | Date     | Changes                                                                                                                                                                       |
| ------- | -------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 2.5     | Aug 2026 | VAT accounting fix (Phase 1E): PPN Keluaran recognized at sale creation (`SalesService::createSale()` + `PosService` post 5-leg VAT-split journal), e-Faktur issuance compliance-only, `SalesReturnService` PPN reversals trigger on `isPpn()` without a Faktur Pajak, seeded `PPH 22` replaced by `PPh Final 0.5%` (PP 23/2018) with in-place backfill migration. |
| 2.4     | Aug 2026 | PPN Masukan (Input VAT on Purchases): `po` snapshot fields, PurchaseService journal splits `Dr Inventory (net)` + `Dr Input VAT (1160)`, `ppn_masukan_account_id` on Accounting Settings, SPT Masa PPN summary includes Masukan/Kurang/Lebih Bayar. |
| 1.0     | Feb 2026 | Initial technical specification                                                                                                                                               |
| 1.0     | Feb 2026 | Added comprehensive test coverage stats and documented returns fixes                                                                                                          |
| 1.0     | Mar 2026 | Full Production Architecture: Subdomain isolation (`*.invent-mag.web.id`), Resend API Email Integration, Advanced Rate Limiting, Midtrans Tenant-Aware Webhooks, CI/CD fixes. |
| 1.0     | Mar 2026 | Documented Subscription system, Midtrans integration, and Railway deployment architecture                                                                                     |
| 1.4     | Mar 2026 | Financial & CRM Integrations: Full integration of Returns into GL, Dashboards, and CRM (Net LTV). `SalesHelper` & `PurchaseHelper` extraction. Opening Balance Module added.  |
| 1.3     | Mar 2026 | Turbo Drive integration: `onPageLoad` utility, `bootstrap-init.js`, `receipt-page.js`, POS reactivity fix, `DashboardService` extraction, auth error handling improvements.   |
| 1.5     | Apr 2026 | UI/UX Polish & Tutorial System: Contextual tutorial buttons on all pages. 30+ page-specific help modals. Consistent icon system. Partially Paid status standardization. Sales Return credit note conditions. PO autofill info. Journal entry audit log sidebar badge fix. |
| 1.9     | June 2026    | Sales Pipeline enhancements, tutorial contexts expansion, lang cleanup, onPageLoad deduplication fix. |
| 1.8     | May 2026     | Architecture Assessment Fixes: Migrated accounting settings to tenants, implemented CSP nonces, split route files, encrypted backup keys, made Redis the default. |
| 1.7     | May 2026     | Tutorial HR Expansion: Added HR & Payroll to welcome setup workflow. Added Employees, Attendance, Payroll to Master Data sub-topics. Tech spec category count updated (6→7), context count updated (30+→40+). |
| 2.0     | Jul 2026     | Platform Currency Display Preference: Added `PlatformCurrencySetting` model for super admin overview screens, `CurrencyHelper::formatPlatform()` method, `currency_code` columns on `plans` and `subscription_orders` tables. This is a display preference only — the single tenant `CurrencySetting` system remains the one currency system. All hardcoded `$` displays replaced with dynamic `formatPlatform()`. |
| 2.1     | Jul 2026     | Tax UX improvements and tutorial expansion (per-item discount type on quotations, tax auto-sync on sales, sales return tax breakdown, pipeline opportunity amount tax-inclusive, notes/terms columns migration, 4 new tutorial step keys, 17 failing unit tests fixed — 528 passing, 0 failed). |
| 2.3     | Aug 2026     | Tax Jurisdiction auto-seed (PPN 11% + PPh 23 2% auto-created on switch to Indonesia), 2 new Tax Jurisdiction vs Tax Configuration tutorial steps + refreshed stale tax steps, Bank Recon Import page tutorial, bug fixes (Fortify error bag, `canDisableIndonesianJurisdiction()` whereDoesntHave, Settings JS hardening, `pkp_date` date cast on Tenant/Customer fixing the e-Faktur Settings page crash). 1151 tests passing. |
| 2.2     | Aug 2026     | Bank Reconciliation module: BCA/Mandiri/BNI/BRI CSV/TXT import with auto format detection, auto + manual matching, opening-balance prefill, variance gate, entry locking, reconciled status badges in Journal/Ledger. New tables `bank_reconciliations` & `bank_reconciliation_items`. Feature gated to Professional+. Opening Balance vs Bank Reconciliation tutorial steps added. |

---

**END OF TECHNICAL SPECIFICATION**
