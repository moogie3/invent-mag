# Invent-MAG: Enterprise Inventory & Accounting System

**Version:** 1.2  
**Author:** moogie3  
**License:** MIT  
**Development Time:** 1.5 years (371+ commits)  
**Status:** Production-Ready  

---

## 📊 Project Statistics

| Metric | Value |
|--------|-------|
| **🏗️ Architecture** | Multi-tenant SaaS |
| **🧪 Test Coverage** | 60+ test files (Feature & Unit) |
| **💻 Codebase** | 371+ commits, 1.5 years development |
| **🔐 Security** | RBAC with 50+ permissions, audit logging |
| **🐳 DevOps** | Docker + GitHub Actions CI/CD |
| **📡 API** | RESTful API with Sanctum authentication |
| **🌍 Multi-tenancy** | Database-level tenant isolation |

---

## 📘 Introduction

Invent-MAG is a comprehensive, enterprise-grade ERP system designed for modern businesses. Built on Laravel 11 with 1.5 years of continuous development, it combines inventory management, sales & purchase operations, customer relationship management (CRM), supplier relationship management (SRM), and a complete double-entry accounting suite into a single, cohesive platform.

Unlike basic inventory systems, Invent-MAG provides **sophisticated business intelligence** including sales forecasting using Holt-Winters algorithms, automated financial reporting, and comprehensive audit trails for compliance.

![Screenshot of Invent-MAG](screenshot.png)

---

## 🚀 Live Demo & Credentials

Experience Invent-MAG firsthand:

- **URL:** `https://invent-mag.com`
- **Admin Login:** `admin@example.com` / `password`
- **Manager Login:** `manager@example.com` / `password`
- **Staff Login:** `staff@example.com` / `password`

---

## ✨ Complete Feature List

### **📦 Inventory & Product Management**

#### Core Features
- **Product Catalog:** Full CRUD with images, categories, descriptions
- **Multi-Warehouse Support:** Track stock across unlimited warehouses
- **Real-time Stock Control:** Live inventory levels per warehouse
- **Stock Adjustments:** Auditable adjustments with reason codes
- **Low Stock Alerts:** Per-warehouse threshold notifications
- **Unit Management:** Multiple units of measure per product

#### Advanced Inventory Features
- **Product Expiry Tracking:** Monitor and alert on expiring products
- **Barcode Search:** Quick product lookup via barcode scanning
- **Stock Adjustment Audit Trail:** Before/after quantities, user tracking
- **Bulk Operations:** Delete, export, and update stock in bulk
- **Per-Customer Pricing:** Historical pricing per customer

---

### **💰 Sales & Customer Management (CRM)**

#### Sales Operations
- **Sales Order Management:** Full lifecycle from quote to payment
- **Point of Sale (POS):** Dedicated interface with receipt printing
- **Multiple Payment Methods:** Cash, card, transfer per transaction
- **Sales Returns:** Complete returns workflow with refunds
- **Invoice Generation:** PDF invoice creation

#### Customer Intelligence
- **Customer Database:** Full profiles with interaction history
- **Purchase History:** Complete customer transaction records
- **Customer Lifetime Value (LTV):** Automated calculation
- **Customer Retention Analysis:** Loyalty tracking
- **Per-Customer Pricing:** Historical price tracking

#### Advanced CRM Features
- **Sales Pipeline:** Kanban-style opportunity management
- **Pipeline Stages:** Customizable stages with drag-and-drop
- **Opportunity Tracking:** Value, probability, expected close dates
- **Pipeline Conversion:** Convert opportunities to sales orders
- **Interaction Tracking:** Log calls, emails, meetings, notes

#### Sales Analytics
- **Sales Forecasting:** ⭐ **Holt-Winters algorithm + Linear Regression**
  - Predicts future sales based on historical data
  - Seasonal trend analysis
  - Accuracy metrics
- **Sales Metrics:** Real-time dashboard KPIs
- **Collection Rate:** Automated performance tracking

---

### **🛒 Purchase & Supplier Management (SRM)**

#### Purchase Operations
- **Purchase Order Management:** Full CRUD with stock reconciliation
- **Multi-warehouse Receiving:** Receive to specific warehouses
- **Purchase Returns:** Complete returns to suppliers
- **Expiry Date Tracking:** Monitor product expiration dates

#### Supplier Intelligence
- **Supplier Database:** Profiles with interaction history
- **Purchase History:** Complete supplier transaction records
- **Supplier Analytics:** Performance tracking
- **Interaction Tracking:** Log all supplier communications

---

### **📊 Financial & Accounting Suite**

#### Chart of Accounts
- **Hierarchical COA:** 35+ pre-configured accounts
- **Account Types:** Assets, Liabilities, Equity, Revenue, Expenses
- **Contra Accounts:** Support for accumulated depreciation, allowances
- **Manual Entry Control:** Per-account permissions

