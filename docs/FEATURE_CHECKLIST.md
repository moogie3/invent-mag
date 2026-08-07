# Invent-MAG: Complete Feature Checklist

**Last Updated:** August 2026  
**Version:** 2.6  
**System Version:** 2.1

---

## ✅ IMPLEMENTED FEATURES

### 🏢 Core System

- [x] Multi-tenant SaaS architecture
- [x] Database-level tenant isolation
- [x] Subdomain-based tenant resolution (`*.invent-mag.web.id`)
- [x] Centralized API architecture (`api.invent-mag.web.id`)
- [x] Resend API Email integration
- [x] User authentication (Laravel Fortify)
- [x] Email verification required
- [x] Role-based access control (101 permissions, 3 roles)
- [x] API authentication (Laravel Sanctum)
- [x] Password security (bcrypt hashing)
- [x] Session management
- [x] CSRF protection
- [x] XSS prevention
- [x] SQL injection protection
- [x] Rate limiting (custom thresholds)
- [x] Security audit logging
- [x] Comprehensive audit trail
- [x] **Audit Log UI** - Dedicated audit log page with:
  - [x] Filterable by user, module, action, date range, search
  - [x] Summary cards (today, this week, this month, total events)
  - [x] Detail modal showing old/new values for changes
  - [x] Superuser-only access (bypasses plan limits)
  - [x] Multi-tenant isolation with tenant-specific data
  - [x] Module-specific actions (create, update, delete, view, post, void, reverse, etc.)

---

### 💳 Subscription & Payment System

- [x] Three-tier subscription plans (Starter, Professional, Enterprise)
- [x] Feature-gated access based on plan
- [x] Usage limits per plan (users, warehouses, products)
- [x] Midtrans payment gateway integration
- [x] Credit card payment processing
- [x] Sandbox testing environment
- [x] Dynamic pricing (USD displayed, IDR charged)
- [x] Payment confirmation via frontend API
- [x] Webhook-based payment verification
- [x] Invoice email sent on successful payment
- [x] Subscription order tracking
- [x] Plan upgrade/downgrade support
- [x] 30-Day Rolling Billing Cycle with Date Stacking
- [x] 7-Day Renewal Grace Period
- [x] Midtrans Webhook Fallback Protection
- [x] Current plan badge in sidebar
- [x] Security: Signature verification for webhooks
- [x] Security: Tenant ownership verification
- [x] Security: Double-processing prevention

---

### 📦 Inventory Management

- [x] Product catalog (CRUD)
- [x] Product images
- [x] Product categorization
- [x] Multi-warehouse support
- [x] Real-time stock tracking
- [x] Per-warehouse stock levels
- [x] Stock adjustments with audit trail
- [x] Low stock alerts (per warehouse)
- [x] Unit of measure management
- [x] Barcode support
- [x] Barcode search
- [x] Product expiry tracking
- [x] Expiry alerts
- [x] Bulk operations (delete, export)
- [x] Bulk stock updates

---

### 📦 Stock Opname & Audit (Professional+)

- [x] Stock Opname scheduling by warehouse and date
- [x] Multi-warehouse counting support
- [x] Snapshot of current stock on opname creation
- [x] Physical count entry with variance tracking
- [x] Auto-calculate shortage/excess quantities
- [x] Inventory adjustment on completion
- [x] Accounting journal integration
- [x] Audit trail for all counts
- [x] Complete workflow (Draft → In Progress → Completed/Canceled)

### 🔄 Stock Transfer (All Plans)

- [x] Warehouse-to-warehouse stock transfer
- [x] Source/destination warehouse selection
- [x] Real-time available stock display per warehouse
- [x] Quantity and reason entry
- [x] Automatic stock decrement from source warehouse
- [x] Automatic stock increment to destination warehouse
- [x] Dual StockAdjustment records with `adjustment_type = 'transfer'`
- [x] Adjustment log integration
- [x] Activity log tracking (MODULE_STOCK_TRANSFER)
- [x] Demo data seeder (`StockTransferSeeder`)
- [x] Full test suite (`StockTransferControllerTest` — 7 tests)
- [x] Linked from product edit page and adjustment log page

