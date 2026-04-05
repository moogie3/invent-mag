# Invent-MAG: Technical Specification Document

**Document Version:** 1.4  
**Last Updated:** March 2026  
**System Version:** 1.4

---

## Executive Summary

Invent-MAG is a production-ready, enterprise-grade ERP system built on Laravel 11 with 1.5 years of continuous development. This document provides detailed technical specifications for potential buyers, integrators, and technical evaluators.

### Key Technical Highlights

- **Multi-tenant SaaS architecture** with row-level database isolation
- **786 test files** ensuring code reliability
- **451+ commits** demonstrating sustained development
- **Docker + CI/CD** for modern DevOps practices
- **Service-oriented architecture** with clean separation of concerns
- **Turbo Drive (Hotwire)** for SPA-like navigation without a frontend framework

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
│  │ MySQL/SQLite │  │ Redis (opt)  │                        │
│  │   Database   │  │    Cache     │                        │
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
**Currency:** USD displayed, IDR charged (16,000 exchange rate)

#### Subscription Tiers

| Tier         | Users     | Warehouses | Products  | Features                              |
| ------------ | --------- | ---------- | --------- | -------------------------------------- |
| Starter      | 3         | 1          | 100       | Basic (Products, Sales, Purchases)    |
| Professional | 10        | Unlimited  | 500       | + POS, Accounting, CRM, Pipeline     |
| Enterprise   | Unlimited | Unlimited  | Unlimited | + Forecasting, API, Audit Trail       |

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

---

## 2. Code Quality & Testing

### 2.1 Test Coverage Statistics

| Category          | Count | Coverage                        |
| ----------------- | ----- | ------------------------------- |
| **Feature Tests** | 40+   | Controllers, Routes, End-to-end |
| **Unit Tests**    | 40+   | Services, Helpers, DTOs         |
| **Total Tests**   | 786   | test files                      |

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
│   │   └── ... (30+ more)
│   ├── Auth/
│   │   ├── AuthenticationTest.php
│   │   ├── PasswordResetTest.php
│   │   ├── EmailVerificationTest.php
│   │   └── PasswordUpdateTest.php
│   ├── Api/
│   │   ├── TenantLookupControllerTest.php
│   │   └── MultiTenancyTest.php
│   └── MidtransWebhookTest.php
└── Unit/
    ├── Services/
    │   ├── AccountingServiceTest.php
    │   ├── SalesServiceTest.php
    │   ├── DashboardServiceTest.php
    │   ├── ActivityLogServiceTest.php
    │   ├── SecurityLoggerTest.php
    │   └── ... (40+ more)
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

Eloquent models act as repositories with custom scopes and methods.

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

**Benefits:**

- Eliminates calculation discrepancies between different modules (e.g. Dashboard vs CRM vs POS)
- Simplifies controllers and services
- Easy to unit test mathematical operations

---

## 3. Security Implementation

### 3.1 Authentication & Authorization

| Layer              | Implementation    | Details                          |
| ------------------ | ----------------- | -------------------------------- |
| **Authentication** | Laravel Fortify   | Email/password with verification |
| **API Auth**       | Laravel Sanctum   | Token-based API access           |
| **Authorization**  | Spatie Permission | 79 granular permissions          |
| **Passwords**      | Bcrypt            | Industry-standard hashing        |
| **Sessions**       | Encrypted cookies | Secure session management        |

### 3.2 Permission System

```php
// Example permissions defined
'view-products'
'create-products'
'edit-products'
'delete-products'
'view-accounting'
'edit-chart-of-accounts'
'post-manual-journal'
// ... 79 total permissions

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
| **XSS Prevention**     | Blade auto-escaping      | ✅ Active               |
| **SQL Injection**      | Query Builder/Eloquent   | ✅ Protected            |
| **Rate Limiting**      | Custom middleware        | ✅ 5 login attempts     |
| **Security Logging**   | SecurityLogger service   | ✅ Logs all auth events |
| **Input Sanitization** | sanitize.js + validation | ✅ XSS protected        |
| **Email Verification** | Fortify built-in         | ✅ Required             |

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
| **tenants**           | Multi-tenancy     | Master table       |
| **products**          | Product catalog   | 1000s per tenant   |
| **product_warehouse** | Stock levels      | 1000s per tenant   |
| **sales**             | Sales orders      | 10000s per tenant  |
| **purchase_orders**   | POs               | 10000s per tenant  |
| **accounts**          | Chart of accounts | 35+ per tenant     |
| **journal_entries**   | Accounting        | 10000s per tenant  |
| **transactions**      | Accounting lines  | 100000s per tenant |

### 4.3 Performance Optimizations

| Optimization      | Implementation                        |
| ----------------- | ------------------------------------- |
| **Indexing**      | Tenant_id + foreign keys indexed      |
| **Eager Loading** | Consistent `with()` usage in services |
| **Pagination**    | 20-50 items per page                  |
| **Caching**       | Permission cache (24 hours)           |
| **Transactions**  | DB::transaction for atomic operations |

---

## 5. API Architecture

### 5.1 API Implementation

**Framework:** Laravel Sanctum  
**Authentication:** Bearer tokens  
**Documentation:** Scribe (auto-generated)  
**Base URL:** `/api/v1`

### 5.2 API Endpoints

#### Authentication

```http
POST /api/login                    # User login
POST /api/register                 # User registration
POST /api/lookup-tenant           # Tenant discovery
POST /api/v1/refresh-token        # Token refresh
```

#### Products

```http
GET /admin/product/search         # Search products
GET /admin/product/search-by-barcode  # Barcode lookup
GET /admin/product/metrics        # Product analytics
GET /admin/product/expiring-soon  # Expiry alerts
```

#### Analytics

```http
GET /admin/supplier/metrics       # Supplier analytics
GET /admin/customer/metrics       # Customer analytics
GET /admin/api/settings           # System settings
```

#### Inventory Management

```http
GET /admin/product/{id}/main-stock                   # Check primary stock
GET /admin/product/{id}/warehouse-stock/{warehouseId} # Check warehouse stock
POST /admin/product/stock-check                      # Validate stock vs cart quantities
```

### 5.3 API Documentation

**Generated Documentation:**

- **URL:** `/docs` after installation
- **Formats:** HTML, OpenAPI 3.0, Postman Collection
- **Features:**
    - Interactive "Try It Out"
    - Code examples (cURL, JavaScript, PHP)
    - Request/response schemas
    - Authentication exampless

---

## 6. DevOps & Deployment

### 6.1 Docker Configuration

**Docker Compose Services:**

```yaml
services:
    app: # PHP 8.2-FPM
    webserver: # Nginx
    database: # MySQL 8.0
    redis: # Redis (optional)
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
- Stage progression
- Opportunity cards

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
| **Queue**   | Database/Redis | ✅ Job processing     |
| **Cache**   | File/Redis     | ✅ Performance        |
| **Storage** | Local/S3       | ✅ File uploads       |

