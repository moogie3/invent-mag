# Project Structure: invent-mag

```
invent-mag/
├── .dockerignore
├── .editorconfig
├── .env.example
├── .gitattributes
├── .gitignore
├── artisan
├── composer.json
├── docker-compose.yml
├── Dockerfile
├── graph.png                        # ERD diagram
├── LICENSE
├── package.json
├── phpunit.xml
├── postcss.config.js
├── README.md
├── screenshot.png
├── tailwind.config.js
├── vite.config.js
│
├── docs/                            # All documentation (no markdown at repo root)
│   ├── ACCOUNTING_SETUP_GUIDE.md
│   ├── ARCHITECTURE_ASSESSMENT.md
│   ├── FEATURE_CHECKLIST.md
│   ├── INTERVIEW_QA.md
│   ├── LANDING_PAGE_CMS.md
│   ├── PPN_EVOLUTION.md
│   ├── ROADMAP.md
│   ├── STRUCTURE.md
│   ├── TAXATION_IMPROVEMENT.md
│   └── TECHNICAL_SPECIFICATION.md
│
├── app/
│   ├── Actions/Fortify/
│   │   ├── CreateNewUser.php
│   │   ├── PasswordValidationRules.php
│   │   ├── ResetUserPassword.php
│   │   ├── UpdateUserPassword.php
│   │   └── UpdateUserProfileInformation.php
│   │
│   ├── Console/Commands/
│   │   ├── BackupTenant.php
│   │   ├── ProcessScheduledPlanChanges.php
│   │   └── ResetDemoTenant.php
│   │
│   ├── DTOs/
│   │   └── TransactionDTO.php
│   │
│   ├── Exceptions/
│   │   ├── Handler.php
│   │   └── PlanLimitExceededException.php
│   │
│   ├── Helpers/
│   │   ├── CurrencyHelper.php
│   │   ├── ProductHelper.php
│   │   ├── PurchaseHelper.php
│   │   ├── PurchaseReturnHelper.php
│   │   ├── SalesHelper.php
│   │   └── SalesReturnHelper.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Controller.php
│   │   │   ├── SuperAdmin/
│   │   │   │   ├── SettingController.php   # Also handles platform-currency settings
│   │   │   │   ├── PlanController.php      # Now handles currency_code on plans
│   │   │   │   └── ... (16 super admin controllers)
│   │   │   ├── Admin/
│   │   │   │   ├── AccountingController.php
│   │   │   │   ├── AuditLogController.php
│   │   │   │   ├── BackupController.php
│   │   │   │   ├── CategoryController.php
│   │   │   │   ├── CurrencyController.php
│   │   │   │   ├── CustomerController.php
│   │   │   │   ├── CustomerCrmController.php
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── EFakturController.php       ← NEW (July 2026)
│   │   │   │   ├── JournalEntryController.php
│   │   │   │   ├── NotificationController.php
│   │   │   │   ├── OpeningBalanceController.php
│   │   │   │   ├── PlanController.php
│   │   │   │   ├── POSController.php
│   │   │   │   ├── ProductController.php
│   │   │   │   ├── ProductStockController.php
│   │   │   │   ├── ProfileController.php
│   │   │   │   ├── PurchaseController.php
│   │   │   │   ├── PurchaseReturnController.php
│   │   │   │   ├── QuotationController.php
│   │   │   │   ├── ReportController.php
│   │   │   │   ├── SalesController.php
│   │   │   │   ├── SalesPipelineController.php
│   │   │   │   ├── SalesReturnController.php
│   │   │   │   ├── SettingsController.php
│   │   │   │   ├── StockOpnameController.php
│   │   │   │   ├── StockTransferController.php
│   │   │   │   ├── SupplierController.php
│   │   │   │   ├── SupplierCrmController.php
│   │   │   │   ├── TaxController.php
│   │   │   │   ├── UnitController.php
│   │   │   │   ├── UserController.php
│   │   │   │   ├── WarehouseController.php
│   │   │   │   └── Hr/
│   │   │   │       ├── AttendanceController.php
│   │   │   │       ├── EmployeeController.php
│   │   │   │       ├── PayrollController.php
│   │   │   │       └── PayslipController.php
│   │   │   ├── Api/
│   │   │   │   ├── Auth/
│   │   │   │   │   ├── AuthController.php
│   │   │   │   │   └── RegisteredUserController.php
│   │   │   │   ├── ContactController.php
│   │   │   │   ├── PaymentController.php
│   │   │   │   ├── TenantLookupController.php
│   │   │   │   └── Webhook/MidtransController.php
│   │   │   └── Auth/
│   │   │       └── LoginController.php
│   │   │
│   │   ├── Middleware/
│   │   │   ├── CaptureImpersonation.php        ← Decrypts impersonation token, logs in tenant user
│   │   │   ├── CheckPlanFeature.php
│   │   │   ├── CheckPlanLimit.php
│   │   │   ├── CheckSubscriptionStatus.php
│   │   │   ├── DebugModeMiddleware.php
│   │   │   ├── HandleTenantFromPath.php
│   │   │   ├── RedirectIfAuthenticated.php
│   │   │   ├── SecurityHeaders.php
│   │   │   ├── SetLocale.php
│   │   │   └── SetSessionCookieName.php
│   │   │
│   │   ├── Requests/
│   │   │   ├── Auth/LoginRequest.php
│   │   │   └── ProfileUpdateRequest.php
│   │   │
│   │   ├── Resources/              # API Resources
│   │   │   ├── AccountResource.php ... WarehouseResource.php
│   │   │   └── (30 resources total)
│   │   │
│   │   └── Responses/              # Fortify customization
│   │       ├── CustomLoginResponse.php
│   │       ├── CustomRegisterResponse.php
│   │       ├── LogoutResponse.php
│   │       ├── PasswordResetResponse.php
│   │       └── VerifyEmailResponse.php
│   │
│   ├── Mail/
│   │   └── SubscriptionInvoiceMail.php
│   │
│   ├── Models/
│   │   ├── Account.php
│   │   ├── AccountingAuditLog.php
│   │   ├── ActivityLog.php
│   │   ├── AttendancePeriod.php
│   │   ├── AttendanceRecord.php
│   │   ├── Categories.php
│   │   ├── CurrencySetting.php             # Per-tenant currency settings
│   │   ├── PlatformCurrencySetting.php     # Super admin (platform) currency settings
│   │   ├── Customer.php
│   │   ├── CustomerCredit.php
│   │   ├── CustomerInteraction.php
│   │   ├── Employee.php
│   │   ├── FakturPajak.php                 ← NEW (July 2026)
│   │   ├── FakturPajakItem.php             ← NEW (July 2026)
│   │   ├── JournalEntry.php
│   │   ├── NsfpPool.php                    ← NEW (July 2026)
│   │   ├── OpeningBalance.php
│   │   ├── Payment.php
│   │   ├── PayrollItem.php
│   │   ├── PayrollRun.php
│   │   ├── PipelineStage.php
│   │   ├── Plan.php
│   │   ├── POItem.php
│   │   ├── Product.php
│   │   ├── ProductWarehouse.php
│   │   ├── Purchase.php
│   │   ├── PurchaseReturn.php
│   │   ├── PurchaseReturnItem.php
│   │   ├── RecurringJournalEntry.php
│   │   ├── Role.php
│   │   ├── Sales.php
│   │   ├── SalesItem.php
│   │   ├── SalesOpportunity.php
│   │   ├── SalesOpportunityItem.php
│   │   ├── SalesPipeline.php
│   │   ├── SalesReturn.php
│   │   ├── SalesReturnItem.php
│   │   ├── Quotation.php
│   │   ├── StockAdjustment.php
│   │   ├── StockOpname.php
│   │   ├── StockOpnameItem.php
│   │   ├── SubscriptionOrder.php
│   │   ├── SuperAdmin.php
│   │   ├── Supplier.php
│   │   ├── SupplierInteraction.php
│   │   ├── Tax.php
│   │   ├── Tenant.php
│   │   ├── Transaction.php
│   │   ├── Unit.php
│   │   ├── User.php
│   │   ├── Warehouse.php
│   │   └── Concerns/BelongsToTenant.php
│   │
│   ├── Observers/
│   │   ├── ActivityObserver.php
│   │   └── CustomerCreditObserver.php
│   │
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   ├── AuthServiceProvider.php
│   │   ├── FortifyServiceProvider.php
│   │   ├── NavigationServiceProvider.php
│   │   └── TestingServiceProvider.php
│   │
│   ├── Scopes/
│   │   └── TenantScope.php
│   │
│   ├── Services/
│   │   ├── AccountingAuditService.php
│   │   ├── AccountingExportService.php
│   │   ├── AccountingReportService.php
│   │   ├── AccountingService.php
│   │   ├── AccountService.php
│   │   ├── ActivityLogService.php
│   │   ├── AgingReportService.php
│   │   ├── AttendanceService.php
│   │   ├── BillingService.php
│   │   ├── CashFlowService.php
│   │   ├── CategoryService.php
│   │   ├── ChartOfAccountsService.php
│   │   ├── CrmService.php
│   │   ├── CurrencyService.php
│   │   ├── CustomerService.php
│   │   ├── DashboardService.php
│   │   ├── EFakturService.php              ← NEW (July 2026)
│   │   ├── EmployeeService.php
│   │   ├── FinancialReportingService.php
│   │   ├── GeneralLedgerService.php
│   │   ├── ManualJournalEntryService.php
│   │   ├── NotificationService.php
│   │   ├── OpeningBalanceService.php
│   │   ├── PayrollService.php
│   │   ├── PlanService.php
│   │   ├── PosService.php
│   │   ├── ProductService.php
│   │   ├── ProfileService.php
│   │   ├── PurchaseReturnService.php
│   │   ├── PurchaseService.php
│   │   ├── ReportExporter.php
│   │   ├── SalesForecastService.php
│   │   ├── SalesPipelineService.php
│   │   ├── SalesReturnService.php
│   │   ├── SalesService.php
│   │   ├── SecurityLogger.php
│   │   ├── StockOpnameService.php
│   │   ├── SupplierService.php
│   │   ├── SystemNotificationService.php
│   │   ├── TaxService.php
│   │   ├── TenantSetupService.php
│   │   ├── TransactionService.php
│   │   ├── TutorialService.php
│   │   ├── UnitService.php
│   │   ├── UserService.php
│   │   └── WarehouseService.php
│   │
│   └── Traits/
│       └── HandlesApiErrors.php
│
├── bootstrap/
│   ├── app.php
│   └── providers.php
│
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── backup.php
│   ├── cors.php
│   ├── database.php
│   ├── filesystems.php
│   ├── fortify.php
│   ├── logging.php
│   ├── mail.php
│   ├── midtrans.php
│   ├── multitenancy.php
│   ├── navigation.php
│   ├── permission.php
│   ├── plans.php
│   ├── sanctum.php
│   ├── scribe.php
│   ├── services.php
│   ├── session.php
│   └── turbo-laravel.php
│
├── database/
│   ├── factories/                   # 30+ model factories
│   │
│   ├── migrations/
│   │   ├── landlord/
│   │   │   └── 2026_01_08_070136_create_landlord_tenants_table.php
│   │   │
│   │   ├── 0000_01_01_000000_create_tenants_table.php
│   │   ├── 0001_01_01_000001_create_users_table.php
│   │   ├── ... (100+ tenant migrations)
│   │   ├── 2026_05_07_081251_add_hr_feature_to_plans_table.php
│   │   ├── 2026_05_12_035444_add_allowances_deductions_to_employees_table.php
│   │   ├── 2026_05_12_045741_add_working_days_to_attendance_periods_table.php
│   │   ├── 2026_05_25_000001_add_cash_flow_feature_to_plans_table.php
│   │   ├── 2026_07_06_000001_create_platform_currency_settings_table.php
│   │   ├── 2026_07_06_000002_add_currency_code_to_plans_table.php
│   │   ├── 2026_07_06_000003_add_currency_code_to_subscription_orders_table.php
│   │   ├── 2026_07_17_000001_create_faktur_pajak_table.php              ← NEW
│   │   ├── 2026_07_17_000002_create_faktur_pajak_items_table.php        ← NEW
│   │   ├── 2026_07_17_000003_create_nsfp_pool_table.php                 ← NEW
│   │   ├── 2026_07_17_000004_add_ppn_fields_to_tenants_customers_sales.php ← NEW
│   │   ├── 2026_07_17_000005_add_ppn_feature_to_plans.php               ← NEW
│   │   ├── 2026_07_25_000001_backfill_ppn_keluaran_account_setting.php  ← NEW
│   │   └── 2026_07_25_000002_add_tax_jurisdiction_to_tenants_and_is_ppn_to_taxes.php ← NEW
│   │
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── PlanSeeder.php
│       ├── PermissionSeeder.php
│       ├── RoleSeeder.php / RolePermissionSeeder.php
│       ├── AccountSeeder.php
│       ├── UserSeeder.php / CustomerSeeder.php / SupplierSeeder.php
│       ├── ProductSeeder.php / CategorySeeder.php / UnitSeeder.php
│       ├── SalesSeeder.php / PurchaseSeeder.php
│       ├── EmployeeSeeder.php / AttendanceSeeder.php / PayrollSeeder.php
│       ├── StockOpnameSeeder.php / StockTransferSeeder.php
│       ├── SalesReturnSeeder.php / PurchaseReturnSeeder.php
│       └── (36 seeders total)
│
├── docker/
│   ├── nginx.conf
│   └── start.sh
│
├── lang/
│   ├── en/
│   │   ├── accounting.php
│   │   ├── messages.php
│   │   ├── plan.php
│   │   └── tutorial.php
│   ├── id/
│   │   ├── accounting.php
│   │   ├── messages.php
│   │   ├── plan.php
│   │   └── tutorial.php
│   └── vendor/backup/
│       ├── en/notifications.php
│       ├── id/notifications.php
│       └── ko/notifications.php
│
├── public/
│   ├── .htaccess
│   ├── index.php
│   ├── audio/                      # POS sound effects (8 files)
│   ├── build/assets/               # Vite build output (JS/CSS)
│   ├── docs/                       # Scribe API docs
│   ├── images/
│   ├── img/default_placeholder.png
│   └── tabler/dist/                # Tabler CSS framework
│
├── resources/
│   ├── css/                        # Page-specific CSS files
│   │   ├── app.css, auth.css, pos.css, print-a4.css
│   │   ├── dashboard/, hr/, settings/ styles
│   │   └── (20+ CSS files)
│   │
│   ├── images/background.jpeg
│   │
│   ├── js/
│   │   ├── app.js, bootstrap.js
│   │   ├── admin/
│   │   │   ├── sales-order.js, purchase-order.js, pos.js
│   │   │   ├── accounting.js, customer.js, supplier.js, product.js
│   │   │   ├── hr.js, stock-opname.js, sales-pipeline.js
│   │   │   ├── (20+ module JS files)
│   │   │   ├── helpers/
│   │   │   │   └── notification.js
│   │   │   ├── layouts/
│   │   │   │   └── (navbar, sidebar, theme-toggle, tutorial-modal, etc.)
│   │   │   ├── partials/           # Per-feature JS modules
│   │   │   │   ├── sales-order/{bulkActions,common,create,edit,view}/
│   │   │   │   ├── purchase-order/{bulkActions,common,create,edit,view}/
│   │   │   │   ├── sales-pipeline/{common,data,forms,modals,opportunities,ui}/
│   │   │   │   ├── product/{bulkActions,modals,search}/
│   │   │   │   ├── customer/{crmModal,editModal,utils}/
│   │   │   │   ├── supplier/{editModal,search,srmModal,utils}/
│   │   │   │   ├── pos/{cart,payment,quickCreate,utils}/
│   │   │   │   ├── user/{common,create,delete,edit,rolesPermissions}/
│   │   │   │   ├── warehouse/{common,create,edit,search}/
│   │   │   │   ├── recentts/{bulkActions,modals,utils}/
│   │   │   │   └── (50+ partial JS files)
│   │   │   └── utils/
│   │   │       ├── formValidator.js       # Global form validation (novalidate + data-validate-form)
│   │   │       ├── midtrans.js
│   │   │       ├── onPageLoad.js
│   │   │       └── sound.js
│   │   └── utils/
│   │       ├── currencyFormatter.js
│   │       └── sanitize.js
│   │
│   └── views/
│       ├── admin/
│       │   ├── dashboard.blade.php
│       │   ├── notifications.blade.php
│       │   │
│       │   ├── efaktur/                        ← NEW (July 2026)
│       │   │   ├── index.blade.php             # Period filter, summary cards, faktur table
│       │   │   ├── show.blade.php              # Faktur Pajak detail view
│       │   │   ├── settings.blade.php          # PKP/NPWP settings, NSFP import
│       │   │   └── faktur-pdf.blade.php        # DJP-compliant A4 PDF
│       │   │
│       │   ├── accounting/
│       │   │   ├── chart-of-accounts.blade.php
│       │   │   ├── journal.blade.php
│       │   │   ├── general-ledger.blade.php
│       │   │   ├── trial-balance.blade.php
│       │   │   ├── accounting-setting.blade.php
│       │   │   ├── accounts/{create,edit,index}.blade.php
│       │   │   ├── journal-entries/{create,edit,index,show}.blade.php
│       │   │   └── opening-balance/index.blade.php
│       │   │
│       │   ├── audit-log/                     # Audit Log UI (superuser-only)
│       │   │   └── index.blade.php
│       │   │
│       │   ├── auth/   (login, register, forgot-password, etc.)
│       │   ├── category/index.blade.php
│       │   ├── currency/currency-edit.blade.php
│       │   ├── customer/index.blade.php
│       │   ├── supplier/index.blade.php
│       │   │
│       │   ├── hr/
│       │   │   ├── employee/{form,index}.blade.php
│       │   │   ├── attendance/{index,show,export-pdf,export-period-pdf}.blade.php
│       │   │   ├── payroll/{index,show,export-pdf,export-run-pdf}.blade.php
│       │   │   └── payslip/print.blade.php
│       │   │
│       │   ├── layouts/
│       │   │   ├── base.blade.php, authbase.blade.php, errorbase.blade.php
│       │   │   ├── navbar.blade.php, menu.blade.php, menu-sidebar.blade.php
│       │   │   ├── footer.blade.php, script.blade.php
│       │   │   └── modals/          # All modal dialogs
│       │   │       ├── sales/salesmodals.blade.php
│       │   │       ├── po/pomodals.blade.php
│       │   │       ├── product/productmodals.blade.php
│       │   │       ├── customer/custmodals.blade.php
│       │   │       ├── supplier/suppmodals.blade.php
│       │   │       ├── users/usersmodals.blade.php
│       │   │       ├── warehouse/waremodals.blade.php
│       │   │       ├── audit-log/  category/  crm/  currency/
│       │   │       ├── journal-entries/  quotations/  recentts/
│       │   │       ├── stock-opname/  tax/  unit/  profile/
│       │   │       ├── general/ (tutorial, keyboard-shortcuts, notifications)
│       │   │       └── (21 modal subdirectories, 30+ modal files)
│       │   │
│       │   ├── layouts/partials/    # Reusable blade partials
│       │   │   ├── sales/{create,edit,index,view}/   (50+ partials)
│       │   │   ├── po/{create,edit,index,view}/      (55+ partials)
│       │   │   ├── product/{create,edit,index}/       (30+ partials)
│       │   │   ├── dashboard/                         (20+ partials)
│       │   │   ├── warehouse/index/                   (8 partials)
│       │   │   ├── accounts/  audit-log/  journal-entries/  notification/
│       │   │   ├── por/  pos/  profile/  sales-returns/
│       │   │   ├── error-actions.blade.php  global-loader.blade.php
│       │   │   ├── impersonation-banner.blade.php  session-notifications.blade.php
│       │   │   ├── trial-banner.blade.php
│       │   │   └── (14 partial subdirectories, 120+ total partials)
│       │   │
│       │   ├── plan/{index,upgrade,checkout,upgrade-required}.blade.php
│       │   ├── po/{index,show,purchase-create,purchase-edit}.blade.php
│       │   ├── por/{index,show,create,edit}.blade.php  # Purchase Returns
│       │   ├── pos/{index,receipt,print}.blade.php
│       │   ├── product/{index,product-create,product-edit}.blade.php
│       │   ├── profile/profile-edit.blade.php
│       │   ├── reports/  (income-statement, balance-sheet, cash-flow, aged-*, etc.)
│       │   ├── sales/{index,show,sales-create,sales-edit,pipeline}.blade.php
│       │   ├── sales-returns/{index,show,create,edit}.blade.php
│       │   ├── quotations/{index,show,create,edit}.blade.php
│       │   ├── settings/{settings,backup}.blade.php  # Tax Region card on settings
│       │   ├── stock-opname/{index,create,show}.blade.php
│       │   ├── tax/tax.blade.php
│       │   ├── unit/index.blade.php
│       │   ├── users/index.blade.php
│       │   └── warehouse/{index,export-pdf}.blade.php
│       │
│       ├── emails/subscription-invoice.blade.php
│       ├── errors/   (200, 302, 400, 401, 403, 404, 419, 429, 500)
│       ├── scribe/index.blade.php
│       └── vendor/   (pagination, turbo-laravel, notifications)
│
├── routes/
│   ├── api.php
│   ├── admin/                          # Modular admin route groups (15 files)
│   │   ├── accounting.php
│   │   ├── auth.php
│   │   ├── contacts.php
│   │   ├── crm.php
│   │   ├── dashboard.php
│   │   ├── efaktur.php                 ← NEW (July 2026) — e-Faktur routes
│   │   ├── hr.php
│   │   ├── inventory.php
│   │   ├── notifications.php
│   │   ├── products.php
│   │   ├── purchases.php
│   │   ├── quotations.php
│   │   ├── reports.php
│   │   ├── sales.php
│   │   └── settings.php
│   ├── console.php
│   ├── turbo.php
│   └── web.php                     # Main route file (admin + auth)
│
└── tests/
    ├── TestCase.php
    ├── Traits/CreatesTenant.php
    │
    ├── Feature/
    │   ├── HrFeatureTest.php
    │   ├── MidtransWebhookTest.php
    │   ├── MultiTenancyTest.php
    │   └── Admin/
    │       ├── SalesControllerTest.php
    │       ├── PurchaseControllerTest.php
    │       ├── POSControllerTest.php
    │       ├── ProductControllerTest.php
    │       ├── CustomerControllerTest.php
    │       ├── SupplierControllerTest.php
    │       ├── AccountingControllerTest.php
    │       ├── NotificationControllerTest.php
    │       ├── PrintTest.php
    │       ├── SalesReturnControllerTest.php
    │       ├── PurchaseReturnControllerTest.php
│       ├── SalesPipelineControllerTest.php
│       ├── StockOpnameControllerTest.php
│       ├── ReportControllerTest.php
│       ├── BackupControllerTest.php
│       ├── AuditLogControllerTest.php
│       ├── (25+ controller test files)
│   ├── Api/TenantLookupControllerTest.php
│   └── Auth/
│       ├── AuthenticationTest.php
│       ├── EmailVerificationTest.php
│       ├── PasswordConfirmationTest.php
│       ├── PasswordResetTest.php
│       └── PasswordUpdateTest.php
│
│   └── Unit/
│       ├── DTOs/TransactionDTOTest.php
│       ├── Helpers/   (Currency, Product, Purchase, Sales helpers)
│       ├── Http/Middleware/SetLocaleTest.php
│       ├── Http/Requests/ProfileUpdateRequestTest.php
│       ├── Models/ProductStockTest.php
│       └── Services/   (CashFlowServiceTest + 20+ service tests)
│
    └── js/                       # Vitest frontend tests (169 files)
        ├── app.test.js, auth.test.js, category.test.js, currency.test.js
        ├── customer*.test.js     # customer, customer-crm-{api,main,state,ui}, customer-edit-*
        ├── helpers-notification.test.js
        ├── layouts-*.test.js     # navbar, sidebar, theme-*, settings, tutorial-modal, etc.
        ├── partials-*.test.js    # per-module partial specs (50+)
        ├── pos-*.test.js         # cart, payment, quickCreate
        └── (169 Vitest test files total)
```