---

### 💰 Sales Management

- [x] Sales order management (CRUD)
- [x] Sales order workflow
- [x] Multiple payment methods per order
- [x] Payment tracking
- [x] Invoice generation (PDF)
- [x] Sales returns processing (with configurable accounting entries)
- [x] **Sales return bulk operations with full side effects** — bulk complete creates journal entries + issues credits; bulk cancel reverses stock + journal entries
- [x] **Sales return tax breakdown** — return show page displays tax rate and type label in the summary
- [x] Refund handling
- [x] **Consistent payment status labels** - Paid, Partially Paid, Unpaid (with sub-states: Pending, Due Today, Due in X Days, Overdue), Returned
- [x] Customer credit notes (auto-created when return is Completed AND invoice is Paid)
- [x] Customer database (CRUD)
- [x] Customer interaction tracking
- [x] Purchase history per customer
- [x] Adjusted Customer lifetime value (Net LTV subtracting returns)
- [x] Accurate category and product purchased counts (deducting returned quantities)
- [x] Customer retention analysis
- [x] Per-customer pricing
- [x] Historical pricing tracking
- [x] **Tax auto-sync on page load** — sales create/edit pages recalculate tax automatically on page load and on tax dropdown change
- [x] **`notes` and `terms_conditions`** columns on sales table (migration `2026_07_09_000001`)
- [x] Bulk operations (delete, mark paid, export)

---

### 🛒 Purchase Management

- [x] Purchase order management (CRUD)
- [x] PO workflow
- [x] Multi-warehouse receiving
- [x] Payment tracking for POs
- [x] **Consistent payment status labels** - Paid, Partially Paid, Pending (with sub-states: Due Today, Due in X Days, Overdue), Returned
- [x] **Autofill info label on create** - past price & due date based on supplier; supplier price based on supplier
- [x] Purchase returns processing (with stock adjustment and accounting entries)
- [x] **Purchase return bulk operations with full side effects** — bulk complete updates status; bulk cancel reverses stock + journal entries via cancelPurchaseReturn()
- [x] **Purchase return journal entry correctness** — uses Purchase Returns account (5120) credit instead of self-cancelling AP entry
- [x] Supplier database (CRUD)
- [x] Supplier interaction tracking
- [x] Purchase history per supplier
- [x] Supplier analytics
- [x] Product expiry tracking (PO items)
- [x] Bulk operations (delete, mark paid, export)
- [x] Purchase Order create page does not show tax (Accurate Online approach)
- [x] PO, Sales Returns, and Quotation index pages display tax column

---

### 🏪 Point of Sale (POS) (All Plans)

- [x] Dedicated POS interface
- [x] Barcode scanning
- [x] Touch-friendly design
- [x] Multiple payment methods
- [x] Payment calculation
- [x] Real-time discount and tax recalculation
- [x] Receipt generation
- [x] Receipt printing
- [x] Quick product search
- [x] **POS accounting journal entries** — Dr AR/Cr Revenue, Dr COGS/Cr Inventory, Dr Cash/Cr AR automatically posted on POS sale
- [x] POS uses Tax model dropdown instead of manual tax rate input

---

### 📊 CRM - Sales Pipeline

- [x] Sales pipeline management
- [x] Multiple pipelines support
- [x] Customizable pipeline stages
- [x] Drag-and-drop kanban board
- [x] Kanban/List view toggle
- [x] Opportunity tracking
- [x] Opportunity value tracking (with currency formatting)
- [x] **Opportunity amount is tax-inclusive grand total** — pipeline opportunity amount reflects the final tax-inclusive total
- [x] Opportunity notes & terms conditions
- [x] Probability scoring
- [x] Expected close dates
- [x] Quotation / Estimate PDF Generation
- [x] Pipeline conversion to sales orders
- [x] Stage progression tracking
- [x] Opportunity product associations

### 📄 Quotation Management