#### Journal Entry System ⭐ **NEW**
- **Manual Journal Entries:** Full CRUD with multi-line support
- **Status Workflow:** Draft → Posted → Void
- **Balance Validation:** Automatic debit/credit balancing
- **Journal Reversal:** One-click reversal with automatic entries
- **Voiding:** Soft delete with audit trail
- **Audit Trail:** Complete change history for compliance

#### Core Accounting
- **General Journal:** All financial transactions
- **General Ledger:** Account-level transaction history
- **Trial Balance:** Automated balancing validation

#### Financial Reporting
- **Income Statement:** P&L with customizable periods
- **Balance Sheet:** Financial position snapshot
- **AR Aging Report:** Outstanding receivables (Current, 1-30, 31-60, 61-90, 90+ days)
- **AP Aging Report:** Outstanding payables (same buckets)
- **Cash Position:** Current cash across accounts

#### Advanced Accounting Features
- **Double-Entry Bookkeeping:** Automatic journal entry creation
- **Multi-Currency Support:** Transaction recording in multiple currencies
- **Opening Balances:** Setup wizard for new fiscal years
- **Recurring Entries:** Infrastructure for automated recurring journals
- **Audit Logging:** Complete accounting audit trail

---

### **⚙️ Administration & System**

#### Dashboard & Analytics
- **Interactive Dashboard:** Real-time KPIs and metrics
- **Sales Charts:** Visual sales trends
- **Financial Health:** Cash position, AR/AP summaries
- **Alerts:** Low stock, expiring products, due dates
- **Sales Forecast:** Visual forecast with confidence intervals

#### User Management
- **Role-Based Access Control:** Admin, Manager, Staff roles
- **Granular Permissions:** 50+ specific permissions
- **User Profiles:** Avatar support, password management
- **Email Verification:** Required for account activation

#### System Configuration
- **Currency Settings:** Multi-currency configuration
- **Tax Configuration:** Dynamic tax rate management
- **Theme Settings:** Dark/light mode with persistence
- **Notification Settings:** Email and in-app notifications
- **Category Management:** Product categorization
- **Unit Management:** Units of measure

#### Security & Compliance
- **Comprehensive Audit Logging:**
  - Password changes
  - Failed login attempts
  - Successful logins
  - Permission denials
  - Suspicious activity
- **Rate Limiting:** Custom throttling (5 login attempts, 3 reg/hour)
- **Input Sanitization:** XSS protection
- **Custom Error Pages:** 403, 404, 419, 429, 500, 503

---

### **🔌 API & Integrations**

#### RESTful API
- **Versioned API:** v1 with Sanctum authentication
- **Auto-Generated Documentation:** Scribe-generated at `/docs`
- **Postman Collection:** Ready to use
- **OpenAPI 3.0 Spec:** Standard specification

#### API Endpoints Include:
```
Authentication:
- POST /api/login
- POST /api/register
- POST /api/lookup-tenant
- POST /api/v1/refresh-token

Products:
- GET /admin/product/search
- GET /admin/product/search-by-barcode
- GET /admin/product/metrics
- GET /admin/product/expiring-soon

Analytics:
- GET /admin/supplier/metrics
- GET /admin/customer/metrics
- GET /admin/api/settings

Data:
- GET /admin/roles-permissions
- GET /admin/notifications/count
- GET /admin/customers/{id}/historical-purchases
- GET /admin/customers/{id}/product-history
```

---

### **🛠️ Developer Features**

#### Testing
- **60+ Test Files:** Feature and unit tests
- **PHPUnit 10+:** Modern PHP testing
- **Parallel Testing:** `php artisan test --parallel`
- **Multi-tenancy Tests:** Tenant isolation testing

#### Code Quality
- **Laravel Pint:** Code formatting
- **Service Layer Pattern:** Clean architecture
- **Repository Pattern:** Data access abstraction
- **Type Hints:** Full PHP 8+ type declarations

#### Developer Tools
- **ER Diagram Generator:** `php artisan generate:erd`
- **Laravel Pail:** Real-time log monitoring
- **API Documentation:** Auto-generated with Scribe
- **Backup System:** Spatie Laravel Backup

---

## 🔐 Security Architecture

| Layer | Implementation |
|-------|----------------|
| **Authentication** | Laravel Fortify + Email Verification |
| **API Security** | Laravel Sanctum (Token-based) |
| **Authorization** | Spatie Permission (50+ permissions) |
| **Tenant Isolation** | Spatie Multitenancy (Database-level) |
| **Password Security** | Bcrypt hashing |
| **CSRF Protection** | Built-in token validation |
| **XSS Prevention** | Blade auto-escaping + sanitize.js |
| **SQL Injection** | Query Builder/Eloquent ORM |
| **Rate Limiting** | Custom throttling per endpoint |
| **Audit Trail** | SecurityLogger + AccountingAuditLog |