### 9.2 API-Ready Architecture

**For Future Integrations:**

- RESTful API foundation
- Webhook support ready
- Event-driven architecture
- Queue-based processing

**Potential Integrations:**

- Payment gateways (Stripe, PayPal)
- Accounting software (QuickBooks, Xero)
- E-commerce platforms (Shopify, WooCommerce)
- Shipping providers
- SMS/WhatsApp notifications

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
- Load balancer compatible
- Queue workers scalable

**Vertical Scaling:**

- PHP 8.2+ optimized
- OPcache enabled
- Database query optimization
- Asset caching

---

## 11. Version History

| Version | Date          | Changes                                                                                                                                                                                                                                                                                  |
| ------- | ------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1.4     | March 2026    | Financial & CRM Integrations: Full integration of Sales and Purchase Returns into GL, Dashboards, and CRM (Adjusted LTV). Extracted `SalesHelper`, `PurchaseHelper`, and `ProductStockController` for DRY calculations. Added Opening Balance Module. Added `ACCOUNTING_SETUP_GUIDE.md`. |
| 1.3     | March 2026    | Turbo Drive (Hotwire) integration: `onPageLoad` utility, unified `bootstrap-init.js`, `receipt-page.js`, POS discount/tax reactivity fix, `DashboardService` extraction, auth error handling fix (ValidationException bypass + session-notifications auth page detection).               |
| 1.2     | March 2026    | Full Production Architecture: Subdomain isolation (`*.invent-mag.web.id`), Resend API Email Integration, Advanced Rate Limiting, Midtrans Tenant-Aware Webhooks, CI/CD fixes.                                                                                                            |
| 1.2     | March 2026    | Implemented subscription & payment system with Midtrans, tiered plans (Starter/Professional/Enterprise), feature gating, usage limits, plan badges in sidebar, invoice emails                                                                                                            |
| 1.2     | February 2026 | Dark theme improvements, notification system enhancements                                                                                                                                                                                                                                |
| 1.2     | January 2026  | Initial release with full ERP features                                                                                                                                                                                                                                                   |

---

## 11. Development Team Requirements

### 11.1 To Maintain

| Role                   | Time Required  | Skills              |
| ---------------------- | -------------- | ------------------- |
| **Laravel Developer**  | 20-40 hrs/week | PHP 8.2, Laravel 11 |
| **Frontend Developer** | 10-20 hrs/week | Tailwind, Alpine.js |
| **DevOps**             | 5-10 hrs/week  | Docker, CI/CD       |

### 11.2 To Extend

Common extension points:

- **New Modules:** Add service + controller + views
- **API Endpoints:** Add to existing controllers
- **Reports:** Extend ReportController
- **Integrations:** Add to Services layer

---

## 12. Deployment Checklist

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

## 13. Contact & Support

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
| 1.0     | Feb 2026 | Initial technical specification                                                                                                                                               |
| 1.0     | Feb 2026 | Added comprehensive test coverage stats and documented returns fixes                                                                                                          |
| 1.0     | Mar 2026 | Full Production Architecture: Subdomain isolation (`*.invent-mag.web.id`), Resend API Email Integration, Advanced Rate Limiting, Midtrans Tenant-Aware Webhooks, CI/CD fixes. |
| 1.0     | Mar 2026 | Documented Subscription system, Midtrans integration, and Railway deployment architecture                                                                                     |
| 1.4     | Mar 2026 | Financial & CRM Integrations: Full integration of Returns into GL, Dashboards, and CRM (Net LTV). `SalesHelper` & `PurchaseHelper` extraction. Opening Balance Module added.  |
| 1.3     | Mar 2026 | Turbo Drive integration: `onPageLoad` utility, `bootstrap-init.js`, `receipt-page.js`, POS reactivity fix, `DashboardService` extraction, auth error handling improvements.   |

---

**END OF TECHNICAL SPECIFICATION**