- [x] Full quotation CRUD (create, edit, view, delete)
- [x] 6-status lifecycle: Draft → Sent → Accepted → Converted (Rejected/Expired → Draft)
- [x] Professional PDF generation (DomPDF)
- [x] Convert Accepted quotation to Sales Order
- [x] Pipeline integration (generate quotation from opportunity)
- [x] Notes & terms conditions fields
- [x] Per-item discount (%/fixed) and tax (exclusive/inclusive/none)
- [x] Per-item discount **type selector** (% vs Fixed) — independent per line item
- [x] Order-level discount (%/fixed) and tax rate
- [x] Bulk delete and CSV export
- [x] Quotation auto-numbering (QTN-YYYYMMDD-XXXX)
- [x] Plan-gated feature (available on Professional & Enterprise)
- [x] 4 granular permissions (view/create/edit/delete)
- [x] Quotation uses Tax model dropdown instead of manual tax_type/tax_rate inputs

---

### 📈 Analytics & Reporting

#### Dashboard

- [x] Interactive dashboard
- [x] Real-time KPIs
- [x] Sales charts
- [x] Financial health summary
- [x] AR/AP summaries
- [x] Low stock alerts
- [x] Expiring product alerts
- [x] Due date notifications
- [x] System Notifications (Subscription, Backups, Setup Alerts)
- [x] Collection rate metrics

#### Sales Analytics

- [x] Sales forecasting
- [x] **Holt-Winters algorithm**
- [x] **Linear regression**
- [x] Seasonal trend analysis
- [x] Confidence intervals
- [x] Historical accuracy tracking
- [x] Sales trends visualization

#### Financial Reports

- [x] Income Statement (P&L)
- [x] Balance Sheet
- [x] Cash Flow Statement (operating, investing, financing activities)
- [x] Trial Balance
- [x] AR Aging Report (buckets: Current, 1-30, 31-60, 61-90, 90+)
- [x] AP Aging Report (same buckets)
- [x] Cash position tracking
- [x] Adjustment log
- [x] Recent transactions report
- [x] All reports exportable (PDF, CSV)

#### Product Analytics

- [x] Product metrics API
- [x] Expiring soon tracking
- [x] Low stock analytics
- [x] Sales velocity

#### Customer/Supplier Analytics

- [x] Customer metrics API
- [x] Supplier metrics API
- [x] Purchase history analysis
- [x] Interaction tracking

---

### 💼 Accounting Suite

#### Chart of Accounts

- [x] Hierarchical COA
- [x] 35+ pre-configured accounts
- [x] Account types (Asset, Liability, Equity, Revenue, Expense)
- [x] Contra account support
- [x] Account code management
- [x] Manual entry permissions per account
- [x] Parent-child relationships

#### Journal Entry System ⭐

- [x] Manual journal entries
- [x] Multi-line transactions
- [x] Draft status
- [x] Posted status
- [x] Void status
- [x] Automatic balance validation
- [x] Journal entry reversal
- [x] Voiding with reason
- [x] Edit draft entries
- [x] Duplicate entries
- [x] Audit trail for all changes
- [x] Dependent entry validation before unposting
- [x] **Audit log sidebar badges** - Role badges on journal entry show page use correct styling (`bg-purple text-white`)

#### Core Accounting

- [x] General Journal
- [x] General Ledger
- [x] Trial Balance with validation
- [x] Double-entry bookkeeping
- [x] Automatic journal entry creation (from sales/purchases/returns)
- [x] Automatic Reversing Journal Entries on Invoice Deletion
- [x] FIFO (First-In, First-Out) COGS Calculation

#### Advanced Accounting

- [x] Multi-currency support
- [x] Currency configuration
- [x] Opening balance setup wizard (New Fiscal Year)
- [x] General Ledger accurately factors in Sales/Purchase Returns
- [x] Recurring journal entry infrastructure
- [x] Journal entry approval workflow
- [x] Comprehensive accounting audit log

