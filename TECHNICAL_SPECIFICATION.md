# Invent-MAG: Technical Specification Document

**Document Version:** 1.0  
**Last Updated:** February 2025  
**System Version:** 1.2

---

## Executive Summary

Invent-MAG is a production-ready, enterprise-grade ERP system built on Laravel 11 with 1.5 years of continuous development. This document provides detailed technical specifications for potential buyers, integrators, and technical evaluators.

### Key Technical Highlights

- **Multi-tenant SaaS architecture** with database-level isolation
- **60+ test files** ensuring code reliability
- **371+ commits** demonstrating sustained development
- **Docker + CI/CD** for modern DevOps practices
- **Service-oriented architecture** with clean separation of concerns

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
│  │ Controllers  │  │   Services   │  │   Requests   │      │
│  │              │  │              │  │   (Form)     │      │
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
**Isolation Level:** Database-level (tenant_id column)  
**Tenant Resolution:** Domain-based automatic detection

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

---

## 2. Code Quality & Testing

### 2.1 Test Coverage Statistics

| Category          | Count | Coverage                        |
| ----------------- | ----- | ------------------------------- |
| **Feature Tests** | 25+   | Controllers, Routes, End-to-end |
| **Unit Tests**    | 35+   | Services, Helpers, DTOs         |
| **Total Tests**   | 60+   | Comprehensive coverage          |

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
│   │   └── ... (20+ more)
│   ├── Auth/
│   │   ├── AuthenticationTest.php
│   │   ├── PasswordResetTest.php
│   │   └── EmailVerificationTest.php
│   └── Api/
│       ├── TenantLookupControllerTest.php
│       └── MultiTenancyTest.php
└── Unit/
    ├── Services/
    │   ├── AccountingServiceTest.php
    │   ├── SalesServiceTest.php
    │   ├── DashboardServiceTest.php
    │   └── ... (15+ more)
    ├── Helpers/
    │   ├── CurrencyHelperTest.php
    │   ├── SalesHelperTest.php
    │   └── ProductHelperTest.php
    └── Http/
        └── Requests/
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
            $this->createAccountingEntries($sale);
            $this->updateInventory($sale);
            return $sale;
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

#### DTO Pattern

```php
class TransactionDTO
{
    public function __construct(
        public string $accountCode,
        public string $type,
        public float $amount
    ) {}
}
```

---

## 3. Security Implementation

### 3.1 Authentication & Authorization

| Layer              | Implementation    | Details                          |
| ------------------ | ----------------- | -------------------------------- |
| **Authentication** | Laravel Fortify   | Email/password with verification |
| **API Auth**       | Laravel Sanctum   | Token-based API access           |
| **Authorization**  | Spatie Permission | 50+ granular permissions         |
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
// ... 50+ total permissions

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

### 5.3 API Documentation

**Generated Documentation:**

- **URL:** `/docs` after installation
- **Formats:** HTML, OpenAPI 3.0, Postman Collection
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

| Layer          | Technology   | Purpose                |
| -------------- | ------------ | ---------------------- |
| **Templating** | Blade        | Server-side rendering  |
| **CSS**        | Tailwind CSS | Utility-first styling  |
| **JavaScript** | Alpine.js    | Reactive components    |
| **Icons**      | Tabler Icons | Consistent iconography |
| **Build**      | Vite         | Asset compilation      |

### 7.2 JavaScript Organization

```
resources/js/
├── admin/
│   ├── accounting.js           # Accounting UI
│   ├── journal-entry.js        # Manual JE validation
│   ├── pos.js                  # POS functionality
│   ├── sales-pipeline.js       # Drag-drop kanban
│   ├── sales-order.js          # Dynamic line items
│   └── layouts/                # Layout components
├── utils/
│   ├── currencyFormatter.js    # Currency formatting
│   └── sanitize.js            # Input sanitization
└── app.js                      # Entry point
```

### 7.3 Key Frontend Features

**Sales Forecasting Chart:**

- Interactive charts
- Historical vs predicted data
- Confidence intervals

**POS Interface:**

- Barcode scanning
- Touch-friendly
- Receipt printing
- Payment calculation

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

**Formula:** Average Order Value × Purchase Frequency × Customer Lifespan

**Implementation:** Automated calculation per customer with historical tracking

---

## 9. Integration Capabilities

### 9.1 Current Integrations

| System      | Integration    | Status                |
| ----------- | -------------- | --------------------- |
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

- [ ] Environment variables configured
- [ ] Database credentials secured
- [ ] APP_KEY generated
- [ ] SSL certificate installed
- [ ] Email SMTP configured

### Deployment

- [ ] Run migrations
- [ ] Seed initial data
- [ ] Build production assets
- [ ] Configure queues
- [ ] Set up backups
- [ ] Configure monitoring

### Post-deployment

- [ ] Test all critical paths
- [ ] Verify email delivery
- [ ] Check error logging
- [ ] Monitor performance
- [ ] Set up alerts

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

| Version | Date     | Changes                         |
| ------- | -------- | ------------------------------- |
| 1.0     | Feb 2026 | Initial technical specification |

---

**END OF TECHNICAL SPECIFICATION**
