# Project Structure: invent-mag

```
invent-mag/
├── .dockerignore
├── .editorconfig
├── .env
├── .env.example
├── .gitattributes
├── .gitignore
├── .phpunit.result.cache
├── Dockerfile
├── LICENSE
├── README.md
├── artisan
├── composer.json
├── composer.lock
├── docker-compose.yml
├── generate_tree.cjs
├── graph.png
├── package-lock.json
├── package.json
├── phpunit.xml
├── postcss.config.js
├── screenshot.png
├── tailwind.config.js
├── vite.config.js
├── docs/
│   ├── ACCOUNTING_SETUP_GUIDE.md
│   ├── ACTUAL_TREE.txt
│   ├── ARCHITECTURE_ASSESSMENT.md
│   ├── DEPLOYMENT_CHECKLIST.md
│   ├── FEATURE_CHECKLIST.md
│   ├── INTERVIEW_QA.md
│   ├── LANDING_PAGE_CMS.md
│   ├── OWNER_SETUP_GUIDE.md
│   ├── PPN_EVOLUTION.md
│   ├── ROADMAP.md
│   ├── STRUCTURE.md
│   ├── TAXATION_IMPROVEMENT.md
│   ├── TAXATION_SYSTEM.md
│   └── TECHNICAL_SPECIFICATION.md
├── app/
│   ├── Actions
│   │   └── Fortify
│   │       ├── CreateNewUser.php
│   │       ├── PasswordValidationRules.php
│   │       ├── ResetUserPassword.php
│   │       ├── UpdateUserPassword.php
│   │       └── UpdateUserProfileInformation.php
│   ├── Console
│   │   └── Commands
│   │       ├── BackupTenant.php
│   │       ├── CheckExpiredSubscriptions.php
│   │       ├── ProcessScheduledPlanChanges.php
│   │       └── ResetDemoTenant.php
│   ├── DTOs
│   │   └── TransactionDTO.php
│   ├── Exceptions
│   │   └── PlanLimitExceededException.php
│   ├── Helpers
│   │   ├── CurrencyHelper.php
│   │   ├── ProductHelper.php
│   │   ├── PurchaseHelper.php
│   │   ├── PurchaseReturnHelper.php
│   │   ├── SalesHelper.php
│   │   └── SalesReturnHelper.php
│   ├── Http
│   │   ├── Controllers
│   │   │   ├── Admin
│   │   │   │   ├── Hr
│   │   │   │   │   ├── AttendanceController.php
│   │   │   │   │   ├── EmployeeController.php
│   │   │   │   │   ├── PayrollController.php
│   │   │   │   │   └── PayslipController.php
│   │   │   │   ├── AccountingController.php
│   │   │   │   ├── AuditLogController.php
│   │   │   │   ├── BackupController.php
│   │   │   │   ├── BankReconciliationController.php
│   │   │   │   ├── CategoryController.php
│   │   │   │   ├── CurrencyController.php
│   │   │   │   ├── CustomerController.php
│   │   │   │   ├── CustomerCrmController.php
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── EFakturController.php
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
│   │   │   │   └── WarehouseController.php
│   │   │   ├── Api
│   │   │   │   ├── Auth
│   │   │   │   │   ├── AuthController.php
│   │   │   │   │   └── RegisteredUserController.php
│   │   │   │   ├── V1
│   │   │   │   ├── Webhook
│   │   │   │   │   └── MidtransController.php
│   │   │   │   ├── CmsController.php
│   │   │   │   ├── ContactController.php
│   │   │   │   ├── PaymentController.php
│   │   │   │   ├── PlanController.php
│   │   │   │   └── TenantLookupController.php
│   │   │   ├── Auth
│   │   │   │   └── LoginController.php
│   │   │   ├── SuperAdmin
│   │   │   │   ├── Auth
│   │   │   │   │   └── LoginController.php
│   │   │   │   ├── AuditLogController.php
│   │   │   │   ├── BackupController.php
│   │   │   │   ├── BillingController.php
│   │   │   │   ├── CmsController.php
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── FeatureController.php
│   │   │   │   ├── InvoiceController.php
│   │   │   │   ├── PaymentOverviewController.php
│   │   │   │   ├── PendingPaymentController.php
│   │   │   │   ├── PermissionController.php
│   │   │   │   ├── PlanController.php
│   │   │   │   ├── SettingController.php
│   │   │   │   ├── SubscriptionController.php
│   │   │   │   ├── TenantController.php
│   │   │   │   └── TransactionController.php
│   │   │   └── Controller.php
│   │   ├── Middleware
│   │   │   ├── CaptureImpersonation.php
│   │   │   ├── CheckPlanFeature.php
│   │   │   ├── CheckPlanLimit.php
│   │   │   ├── CheckSubscriptionStatus.php
│   │   │   ├── DebugModeMiddleware.php
│   │   │   ├── HandleTenantFromPath.php
│   │   │   ├── RedirectIfAuthenticated.php
│   │   │   ├── SecurityHeaders.php
│   │   │   ├── SetLocale.php
│   │   │   └── SetSessionCookieName.php
│   │   ├── Requests
│   │   │   ├── Api
│   │   │   │   └── V1
│   │   │   ├── Auth
│   │   │   │   └── LoginRequest.php
│   │   │   └── ProfileUpdateRequest.php
│   │   ├── Resources
│   │   │   ├── AccountResource.php
│   │   │   ├── CategoryResource.php
│   │   │   ├── CurrencySettingResource.php
│   │   │   ├── CustomerInteractionResource.php
│   │   │   ├── CustomerResource.php
│   │   │   ├── JournalEntryResource.php
│   │   │   ├── PaymentResource.php
│   │   │   ├── PipelineStageResource.php
│   │   │   ├── POItemResource.php
│   │   │   ├── POSResource.php
│   │   │   ├── ProductResource.php
│   │   │   ├── PurchaseResource.php
│   │   │   ├── RoleResource.php
│   │   │   ├── SalesItemResource.php
│   │   │   ├── SalesOpportunityItemResource.php
│   │   │   ├── SalesOpportunityResource.php
│   │   │   ├── SalesPipelineResource.php
│   │   │   ├── SalesResource.php
│   │   │   ├── StockAdjustmentResource.php
│   │   │   ├── SupplierInteractionResource.php
│   │   │   ├── SupplierResource.php
│   │   │   ├── TaxResource.php
│   │   │   ├── TransactionResource.php
│   │   │   ├── UnitResource.php
│   │   │   ├── UserResource.php
│   │   │   └── WarehouseResource.php
│   │   └── Responses
│   │       ├── CustomLoginResponse.php
│   │       ├── CustomRegisterResponse.php
│   │       ├── LogoutResponse.php
│   │       ├── PasswordResetResponse.php
│   │       └── VerifyEmailResponse.php
│   ├── Mail
│   │   ├── ContactFormMail.php
│   │   └── SubscriptionInvoiceMail.php
│   ├── Models
│   │   ├── Concerns
│   │   │   └── BelongsToTenant.php
│   │   ├── Models
│   │   ├── Account.php
│   │   ├── AccountingAuditLog.php
│   │   ├── ActivityLog.php
│   │   ├── AttendancePeriod.php
│   │   ├── AttendanceRecord.php
│   │   ├── BankReconciliation.php
│   │   ├── BankReconciliationItem.php
│   │   ├── BillingSetting.php
│   │   ├── Categories.php
│   │   ├── CurrencySetting.php
│   │   ├── Customer.php
│   │   ├── CustomerCredit.php
│   │   ├── CustomerInteraction.php
│   │   ├── Employee.php
│   │   ├── FakturPajak.php
│   │   ├── FakturPajakItem.php
│   │   ├── JournalEntry.php
│   │   ├── NsfpPool.php
│   │   ├── OpeningBalance.php
│   │   ├── Payment.php
│   │   ├── PayrollItem.php
│   │   ├── PayrollRun.php
│   │   ├── PipelineStage.php
│   │   ├── Plan.php
│   │   ├── PlatformCurrencySetting.php
│   │   ├── POItem.php
│   │   ├── Product.php
│   │   ├── ProductWarehouse.php
│   │   ├── Purchase.php
│   │   ├── PurchaseReturn.php
│   │   ├── PurchaseReturnItem.php
│   │   ├── Quotation.php
│   │   ├── RecurringJournalEntry.php
│   │   ├── Role.php
│   │   ├── Sales.php
│   │   ├── SalesItem.php
│   │   ├── SalesOpportunity.php
│   │   ├── SalesOpportunityItem.php
│   │   ├── SalesPipeline.php
│   │   ├── SalesReturn.php
│   │   ├── SalesReturnItem.php
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
│   │   └── Warehouse.php
│   ├── Multitenancy
│   ├── Observers
│   │   ├── ActivityObserver.php
│   │   └── CustomerCreditObserver.php
│   ├── Providers
│   │   ├── AppServiceProvider.php
│   │   ├── AuthServiceProvider.php
│   │   ├── FortifyServiceProvider.php
│   │   ├── NavigationServiceProvider.php
│   │   └── TestingServiceProvider.php
│   ├── Scopes
│   │   └── TenantScope.php
│   ├── Services
│   │   ├── BankStatement
│   │   │   ├── AbstractBankStatementParser.php
│   │   │   ├── BankStatementParser.php
│   │   │   ├── BankStatementParserFactory.php
│   │   │   ├── BcaStatementParser.php
│   │   │   ├── BniStatementParser.php
│   │   │   ├── BriStatementParser.php
│   │   │   └── MandiriStatementParser.php
│   │   ├── AccountingAuditService.php
│   │   ├── AccountingExportService.php
│   │   ├── AccountingReportService.php
│   │   ├── AccountingService.php
│   │   ├── AccountService.php
│   │   ├── ActivityLogService.php
│   │   ├── AgingReportService.php
│   │   ├── AttendanceService.php
│   │   ├── BankReconciliationService.php
│   │   ├── BillingService.php
│   │   ├── CashFlowService.php
│   │   ├── CategoryService.php
│   │   ├── ChartOfAccountsService.php
│   │   ├── CrmService.php
│   │   ├── CurrencyService.php
│   │   ├── CustomerService.php
│   │   ├── DashboardService.php
│   │   ├── EFakturService.php
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
│   └── Traits
│       └── HandlesApiErrors.php
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── backup.php
│   ├── billing.php
│   ├── cache.php
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
│   ├── queue.php
│   ├── sanctum.php
│   ├── scribe.php
│   ├── services.php
│   ├── session.php
│   ├── superadmin-tutorial.php
│   ├── turbo-laravel.php
│   └── tutorial.php
├── routes/
│   ├── admin
│   │   ├── accounting.php
│   │   ├── auth.php
│   │   ├── bank-reconciliation.php
│   │   ├── contacts.php
│   │   ├── crm.php
│   │   ├── dashboard.php
│   │   ├── efaktur.php
│   │   ├── hr.php
│   │   ├── inventory.php
│   │   ├── notifications.php
│   │   ├── products.php
│   │   ├── purchases.php
│   │   ├── quotations.php
│   │   ├── reports.php
│   │   ├── sales.php
│   │   └── settings.php
│   ├── api.php
│   ├── console.php
│   ├── superadmin.php
│   ├── turbo.php
│   └── web.php
├── database/
│   ├── data
│   ├── migrations
│   │   └── [Migrations omitted for brevity...]
│   ├── schema
│   └── .gitignore
└── tests/
    ├── Feature
    │   ├── Admin
    │   │   ├── AccountingControllerTest.php
    │   │   ├── AuditLogControllerTest.php
    │   │   ├── BackupControllerTest.php
    │   │   ├── BankReconciliationControllerTest.php
    │   │   ├── CategoryControllerTest.php
    │   │   ├── CurrencyControllerTest.php
    │   │   ├── CustomerControllerTest.php
    │   │   ├── CustomerCrmControllerTest.php
    │   │   ├── DashboardControllerTest.php
    │   │   ├── EfakturSettingsTest.php
    │   │   ├── JournalEntryControllerTest.php
    │   │   ├── NotificationControllerTest.php
    │   │   ├── OpeningBalanceControllerTest.php
    │   │   ├── PlanControllerTest.php
    │   │   ├── PlanFeatureGateTest.php
    │   │   ├── POSControllerTest.php
    │   │   ├── PrintTest.php
    │   │   ├── ProductControllerTest.php
    │   │   ├── ProductStockControllerTest.php
    │   │   ├── ProfileControllerTest.php
    │   │   ├── PurchaseControllerTest.php
    │   │   ├── PurchasePpnMasukanViewTest.php
    │   │   ├── PurchaseReturnControllerTest.php
    │   │   ├── QuotationControllerTest.php
    │   │   ├── ReportControllerTest.php
    │   │   ├── SalesControllerTest.php
    │   │   ├── SalesPipelineControllerTest.php
    │   │   ├── SalesReturnControllerTest.php
    │   │   ├── SettingsControllerTest.php
    │   │   ├── SettingsTaxJurisdictionTest.php
    │   │   ├── StockOpnameControllerTest.php
    │   │   ├── StockTransferControllerTest.php
    │   │   ├── SupplierControllerTest.php
    │   │   ├── SupplierCrmControllerTest.php
    │   │   ├── TaxControllerTest.php
    │   │   ├── UnitControllerTest.php
    │   │   ├── UserControllerTest.php
    │   │   └── WarehouseControllerTest.php
    │   ├── Api
    │   │   ├── Auth
    │   │   ├── ContactControllerTest.php
    │   │   └── TenantLookupControllerTest.php
    │   ├── Auth
    │   │   ├── AuthenticationTest.php
    │   │   ├── EmailVerificationTest.php
    │   │   ├── PasswordConfirmationTest.php
    │   │   ├── PasswordResetTest.php
    │   │   └── PasswordUpdateTest.php
    │   ├── HrFeatureTest.php
    │   ├── MidtransWebhookTest.php
    │   └── MultiTenancyTest.php
    ├── js
    │   ├── app.test.js
    │   ├── auth.test.js
    │   ├── category.test.js
    │   ├── currency.test.js
    │   ├── customer-crm-api.test.js
    │   ├── customer-crm-main.test.js
    │   ├── customer-crm-state.test.js
    │   ├── customer-crm-ui.test.js
    │   ├── customer-edit-form.test.js
    │   ├── customer-edit-image.test.js
    │   ├── customer-edit-main.test.js
    │   ├── customer.test.js
    │   ├── helpers-notification.test.js
    │   ├── layouts-advanced-settings.test.js
    │   ├── layouts-global-keyboard-shortcuts.test.js
    │   ├── layouts-menu-sidebar.test.js
    │   ├── layouts-navbar.test.js
    │   ├── layouts-page-loader.test.js
    │   ├── layouts-selectable-table.test.js
    │   ├── layouts-settings.test.js
    │   ├── layouts-theme-initializer.test.js
    │   ├── layouts-theme-toggle.test.js
    │   ├── layouts-theme-visibility.test.js
    │   ├── partials-auth-showContentAfterLoading.test.js
    │   ├── partials-auth-togglePasswordVisibility.test.js
    │   ├── partials-category-handleEditCategoryModal.test.js
    │   ├── partials-currency-api.test.js
    │   ├── partials-currency-elements.test.js
    │   ├── partials-currency-form.test.js
    │   ├── partials-currency-modal.test.js
    │   ├── partials-currency-preview.test.js
    │   ├── partials-currency-state.test.js
    │   ├── partials-customer-crmModal-api.test.js
    │   ├── partials-customer-crmModal-main.test.js
    │   ├── partials-customer-crmModal-state.test.js
    │   ├── partials-customer-crmModal-ui.test.js
    │   ├── partials-customer-editModal-form.test.js
    │   ├── partials-customer-editModal-image.test.js
    │   ├── partials-customer-editModal-main.test.js
    │   ├── partials-customer-utils-currency.test.js
    │   ├── partials-customer-utils-date.test.js
    │   ├── partials-customer-utils-dom.test.js
    │   ├── partials-customer-utils-status.test.js
    │   ├── partials-pos-cart-actions.test.js
    │   ├── partials-pos-cart-dom.test.js
    │   ├── partials-pos-cart-state.test.js
    │   ├── partials-pos-cart-totals.test.js
    │   ├── partials-pos-payment-api.test.js
    │   ├── partials-pos-payment-form.test.js
    │   ├── partials-pos-payment-modal.test.js
    │   ├── partials-pos-quickCreate-customer.test.js
    │   ├── partials-pos-quickCreate-product.test.js
    │   ├── partials-pos-utils-currency.test.js
    │   ├── partials-pos-utils-sound.test.js
    │   ├── partials-pos-utils-ui.test.js
    │   ├── partials-product-bulkActions-delete.test.js
    │   ├── partials-product-bulkActions-export.test.js
    │   ├── partials-product-bulkActions-selection.test.js
    │   ├── partials-product-bulkActions-stock.test.js
    │   ├── partials-product-events.test.js
    │   ├── partials-product-main.test.js
    │   ├── partials-product-modals-delete.test.js
    │   ├── partials-product-modals-init.test.js
    │   ├── partials-product-modals-product.test.js
    │   ├── partials-product-search-state.test.js
    │   ├── partials-product-search-table.test.js
    │   ├── partials-product-search-ui.test.js
    │   ├── partials-product-stats.test.js
    │   ├── partials-product-utils-helpers.test.js
    │   ├── partials-product-utils-ui.test.js
    │   ├── partials-profile-form.test.js
    │   ├── partials-profile-modal.test.js
    │   ├── partials-purchase-order-bulkActions-actions.test.js
    │   ├── partials-purchase-order-bulkActions-PurchaseOrderBulkSelection.test.js
    │   ├── partials-purchase-order-common-PurchaseOrderModule.test.js
    │   ├── partials-purchase-order-create-PurchaseOrderCreate.test.js
    │   ├── partials-purchase-order-edit-PurchaseOrderEdit.test.js
    │   ├── partials-purchase-order-view-PurchaseOrderView.test.js
    │   ├── partials-recentts-bulkActions-api.test.js
    │   ├── partials-recentts-bulkActions-selection.test.js
    │   ├── partials-recentts-modals-markAsPaid.test.js
    │   ├── partials-recentts-utils-dom.test.js
    │   ├── partials-recentts-utils-export.test.js
    │   ├── partials-recentts-utils-resetButton.test.js
    │   ├── partials-recentts-utils-search.test.js
    │   ├── partials-recentts-utils-selection.test.js
    │   ├── partials-sales-order-bulkActions-actions.test.js
    │   ├── partials-sales-order-bulkActions-SalesOrderBulkSelection.test.js
    │   ├── partials-sales-order-common-SalesOrderModule.test.js
    │   ├── partials-sales-order-create-SalesOrderCreate.test.js
    │   ├── partials-sales-order-edit-SalesOrderEdit.test.js
    │   ├── partials-sales-order-view-SalesOrderView.test.js
    │   ├── partials-sales-pipeline-common-constants.test.js
    │   ├── partials-sales-pipeline-common-state.test.js
    │   ├── partials-sales-pipeline-common-utils.test.js
    │   ├── partials-sales-pipeline-data-init.test.js
    │   ├── partials-sales-pipeline-forms-convertOpportunity.test.js
    │   ├── partials-sales-pipeline-forms-editOpportunity.test.js
    │   ├── partials-sales-pipeline-forms-editPipeline.test.js
    │   ├── partials-sales-pipeline-forms-newOpportunity.test.js
    │   ├── partials-sales-pipeline-forms-newPipeline.test.js
    │   ├── partials-sales-pipeline-forms-newStage.test.js
    │   ├── partials-sales-pipeline-modals-opportunityItems.test.js
    │   ├── partials-sales-pipeline-opportunities-card.test.js
    │   ├── partials-sales-pipeline-opportunities-dragAndDrop.test.js
    │   ├── partials-sales-pipeline-ui-customerSelect.test.js
    │   ├── partials-sales-pipeline-ui-pipelineBoard.test.js
    │   ├── partials-sales-pipeline-ui-pipelineSelect.test.js
    │   ├── partials-sales-pipeline-ui-pipelinesList.test.js
    │   ├── partials-sales-pipeline-ui-stagesList.test.js
    │   ├── partials-sorting-lis.test.js.js
    │   ├── partials-sorting-search.test.js
    │   ├── partials-supplier-editModal-form.test.js
    │   ├── partials-supplier-editModal-image.test.js
    │   ├── partials-supplier-editModal-main.test.js
    │   ├── partials-supplier-search-state.test.js
    │   ├── partials-supplier-search-table.test.js
    │   ├── partials-supplier-search-ui.test.js
    │   ├── partials-supplier-srmModal-api.test.js
    │   ├── partials-supplier-srmModal-main.test.js
    │   ├── partials-supplier-srmModal-state.test.js
    │   ├── partials-supplier-srmModal-ui.test.js
    │   ├── partials-supplier-utils-currency.test.js
    │   ├── partials-supplier-utils-date.test.js
    │   ├── partials-supplier-utils-dom.test.js
    │   ├── partials-supplier-utils-status.test.js
    │   ├── partials-unit-handleEditUnitModal.test.js
    │   ├── partials-user-common-elements.test.js
    │   ├── partials-user-common-state.test.js
    │   ├── partials-user-create-main.test.js
    │   ├── partials-user-delete-api.test.js
    │   ├── partials-user-delete-main.test.js
    │   ├── partials-user-edit-api.test.js
    │   ├── partials-user-edit-main.test.js
    │   ├── partials-user-rolesPermissions-api.test.js
    │   ├── partials-user-rolesPermissions-sync.test.js
    │   ├── partials-warehouse-common-formSubmission.test.js
    │   ├── partials-warehouse-create-main.test.js
    │   ├── partials-warehouse-edit-main.test.js
    │   ├── partials-warehouse-search-state.test.js
    │   ├── partials-warehouse-search-table.test.js
    │   ├── partials-warehouse-search-ui.test.js
    │   ├── pos-actions.test.js
    │   ├── pos-state.test.js
    │   ├── pos-totals.test.js
    │   ├── pos.test.js
    │   ├── product.test.js
    │   ├── profile.test.js
    │   ├── purchase-order-bulk-actions.test.js
    │   ├── purchase-order-bulk-selection.test.js
    │   ├── purchase-order-common.test.js
    │   ├── purchase-order-create.test.js
    │   ├── purchase-order-edit.test.js
    │   ├── purchase-order-view.test.js
    │   ├── purchase-order.test.js
    │   ├── recentts.test.js
    │   ├── sales-order-bulk-actions.test.js
    │   ├── sales-order-bulk-selection.test.js
    │   ├── sales-order-common.test.js
    │   ├── sales-order-create.test.js
    │   ├── sales-order-edit.test.js
    │   ├── sales-order-view.test.js
    │   ├── sales-order.test.js
    │   ├── sales-pipeline.test.js
    │   ├── setup.js
    │   ├── sorting.test.js
    │   ├── supplier.test.js
    │   ├── tax.test.js
    │   ├── unit.test.js
    │   ├── user.test.js
    │   └── warehouse.test.js
    ├── Traits
    │   └── CreatesTenant.php
    ├── Unit
    │   ├── DTOs
    │   │   └── TransactionDTOTest.php
    │   ├── Helpers
    │   │   ├── CurrencyHelperPlatformTest.php
    │   │   ├── CurrencyHelperTest.php
    │   │   ├── PphHelperTest.php
    │   │   ├── ProductHelperTest.php
    │   │   ├── PurchaseHelperTest.php
    │   │   ├── PurchaseReturnHelperTest.php
    │   │   ├── SalesHelperTest.php
    │   │   └── SalesReturnHelperTest.php
    │   ├── Http
    │   │   ├── Middleware
    │   │   │   └── SetLocaleTest.php
    │   │   └── Requests
    │   │       └── ProfileUpdateRequestTest.php
    │   ├── Models
    │   │   ├── ProductStockTest.php
    │   │   ├── SalesIsPphTest.php
    │   │   ├── TaxIsPphTest.php
    │   │   └── TenantCanDisableIndonesianJurisdictionTest.php
    │   ├── Observers
    │   └── Services
    │       ├── AccountingAuditServiceTest.php
    │       ├── AccountingExportServiceTest.php
    │       ├── AccountingReportServiceTest.php
    │       ├── AccountingServiceTest.php
    │       ├── AccountServiceTest.php
    │       ├── ActivityLogServiceTest.php
    │       ├── AgingReportServiceTest.php
    │       ├── BankStatementParserTest.php
    │       ├── BillingServiceTest.php
    │       ├── CashFlowServiceTest.php
    │       ├── CategoryServiceTest.php
    │       ├── ChartOfAccountsServiceTest.php
    │       ├── CrmServiceTest.php
    │       ├── CurrencyServiceTest.php
    │       ├── CustomerServiceTest.php
    │       ├── DashboardServiceTest.php
    │       ├── EFakturServiceSptMasukanTest.php
    │       ├── FinancialReportingServiceTest.php
    │       ├── GeneralLedgerServiceTest.php
    │       ├── ManualJournalEntryServiceTest.php
    │       ├── NotificationServiceTest.php
    │       ├── OpeningBalanceServiceTest.php
    │       ├── PlanServiceTest.php
    │       ├── PosServiceTest.php
    │       ├── ProductServiceTest.php
    │       ├── ProfileServiceTest.php
    │       ├── PurchaseReturnServiceTest.php
    │       ├── PurchaseServicePpnMasukanTest.php
    │       ├── PurchaseServiceTest.php
    │       ├── ReportExporterTest.php
    │       ├── SalesForecastServiceTest.php
    │       ├── SalesPipelineServiceTest.php
    │       ├── SalesReturnServiceTest.php
    │       ├── SalesServiceTest.php
    │       ├── SecurityLoggerTest.php
    │       ├── StockOpnameServiceTest.php
    │       ├── SupplierServiceTest.php
    │       ├── SystemNotificationServiceTest.php
    │       ├── TaxServiceEnsureIndonesianDefaultsTest.php
    │       ├── TaxServiceTest.php
    │       ├── TenantSetupServiceTest.php
    │       ├── TransactionServiceTest.php
    │       ├── TutorialServiceTest.php
    │       ├── UnitServiceTest.php
    │       ├── UserServiceTest.php
    │       └── WarehouseServiceTest.php
    └── TestCase.php
```