#### Bank Reconciliation (Professional + Enterprise)
- [x] CSV/TXT statement import (BCA, Mandiri, BNI, BRI) with per-bank parser + auto delimiter/number/date detection
- [x] Auto-match journal entries by amount + date proximity (±5 days)
- [x] Manual match / unmatch UI for statement lines
- [x] Reconciliation lock — matched journal entries cannot be reversed, voided, or unposted after completion
- [x] Opening / statement closing / computed closing balance + variance
- [x] **Variance gate** — reconciliation cannot be completed when variance ≠ 0 (0.005 tolerance); Complete button disabled out-of-balance
- [x] **Opening balance prefill** — statement opening balance prefilled from the account's Opening Balance (adjustable to the statement)
- [x] **Reconciled status badges** — matched entries show "Terekonsiliasi" in Journal, Ledger, and manual-entry lists (`withReconciledStatus()` scope, no N+1)
- [x] Open → Completed status workflow with reconciliation history list
- [x] **Import page tutorial** — page-specific context (`admin.bank-reconciliation.create`) explaining why the statement must be imported first; "Statement Start vs System Opening Balance" step clarifies the prefill

---

### 👥 HR & Payroll (Professional + Enterprise)

#### Employee Management
- [x] Employee profile CRUD (Name, Code, Department, Position, Type)
- [x] Base salary & banking details tracking
- [x] Employment status tracking (Active, Resigned, etc.)
- [x] **Default Allowances & Deductions** per employee
- [x] Employee history tracking

#### Attendance Tracking
- [x] Monthly attendance period management
- [x] Open / Close periods workflow
- [x] **Configurable Working Days** per month (flexible for Mon-Fri vs Mon-Sat work weeks)
- [x] Interactive daily grid (Present, Absent, Sick, Leave, Holiday, Half Day)
- [x] Fast individual entry via keyboard
- [x] **Bulk Fill** tool for massive attendance updates
- [x] Custom "Skip Days" selector (e.g. automatically skip weekends during bulk fill)
- [x] Overtime hours tracking

#### Payroll Engine
- [x] Automated monthly payroll generation
- [x] **Salaried Proration Engine:** Auto-deducts pay for unpaid absences: `(Base Salary / Total Working Days) * Absent Days`
- [x] Half-day proration support
- [x] Auto-populates recurring default allowances and deductions
- [x] Draft payslip editing
- [x] **Two-Step Accounting Flow:**
  - [x] *Step 1 (Approve):* Creates Accrual Journal Entry (`Debit: Salary Expense, Credit: Salary Payable`)
  - [x] *Step 2 (Pay):* Select specific bank account and creates Payment Journal Entry (`Debit: Salary Payable, Credit: Bank`)
- [x] PDF Payslip generation and download

---

### 🔔 Notifications

- [x] In-app notification system
- [x] Real-time notification count
- [x] Notification list
- [x] Mark as read functionality
- [x] Low stock alerts
- [x] Expiring product alerts
- [x] Due date alerts
- [x] Sound notifications (optional)
- [x] **System Notifications** — Dashboard notification center with categories:
  - [x] Plan expiry alerts (14-day warning before subscription expires)
  - [x] Product expiry alerts (items expiring within 30/7 days)
  - [x] Backup status alerts (no backup taken in 7+ days)
  - [x] Trial status notifications
  - [x] Usage limit warnings
  - [x] Accounting setup reminders
  - [x] Plan upgrade/downgrade notifications

---

### 👥 User Management

- [x] User CRUD
- [x] Role assignment (Superuser, Staff, POS)
- [x] Granular permissions (101)
- [x] Email verification
- [x] Password reset
- [x] Profile management
- [x] Avatar upload
- [x] Password change
- [x] API token generation
- [x] User settings

---

### ⚙️ System Settings

- [x] Currency settings
- [x] Multi-currency configuration
- [x] Platform currency setting (Super Admin) — independent from tenant currencies
- [x] Tax configuration
- [x] Dynamic tax rates
- [x] **Auto-seeded Indonesian defaults** — PPN 11% + PPh 23 2% + PPh Final 0.5% created automatically when Tax Jurisdiction is switched to Indonesia (no manual setup needed); full six-record catalog via `TaxSeeder` (adds PPN 12%, Non-PKP, PPN Inclusive)
- [x] Theme settings (Dark/Light mode)
- [x] Theme persistence
- [x] Category management
- [x] Unit management
- [x] Warehouse management
- [x] Main warehouse designation