---

## 🚀 Technology Stack

### Backend
- **Framework:** Laravel 11 (PHP 8.2+)
- **Authentication:** Laravel Fortify + Sanctum
- **Permissions:** Spatie Laravel Permission
- **Multi-tenancy:** Spatie Laravel Multitenancy
- **PDF Generation:** DomPDF
- **Testing:** PHPUnit 10+ + Paratest

### Frontend
- **Template Engine:** Blade
- **CSS Framework:** Tailwind CSS
- **JavaScript:** Alpine.js
- **Build Tool:** Vite
- **UI Kit:** Tabler

### Database & Storage
- **Primary:** MySQL (recommended)
- **Alternative:** SQLite
- **Cache:** File/Database (Redis ready)
- **Queue:** Database (Redis ready)
- **Backups:** Spatie Laravel Backup

### DevOps
- **Containerization:** Docker + Docker Compose
- **CI/CD:** GitHub Actions
- **Code Quality:** Laravel Pint
- **Documentation:** Scribe

---

## 🧪 Test Coverage

**60+ Test Files Covering:**

### Feature Tests
- Authentication & Authorization
- Product Management (CRUD, bulk operations)
- Sales & Purchase Orders
- POS System
- Sales Returns & Purchase Returns
- Warehouse Management
- Customer & Supplier CRM
- Accounting (Journal, COA, Reports)
- Sales Pipeline
- Multi-tenancy

### Unit Tests
- Service Layer (Dashboard, Sales, POS, Accounting, etc.)
- Helper Classes (Currency, Sales, Product)
- DTOs and Form Requests
- Middleware

### Running Tests
```bash
# All tests
php artisan test

# Parallel testing (faster)
php artisan test --parallel

# Frontend tests
npm run test:js
```

---

## 🐳 Docker Support

### Quick Start with Docker
```bash
# Clone and start
git clone https://github.com/moogie3/invent-mag.git
cd invent-mag
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate --seed

# Access app
http://localhost
```

### Docker Includes:
- PHP 8.2-FPM
- Nginx web server
- MySQL database
- Redis (optional)
- Automated CI/CD pipeline

---

## ⚙️ Installation

### Standard Installation

```bash
# 1. Clone repository
git clone https://github.com/moogie3/invent-mag.git
cd invent-mag

# 2. Install PHP dependencies
composer install

# 3. Install JavaScript dependencies
npm install

# 4. Configure environment
cp .env.example .env
php artisan key:generate
# Edit .env with your database credentials

# 5. Run migrations and seeders
php artisan migrate --seed

# 6. Build assets
npm run build

# 7. Serve application
php artisan serve
```

### Docker Installation (Recommended)

```bash
# 1. Clone and start
git clone https://github.com/moogie3/invent-mag.git
cd invent-mag
docker-compose up -d

# 2. Run migrations
docker-compose exec app php artisan migrate --seed

# 3. Access at http://localhost
```

---

## 📚 Documentation

### API Documentation
- **URL:** `/docs` (after installation)
- **Features:** Try It Out, code examples, OpenAPI spec
- **Formats:** HTML, Postman Collection, OpenAPI 3.0

### Code Documentation
- **ER Diagram:** `php artisan generate:erd`
- **API Spec:** Auto-generated via Scribe
- **Test Docs:** PHPUnit coverage reports

---

## 🗺️ Roadmap

### Recently Added (v1.2)
- ✅ Manual Journal Entry System
- ✅ Journal Entry Approval Workflow
- ✅ Comprehensive Audit Trail
- ✅ Recurring Journal Entry Infrastructure
- ✅ Enhanced Chart of Accounts
- ✅ Opening Balance Support

### Planned Features
- 🔄 Bank Reconciliation
- 🔄 Cash Flow Statement
- 🔄 Advanced Multi-Currency
- 🔄 Indonesian Tax Compliance (PPN, e-Faktur)
- 🔄 Mobile Application

---

## 🤝 Support & Contact

**Developer:** moogie3  
**GitHub:** [@moogie3](https://github.com/moogie3)  
**LinkedIn:** [Jefry Dwijaya](https://linkedin.com/in/jefry-dwijaya-01b48521a)

For issues and suggestions, please submit a GitHub issue.

---

## 📜 License

Invent-MAG is licensed under the **MIT License**. See the `LICENSE` file for details.

---

## 🙏 Acknowledgments

- Built with [Laravel](https://laravel.com)
- UI powered by [Tailwind CSS](https://tailwindcss.com) and [Tabler](https://tabler.io)
- Multi-tenancy by [Spatie](https://spatie.be)
- Icons by [Tabler Icons](https://tabler-icons.io)

---

**⭐ Star this repository if you find it helpful!**