---

### 🏢 Super Admin Panel (SaaS Management)

#### Authentication & Access
- [x] Separate `SuperAdmin` model (table: `super_admins`) — independent from tenant `User` model
- [x] Dedicated `super_admin` auth guard with its own Eloquent provider
- [x] Root domain hosting (`invent-mag.web.id`) — fully isolated from tenant subdomains
- [x] Rate-limited login (5 attempts per email+IP, 60-second lockout)

#### Tenant Management
- [x] Tenant listing with plan info and status
- [x] Tenant detail view with domain editing
- [x] Toggle tenant active/inactive status
- [x] **Tenant impersonation** — encrypted token cross-domain handoff via `CaptureImpersonation` middleware; super admin redirected to `{tenant.domain}/admin/dashboard?impersonation_token=...`; yellow banner with "Stop Impersonating" shown on tenant side

#### Plan Management
- [x] Full plan CRUD (slug, name, price, description, limits, trial days)
- [x] Feature assignment per plan (matrix view with bulk update)
- [x] Plan deletion guarded against active subscribers
- [x] `currency_code` per plan for historical currency tracking

#### Subscription Management
- [x] Subscription listing with stats (active/trialing/expired/terminated)
- [x] Terminate subscription (voids pending orders, clears billing)
- [x] Reactivate terminated subscription (restores billing cycle)
- [x] Scheduled downgrade support

#### Financial Operations
- [x] Billing dashboard (revenue totals, order counts)
- [x] Payment overview (revenue statistics, gateway breakdown)
- [x] Transaction history (searchable/filterable)
- [x] Pending payment approval/rejection workflow
- [x] Paid invoice listing and detail views
- [x] Order voiding with audit trail

#### System Administration
- [x] Application settings (.env editor: app name, URL, debug, mail)
- [x] User preferences (theme, notifications, performance — persisted per super admin)
- [x] Platform currency configuration (symbol, separators, position, code)
- [x] Permissions overview (view-only listing of tenant roles and permissions)
- [x] System-wide and per-tenant backup management
- [x] Backup restore (with SQL file upload)
- [x] Platform-level audit log with module/action filtering

#### Landing Page CMS
- [x] CMS editor for hero section, features, pricing, and contact content

#### Views & Tutorial
- [x] 28 Blade views in `resources/views/superadmin/`
- [x] Dedicated tutorial system (`config/superadmin-tutorial.php` — 258 lines)
- [x] Sidebar navigation with grouped sections (Management, Payments, System, Configuration)

---

### 🧾 e-Faktur PPN (Professional+ — feature slug: `ppn`)

- [x] **Phase 1 — COMPLETE** (July 2026)
- [x] DB migrations: `faktur_pajak`, `faktur_pajak_items`, `nsfp_pool` tables
- [x] DB migrations: `ppn`/`npwp`/`pkp_*` fields on `tenants`, `customers`, `sales`
- [x] `ppn` feature slug added to Professional & Enterprise plans
- [x] `FakturPajak` model with `BelongsToTenant`, full relationships, helpers
- [x] `FakturPajakItem` model with `BelongsToTenant`
- [x] `NsfpPool` model with `BelongsToTenant` + `scopeAvailable()`
- [x] `Sales::fakturPajak()` HasOne relationship
- [x] `EFakturService` — generate, cancel, NSFP import, DJP CSV export, SPT Masa summary
- [x] Atomic NSFP allocation with `lockForUpdate()` (race condition safe)
- [x] PPN Keluaran journal entry via `AccountingService` on faktur generation
- [x] `EFakturController` — index, show, generate, cancel, print (Dompdf PDF), exportCsv, settings, saveSettings, importNsfp
- [x] Routes in `routes/admin/efaktur.php` under `plan.feature:ppn` middleware
- [x] Blade views: `efaktur/index.blade.php`, `show.blade.php`, `settings.blade.php`, `faktur-pdf.blade.php`
- [x] DJP-compliant A4 PDF layout (NSFP, penjual/pembeli, BKP items, DPP/PPN totals, signature block)
- [x] DJP CSV export: FK header + OF item rows, UTF-8 BOM, amounts in cents
- [x] PPN determined by tax dropdown selection (`Tax.is_ppn`) + PPN line on sales view/amount-summary
- [x] "Buat Faktur Pajak" / "Lihat Faktur Pajak" button on sales view page
- [x] e-Faktur nav menu entry (Professional+ only)
- [x] Tax Region configurable from Settings page (no e-Faktur access required)
- [x] Navigation jurisdiction filtering — e-Faktur menu hidden for non-Indonesian tenants
- [x] **Tax Jurisdiction auto-seed** — switching to Indonesia auto-creates PPN 11% (`is_ppn`) + PPh 23 2% + PPh Final 0.5% (`is_pph`) records via `TaxService::ensureIndonesianDefaults()` (idempotent; preserves existing default)
- [x] **VAT split at sale creation (Phase 1E)** — PPN Keluaran recognized when the sale is created: `Dr AR (grand total) / Cr Revenue (net of VAT) / Cr PPN Keluaran` for Indonesian PPN sales (5-leg journal, graceful 4-leg gross fallback). Faktur Pajak issuance is compliance-only (no journal). Sales returns reverse PPN whenever the sale `isPpn()` (no Faktur Pajak required). PPh 22 seed replaced by **PPh Final 0.5%** (PP 23/2018); existing rows converted in place via migration `2026_08_05_000002`
- [x] **Jurisdiction vs Tax Configuration tutorial** — 2 new tutorial steps (System Settings + Tax page) clarifying the jurisdiction switch vs tax records; stale tax steps refreshed
- [x] **PPN Masukan (Input VAT) on Purchases** — `ppn_enabled/rate/amount` snapshot fields on `po`; PPN Masukan debited to Input VAT asset account (1160) instead of capitalizing into inventory; `ppn_masukan_account_id` wired into accounting settings (seeder + backfill + registration); select on Accounting Settings page
- [x] **SPT Masa PPN summary includes PPN Masukan** — `EFakturService::getSptMasaSummary()` now returns `total_ppn_masukan`, `net_ppn_payable` and `ppn_credit` from `po` records (auto-scoped to tenant, filtered by period + `ppn_enabled`)
- [x] **PPN Keluaran recognized at sale creation (Phase 1E)** — VAT split journal (`Dr AR gross / Cr Revenue net / Cr PPN Keluaran`) posted in `SalesService::createSale()` and `PosService` for Indonesian PPN sales; Faktur Pajak generation no longer posts a PPN journal (compliance-only); sales returns reverse PPN without requiring a Faktur Pajak
- [ ] **Phase 2 — PLANNED:** Klikpajak PJAP API direct DJP submission (Enterprise only)

---

### 🔑 Tenant Impersonation (Super Admin)

- [x] "Impersonate Tenant" button on superadmin tenant detail page
- [x] `TenantController::impersonate()` — encrypts payload, redirects to `{tenant.domain}/admin/dashboard?impersonation_token=...`
- [x] `CaptureImpersonation` middleware — decrypts token, logs in tenant user, sets impersonation session, strips token from URL
- [x] Yellow impersonation banner on tenant admin panel with "Stop Impersonating" button
- [x] `stopImpersonating()` — clears session, logs out, redirects to superadmin return URL

---

### 🔌 API & Integrations

- [x] RESTful API (v1)
- [x] Sanctum token authentication
- [x] Auto-generated API documentation (Scribe)
- [x] OpenAPI 3.0 specification
- [x] Postman collection generation
- [x] "Try It Out" functionality
- [x] API rate limiting
- [x] Tenant-aware API endpoints

**API Endpoints (essential REST API under `/api` — Scribe-documented):**

- [x] Authentication: `POST /api/register`, `POST /api/login`, `POST /api/lookup-tenant`, `POST /api/v1/refresh-token`
- [x] CMS: `GET /api/cms`
- [x] Payments: `POST /api/payment/confirm` (Bearer), `POST /api/webhooks/midtrans`, `POST /api/webhook`
- [x] Plans: `GET /api/v1/plans`, `GET /api/v1/plan/current`
- [x] Contact: `POST /api/contact` (marketing-site form → founder email)

> Admin CRUD (products, suppliers, customers, stock, settings, etc.) is **session-authenticated web AJAX under `/admin/*`** — not part of the REST API. Full REST CRUD is a future Enterprise expansion.

---

### 🧪 Testing

- [x] PHPUnit 11+ configured
- [x] 1186 PHP test methods / 3715 PHP assertions (Feature + Unit PHP, 169 JS Vitest files)
- [x] **Plan-tier gating regression suite** — `PlanFeatureGateTest` (15 tests) verifying every Professional/Enterprise feature slug is enforced at the route layer (`plan.feature:*` middleware) or controller layer (sales forecasting), including the JSON 403 response for API clients and the legacy no-plan full-access path
- [x] Feature tests (controllers, routes)
- [x] Unit tests (services, helpers)
- [x] Multi-tenancy tests
- [x] Tenant creation trait
- [x] Parallel testing support
- [x] Vitest for frontend (169 JS test files)

---

### 🐳 DevOps

- [x] Docker support
- [x] Docker Compose configuration
- [x] PHP 8.2-FPM container
- [x] Nginx container
- [x] MySQL container
- [x] Redis support (optional)
- [x] GitHub Actions CI/CD
- [x] Automated testing pipeline
- [x] Deployment workflow
- [x] Backup system (Spatie)

---

### 🛠️ Developer Tools

- [x] API documentation generator (Scribe)
- [x] ER diagram generator
- [x] Laravel Pint (code formatting)
- [x] Laravel Pail (log monitoring)
- [x] Parallel testing
- [x] Type hints throughout
- [x] Service layer pattern
- [x] DTO pattern
- [x] Request validation
- [x] Custom middleware

---

### 🔒 Security Features

- [x] Multi-tenant isolation
- [x] Database-level tenant separation
- [x] Role-based access control
- [x] Granular permissions
- [x] Authentication logging
- [x] Password change logging
- [x] Failed login tracking
- [x] Permission denial logging
- [x] Suspicious activity detection
- [x] Input sanitization
- [x] XSS protection
- [x] Custom error pages (403, 404, 419, 429, 500, 503)
- [x] Auth-specific ValidationException handling bypassing global 500 error

---

### 🎨 UI/UX Features

- [x] Responsive design (Tailwind CSS)
- [x] Turbo Drive (Hotwire) SPA-like navigation
- [x] Dedicated `onPageLoad` utility for Turbo-compatible initialization
- [x] Unified `bootstrap-init.js` for dynamic DOM component reattachment
- [x] Dark/Light theme toggle
- [x] Theme persistence
- [x] Alpine.js interactions
- [x] Tabler UI components
- [x] Modal dialogs
- [x] Form validation — **global standardized system** (`resources/js/admin/utils/formValidator.js`)
  - [x] All admin forms carry `novalidate data-validate-form` (browser native popups disabled)
  - [x] Capture-phase submit listener runs before Turbo — validates all `[required]` fields
  - [x] Highlights **every** empty required field with `is-invalid` + shows InventMagApp `'Warning'` toast + focuses first invalid field
  - [x] Custom JS handlers guard with `if (e.defaultPrevented) return;` so validation failures stop submission
- [x] Toast notifications
- [x] Auth-specific session error/success modals
- [x] Loading states
- [x] Global keyboard shortcuts
- [x] Sound notifications (optional)
- [x] **Contextual tutorial button system** - Every page has a tutorial (?) button in the page title that opens a contextual help modal specific to that page
- [x] **Consistent page title iconography** - All pages use `ti-*` icons before the title text (`ti-plus` for create, `ti-edit` for edit, `ti-eye` for view)
- [x] **Tutorial modal** - Multi-step modal covering setup workflow, topic grid, and page-specific quick guide with 50+ page contexts covered including Sales Pipeline stages and Quotation lifecycle
- [x] **Sub-topic drill-down navigation** - Topics grid drills into category-specific page lists (Configuration → System Settings, Units, Categories...), clicking navigates to that page and auto-shows its contextual tutorial
- [x] **Click-outside to close** - Clicking the modal backdrop now closes the tutorial modal (was static before)
- [x] **Tax Jurisdiction vs Tax Configuration steps** - Tutorial steps on System Settings and Tax pages explaining the difference between the jurisdiction switch and actual tax records (incl. auto-seed note); refreshed stale tax steps (`define_tax_rates`, `sales_application`, `tax_configuration_desc`)
- [x] **Bank Reconciliation import page tutorial** - `admin.bank-reconciliation.create` context explaining statement import; "Statement Start vs System Opening Balance" step

---

## 🚧 PLANNED FEATURES

### High Priority

- [ ] Bank reconciliation
- [ ] Advanced multi-currency (real-time rates)
- [ ] e-Faktur Phase 2: PJAP API (Klikpajak direct DJP submission)

### Medium Priority

- [x] **Indonesian tax compliance (PPN 11%)** — Phase 1 complete (see e-Faktur section below)
- [x] **e-Faktur CSV export** — DJP FK/OF format with UTF-8 BOM, amounts in cents
- [x] **PPN Masukan (Input VAT) tracking** — PPN on purchases (`po.ppn_enabled`) recorded as Input VAT (1160) with SPT Masa PPN summary showing Keluaran − Masukan = Kurang/Lebih Bayar
- [ ] Bank integrations (BCA, BNI, Mandiri)
- [ ] WhatsApp invoice delivery
- [ ] Midtrans payment links on invoices

### Low Priority

- [x] PPh 23 (Withholding Tax on services) — completed via seeded `is_pph` Tax record (2%)
- [ ] PPh 21 (employee withholding) — UMKM-flat "Payroll Tax" picker on payroll screen (withholding line in payment journal); explicitly NOT a full TER/PTKP/1721-II engine
- [x] PPh Final 0.5% (UMKM final income tax, PP 23/2018 & PP 20/2026) — completed via seeded `PPh Final 0.5%` Tax record (0.5%, `is_pph = true`, added to `TaxSeeder` + `TaxService::ensureIndonesianDefaults()` + backfill migration `2026_08_05_000002` converting existing `PPH 22` rows in place); flows through existing PPh dropdowns + shared PPh Payable (2150) unchanged. `PPH 22` (1.5%) remains creatable on the Tax page but is no longer seeded
- [ ] PPh 25 (installment tax payments) — deferred; manual journal entry suffices for UMKM
- [ ] Mobile app (React Native/Flutter)
- [ ] Advanced BI dashboards

---

## 📊 STATISTICS

| Metric               | Value     |
| -------------------- | --------- |
| **Total Features**   | 220+      |
| **Controllers**      | 62        |
| **Services**         | 46        |
| **Models**           | 51        |
| **Helpers**          | 6         |
| **Test Methods (PHP)** | 1186     |
| **Test Assertions (PHP)** | 3715   |
| **JS Test Files**    | 169       |
| **API Endpoints**    | 11        |
| **Database Tables**  | 55        |
| **Migrations**       | 100+      |
| **Seeders**          | 36        |
| **Permissions**      | 101       |
| **Routes**           | 220+      |
| **JavaScript Files** | 189       |
| **Languages**        | 2 (EN/ID) |
| **Development Time** | 1.5 years |
| **Total Commits**    | 527+      |

---

## 🏆 KEY DIFFERENTIATORS

1. **Sales Forecasting** - Holt-Winters algorithm (rare in ERPs)
2. **Complete Accounting** - Double-entry with manual journal entries
3. **Multi-tenant SaaS** - True isolation, single codebase
4. **Sales Pipeline** - Kanban with forecasting
5. **1186 PHP Tests / 3715 Assertions + 169 Vitest Files** - Enterprise-grade testing (PHP + JS)
6. **Docker + CI/CD** - Modern DevOps ready
7. **Comprehensive Audit Trail** - Security and accounting
8. **Contextual Tutorial System** - 50+ page-specific help guides with drill-down navigation - users click a topic category, see all pages in that category, click one to navigate there with its contextual tutorial auto-opened

---

**Document Version:** 2.7  
**Last Updated:** August 2026
