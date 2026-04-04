@php
    $routeName = Route::currentRouteName();
    $contextTitle = __('Help & Tutorial');
    $contextDesc = __('Need help navigating this page? Here is what you can do.');
    $contextIcon = 'ti-help';
    $contextSteps = [];

    // Contextual logic
    if (Str::startsWith($routeName, 'admin.product')) {
        $contextTitle = __('Products Master Data');
        $contextIcon = 'ti-packages';
        $contextDesc = __('Manage all your sellable and purchasable items, including tracking stock levels across multiple warehouses.');
        $contextSteps = [
            __('<b>Create Product:</b> Use the "Create Product" button to add basic information such as name, code, supplier, category, unit, buying price, and selling price. Warehouse and quantity are assigned separately through stock adjustment features.'),
            __('<b>Warehouse Filter:</b> The dropdown filter adjacent to the Create Product button allows you to view products specific to a particular warehouse location.'),
            __('<b>Stock Status:</b> The "Stock Status" feature displays all products with low stock levels, enabling timely replenishment.'),
            __('<b>Expiry Status:</b> The "Expiry Status" feature provides visibility into expired or soon-to-expire products, essential for managing perishable inventory.'),
            __('<b>Bulk Actions:</b> Select multiple products using the checkboxes to perform Bulk Update (adjust quantities), Bulk Export (PDF/CSV), or Bulk Delete operations.'),
            __('<b>Table Actions:</b> Each row includes an action menu with the following options: View (product details and stock history), Edit (modify basic information), Adjust Stock (modify quantity for a specific warehouse), and Delete (remove the product).'),
            __('<b>Multi-Warehouse Flow:</b> Newly created products are automatically assigned to the Main Warehouse. In this multi-warehouse system, quantity is added through "Adjust Stock" for individual products or "Bulk Update" for multiple products. This applies regardless of the number of warehouses configured.')
        ];
    } elseif (Str::startsWith($routeName, 'admin.warehouse')) {
        $contextTitle = __('Warehouse Management');
        $contextIcon = 'ti-building-warehouse';
        $contextDesc = __('Configure physical or logical locations for storing and tracking inventory across your business operations.');
        $contextSteps = [
            __('<b>Create Warehouses:</b> Multiple warehouses can be established to represent various physical locations (e.g., Main Store, Warehouse A, Branch Office) or logical divisions (e.g., Online Inventory, Consignment Stock).'),
            __('<b>Main Warehouse:</b> Designate one warehouse as the "Main" option, which serves as the default for new products and stock adjustments when no specific warehouse is selected.'),
            __('<b>Stock Tracking:</b> Each warehouse maintains independent stock levels. Product views can be filtered by warehouse to display availability at each location.')
        ];
    } elseif (Str::startsWith($routeName, 'admin.sales')) {
        $contextTitle = __('Sales Operations');
        $contextIcon = 'ti-shopping-cart';
        $contextDesc = __('Manage customer orders, invoicing, payments, returns, and the sales pipeline for business growth.');
        $contextSteps = [
            __('<b>Sales Orders:</b> Create new sales orders by selecting a customer, adding products from inventory, and specifying quantities. The system automatically calculates totals.'),
            __('<b>Sales Pipeline:</b> Track prospects and opportunities through customizable stages (Lead, Quoted, Won, Lost) to visualize the sales funnel and forecast revenue.'),
            __('<b>Payments:</b> Record customer payments against generated invoices, including partial payments, full payments, and payment due date tracking.'),
            __('<b>Sales Returns:</b> Process customer returns through dedicated return orders, which automatically adjust inventory and issue customer credits.'),
            __('<b>Invoices:</b> Generate professional invoices for printing or PDF export, customizable with business logo and terms.')
        ];
    } elseif (Str::startsWith($routeName, 'admin.po')) {
        $contextTitle = __('Purchase Orders');
        $contextIcon = 'ti-truck-delivery';
        $contextDesc = __('Manage stock replenishment from suppliers through purchase order creation and tracking.');
        $contextSteps = [
            __('<b>Create Purchase Order:</b> Initiate a purchase order by selecting a supplier, adding products, specifying quantities, and reviewing the total cost.'),
            __('<b>Supplier Association:</b> Each purchase order is linked to a specific supplier, enabling tracking of purchasing history and maintenance of supplier relationships.'),
            __('<b>Goods Receipt:</b> When orders arrive, goods are received into the selected warehouse, automatically updating stock levels.'),
            __('<b>Budget Management:</b> Review total costs against budget before order confirmation and monitor spending patterns per supplier.')
        ];
    } elseif (Str::startsWith($routeName, 'admin.accounting')) {
        $contextTitle = __('Accounting Module');
        $contextIcon = 'ti-calculator';
        $contextDesc = __('Monitor financial health through journals, the chart of accounts, and financial reports.');
        $contextSteps = [
            __('<b>Chart of Accounts (COA):</b> The foundational structure for categorizing all financial transactions into accounts such as Sales Revenue, Inventory, Cash, and Expenses.'),
            __('<b>Manual Journal Entries:</b> Record adjustments, corrections, or expenses outside regular sales or purchases. Each entry debits and credits accounts to maintain balanced books.'),
            __('<b>Trial Balance:</b> View a summary of all accounts and their balances to verify debits equal credits, identifying errors before report generation.'),
            __('<b>General Ledger:</b> Access detailed transaction history for any account, providing a comprehensive audit trail of all financial activities.'),
            __('<b>Financial Reports:</b> Generate reports including Income Statements (Profit & Loss) and Balance Sheets to analyze business profitability and financial position.')
        ];
    } elseif (Str::startsWith($routeName, 'admin.setting.index')) {
        $contextTitle = __('System Settings');
        $contextIcon = 'ti-settings';
        $contextDesc = __('Configure global preferences that determine workspace appearance and behavior.');
        $contextSteps = [
            __('<b>Layout Options:</b> Switch between Sidebar and Navbar layouts. The sidebar presents all modules in a collapsible menu, while the navbar displays them in a top menu bar.'),
            __('<b>Notification Settings:</b> Configure notification display duration and enable or disable visual animation effects throughout the application.'),
            __('<b>Language & Security:</b> Set the preferred system language for translations. Configure auto-logout timers to automatically sign out inactive users after a specified period.')
        ];
    } elseif (Str::startsWith($routeName, 'admin.setting.unit')) {
        $contextTitle = __('Unit Management');
        $contextIcon = 'ti-ruler-2';
        $contextDesc = __('Define units of measurement for products to ensure consistent quantity tracking.');
        $contextSteps = [
            __('<b>Create Units:</b> Add measurement units such as Pieces (pcs), Boxes, Kilograms (kg), Liters (L), Meters (m), or other relevant units.'),
            __('<b>Unit Symbols:</b> Use clear, internationally recognized symbols (e.g., "kg" for kilogram, "L" for liter) which appear on invoices and reports.'),
            __('<b>Base Units:</b> Select a base unit when creating products. This is essential for accurate product setup and quantity calculations.')
        ];
    } elseif (Str::startsWith($routeName, 'admin.setting.category')) {
        $contextTitle = __('Product Categories');
        $contextIcon = 'ti-tags';
        $contextDesc = __('Organize products into structured groups for streamlined filtering, reporting, and management.');
        $contextSteps = [
            __('<b>Create Categories:</b> Establish meaningful product groups such as Electronics, Apparel, Office Supplies, Food & Beverages, or Services.'),
            __('<b>Filtering:</b> Categories enable efficient filtering of the product inventory, allowing quick access to items within specific groups.'),
            __('<b>Reporting:</b> Sales reports, income statements, and stock reports can be grouped by category to analyze product type performance.')
        ];
    } elseif (Str::startsWith($routeName, 'admin.setting.tax')) {
        $contextTitle = __('Tax Configuration');
        $contextIcon = 'ti-receipt-tax';
        $contextDesc = __('Configure global tax rules and rates applied to all sales and purchases.');
        $contextSteps = [
            __('<b>Define Tax Rates:</b> Create primary tax types (e.g., VAT, GST, Sales Tax) and set percentage rates (such as 10%, 15%, or 20%).'),
            __('<b>Global Application:</b> Enable or disable tax calculations system-wide. When enabled, tax is automatically applied to invoices and purchase orders.'),
            __('<b>Multiple Taxes:</b> Configure multiple tax rates for different product types or regions as required by your business.')
        ];
    } elseif (Str::startsWith($routeName, 'admin.setting.currency')) {
        $contextTitle = __('Currency Settings');
        $contextIcon = 'ti-coin';
        $contextDesc = __('Configure currency display formatting throughout the system.');
        $contextSteps = [
            __('<b>Primary Currency:</b> Select the main business currency (e.g., USD, EUR, GBP). All financial reports and transactions utilize this currency.'),
            __('<b>Formatting:</b> Choose currency symbol position (before or after amount, e.g., $100 or 100$) and set decimal places (0, 2, or 3).'),
            __('<b>Thousands Separator:</b> Configure number formatting (e.g., 1,000.00 vs 1.000,00) for improved readability in invoices and reports.')
        ];
    } elseif (Str::startsWith($routeName, 'admin.setting.profile')) {
        $contextTitle = __('My Profile');
        $contextIcon = 'ti-user-circle';
        $contextDesc = __('Manage personal account details, security settings, and API access.');
        $contextSteps = [
            __('<b>Account Information:</b> Update name, email address, and profile avatar. This information appears on the account and in activity logs.'),
            __('<b>Password Management:</b> Regularly update the account password for security. Use a strong password incorporating letters, numbers, and symbols.'),
            __('<b>API Tokens:</b> Generate personal API tokens for integration with external applications or programmatic data access.')
        ];
    } elseif (Str::startsWith($routeName, 'admin.setting.plan')) {
        $contextTitle = __('Subscription Plan');
        $contextIcon = 'ti-crown';
        $contextDesc = __('Monitor workspace plan limits, billing status, and available premium features.');
        $contextSteps = [
            __('<b>Current Usage:</b> Review plan limit consumption (e.g., number of products, users, warehouses) to avoid reaching limits.'),
            __('<b>Upgrade Options:</b> Explore available plans with expanded features for business growth and increased capacity needs.'),
            __('<b>Active Features:</b> View currently enabled premium modules and features based on the subscription tier.')
        ];
    } elseif (Str::startsWith($routeName, 'admin.setting.accounting')) {
        $contextTitle = __('Accounting Setup');
        $contextIcon = 'ti-calculator';
        $contextDesc = __('Map business operations to appropriate accounts in the Chart of Accounts for accurate financial tracking.');
        $contextSteps = [
            __('<b>Default Accounts:</b> Link key accounts for Sales Revenue, Cost of Goods Sold (COGS), and other essential transaction types.'),
            __('<b>Cash & Receivables:</b> Configure primary Cash account and Accounts Receivable ledger to track customer payments.'),
            __('<b>Reset Option:</b> The danger zone allows resetting the Chart of Accounts to system defaults. Note: This removes all custom account mappings.')
        ];
    } elseif (Str::startsWith($routeName, 'admin.audit-log')) {
        $contextTitle = __('Audit Logs');
        $contextIcon = 'ti-shield-lock';
        $contextDesc = __('Monitor system changes and user activity for security and compliance purposes.');
        $contextSteps = [
            __('<b>Activity Timeline:</b> View a chronological record of all system actions, including who performed each action, when, and what was affected.'),
            __('<b>Filter by Type:</b> Filter logs by action type (e.g., Created, Updated, Deleted, Login) to locate specific activity categories.'),
            __('<b>Filter by User:</b> View all activities performed by a specific user for individual behavior monitoring.'),
            __('<b>Security & Compliance:</b> Utilize audit logs for issue investigation, unauthorized change detection, and compliance demonstration during audits.')
        ];
    } elseif (Str::startsWith($routeName, 'admin.users')) {
        $contextTitle = __('User Management');
        $contextIcon = 'ti-users';
        $contextDesc = __('Control workspace access and manage user permission levels.');
        $contextSteps = [
            __('<b>Invite Users:</b> Add new staff members through email invitations to join the workspace.'),
            __('<b>Assign Roles:</b> Assign specific roles: POS (retail staff), Staff (general access), Superuser (full admin access). Each role carries different permissions.'),
            __('<b>Permissions:</b> Manage granular permissions to restrict access to sensitive areas including accounting settings, user management, or critical data deletion.'),
            __('<b>Deactivate Users:</b> Deactivate accounts when staff leave to revoke access while preserving historical activity in audit logs.')
        ];
    } elseif ($routeName === 'admin.notifications' || $routeName === 'admin.setting.notifications') {
        $contextTitle = __('My Notifications');
        $contextIcon = 'ti-bell';
        $contextDesc = __('Stay informed about system alerts, important updates, and recent activity.');
        $contextSteps = [
            __('<b>All Notifications:</b> Access all notifications in one location, including low stock alerts, order updates, system announcements, and user activity.'),
            __('<b>Read Details:</b> Click on any notification to view full details and any required actions.'),
            __('<b>Mark as Read:</b> Use the checkmark button to mark individual notifications as read, or "Clear All" to remove all notifications.'),
            __('<b>Notification Settings:</b> Configure notification types and delivery preferences (in-app, email).')
        ];
    } else {
        // Generic Help
        $contextSteps = [
            __('<b>Navigation:</b> Use the sidebar menu on the left to navigate between different modules such as Products, Sales, Purchases, and Accounting.'),
            __('<b>Settings:</b> Configure base settings through the Settings menu, including Units, Categories, Taxes, and Currencies.'),
            __('<b>Creating Data:</b> Look for "Create", "Add", or "+" buttons typically positioned at the top right of list pages to add new data.'),
            __('<b>Search & Filter:</b> Most pages include search boxes and filters for quick record location.'),
            __('<b>Actions:</b> Click on any record to view details, use the action menu to edit, or access additional options.')
        ];
    }
@endphp

<div class="modal modal-blur fade" id="tutorial-modal" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static" data-completed="{{ auth()->check() && auth()->user()->tutorial_completed ? 'true' : 'false' }}" data-route="{{ Route::currentRouteName() }}">
    <div class="modal-dialog modal-dialog-centered modal-lg tutorial-modal-dialog" role="document">
        <div class="modal-content shadow-lg border-0" style="border-radius: 20px; overflow: hidden;">

            <!-- Header with Gradient -->
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #1e40af, #3b82f6); border-bottom: none; padding: 20px 24px;">
                <h5 class="modal-title fw-bold fs-3" id="tutorial-modal-title">
                    <i class="ti ti-rocket me-2"></i> {{ __('Welcome to Invent-MAG') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="closeTutorialExplicitly()"></button>
            </div>

            <!-- STEP 1: Workflow Overview (For First Time Login) -->
            <div class="modal-body py-5 px-4 px-md-5 tutorial-step" id="tutorial-step-1">
                <div class="text-center mb-5">
                    <h2 class="fw-bold text-dark mb-3">{{ __('Your Complete Inventory & Accounting Solution') }}</h2>
                    <p class="text-secondary fs-4">
                        {{ __('To unlock the full potential of the app, follow this recommended setup flow:') }}
                    </p>
                </div>

                <div class="row">
                    <div class="col-md-10 offset-md-1">

                        <div class="tutorial-list-item">
                            <div class="tutorial-icon-box bg-primary-lt text-primary me-4 shadow-sm">
                                <i class="ti ti-settings"></i>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold fs-4">1. Configuration</h3>
                                <p class="text-secondary mb-0">Set up your base parameters: <strong>Units, Categories, Currencies, and Taxes</strong>.</p>
                            </div>
                        </div>

                        <div class="tutorial-list-item">
                            <div class="tutorial-icon-box bg-success-lt text-success me-4 shadow-sm">
                                <i class="ti ti-database"></i>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold fs-4">2. Master Data</h3>
                                <p class="text-secondary mb-0">Input your core entities: <strong>Warehouses, Suppliers, Customers, and Products</strong>.</p>
                            </div>
                        </div>

                        <div class="tutorial-list-item">
                            <div class="tutorial-icon-box bg-warning-lt text-warning me-4 shadow-sm">
                                <i class="ti ti-shopping-cart"></i>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold fs-4">3. Operations</h3>
                                <p class="text-secondary mb-0">Manage daily tasks: <strong>Purchase Orders, Sales, Returns, POS, and Pipeline</strong>.</p>
                            </div>
                        </div>

                        <div class="tutorial-list-item">
                            <div class="tutorial-icon-box bg-info-lt text-info me-4 shadow-sm">
                                <i class="ti ti-chart-bar"></i>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold fs-4">4. Accounting & Reports</h3>
                                <p class="text-secondary mb-0">Track your finances: <strong>Journals, Chart of Accounts, Income Statements</strong>.</p>
                            </div>
                        </div>

                        <div class="tutorial-list-item">
                            <div class="tutorial-icon-box bg-purple-lt text-purple me-4 shadow-sm">
                                <i class="ti ti-shield-lock"></i>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold fs-4">5. Administration & Security</h3>
                                <p class="text-secondary mb-0">Configure system preferences on the <strong>Settings page</strong>, and monitor system changes via the <strong>Audit & Activity Logs</strong>.</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- STEP 2: Topics Grid -->
            <div class="modal-body py-5 px-4 px-md-5 d-none tutorial-step" id="tutorial-step-topics">
                <div class="text-center mb-5">
                    <h2 class="fw-bold text-dark mb-2">{{ __('What would you like to explore?') }}</h2>
                    <p class="text-secondary">{{ __('Select a module to jump right into the action.') }}</p>
                </div>

                <div class="row g-4">
                    <div class="col-md-4">
                        <a href="{{ route('admin.setting.index') }}" class="text-decoration-none">
                            <div class="card topic-card h-100 shadow-sm bg-primary-lt">
                                <div class="card-body text-center p-4">
                                    <div class="icon-wrapper bg-primary text-white mx-auto shadow">
                                        <i class="ti ti-adjustments"></i>
                                    </div>
                                    <h4 class="fw-bold text-dark">Configuration</h4>
                                    <p class="text-secondary small mb-0">Units, Categories, Taxes, Currencies, Company Profile</p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-4">
                        <a href="{{ route('admin.product') }}" class="text-decoration-none">
                            <div class="card topic-card h-100 shadow-sm bg-success-lt">
                                <div class="card-body text-center p-4">
                                    <div class="icon-wrapper bg-success text-white mx-auto shadow">
                                        <i class="ti ti-packages"></i>
                                    </div>
                                    <h4 class="fw-bold text-dark">Master Data</h4>
                                    <p class="text-secondary small mb-0">Products, Warehouses, Suppliers, Customers, Brands</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-md-4">
                        <a href="{{ route('admin.sales') }}" class="text-decoration-none">
                            <div class="card topic-card h-100 shadow-sm bg-warning-lt">
                                <div class="card-body text-center p-4">
                                    <div class="icon-wrapper bg-warning text-white mx-auto shadow">
                                        <i class="ti ti-shopping-cart"></i>
                                    </div>
                                    <h4 class="fw-bold text-dark">Operations</h4>
                                    <p class="text-secondary small mb-0">Sales Orders, Purchase Orders, POS, Returns, Pipeline</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-md-4">
                        <a href="{{ route('admin.accounting.chart') }}" class="text-decoration-none">
                            <div class="card topic-card h-100 shadow-sm bg-info-lt">
                                <div class="card-body text-center p-4">
                                    <div class="icon-wrapper bg-info text-white mx-auto shadow">
                                        <i class="ti ti-calculator"></i>
                                    </div>
                                    <h4 class="fw-bold text-dark">Accounting</h4>
                                    <p class="text-secondary small mb-0">Chart of Accounts, Journals, Trial Balance, Ledgers</p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-4">
                        <a href="{{ route('admin.reports.income-statement') }}" class="text-decoration-none">
                            <div class="card topic-card h-100 shadow-sm bg-indigo-lt">
                                <div class="card-body text-center p-4">
                                    <div class="icon-wrapper bg-indigo text-white mx-auto shadow">
                                        <i class="ti ti-report-analytics"></i>
                                    </div>
                                    <h4 class="fw-bold text-dark">Reports</h4>
                                    <p class="text-secondary small mb-0">Income Statement, Balance Sheet, Stock Reports, Tax Reports</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-md-4">
                        <a href="{{ route('admin.audit-log.index') }}" class="text-decoration-none">
                            <div class="card topic-card h-100 shadow-sm bg-purple-lt">
                                <div class="card-body text-center p-4">
                                    <div class="icon-wrapper bg-purple text-white mx-auto shadow">
                                        <i class="ti ti-shield-lock"></i>
                                    </div>
                                    <h4 class="fw-bold text-dark">Administration</h4>
                                    <p class="text-secondary small mb-0">Audit Logs, User Management, Roles, Settings</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- STEP 3: Contextual Help (Page Specific) -->
            <div class="modal-body py-5 px-4 px-md-5 d-none tutorial-step" id="tutorial-step-context">
                <div class="text-center mb-5">
                    <div class="icon-wrapper bg-blue-lt text-blue mx-auto shadow-sm mb-3" style="width: 64px; height: 64px; border-radius: 16px; font-size: 32px; display: flex; align-items: center; justify-content: center;">
                        <i class="ti {{ $contextIcon }}"></i>
                    </div>
                    <h2 class="fw-bold text-dark mb-2">{{ $contextTitle }}</h2>
                    <p class="text-secondary fs-4">{{ $contextDesc }}</p>
                </div>

                <div class="row">
                    <div class="col-md-10 offset-md-1">
                        <div class="card shadow-sm border-0 bg-blue-lt">
                            <div class="card-body p-4">
                                <h4 class="fw-bold mb-3"><i class="ti ti-bulb text-warning me-2"></i>{{ __('Quick Guide:') }}</h4>
                                <ul class="text-secondary fs-4 context-list ps-3 mb-0">
                                    @foreach($contextSteps as $step)
                                        <li>{!! $step !!}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer bg-light" style="border-top: 1px solid rgba(0,0,0,0.05); padding: 16px 24px;">
                <button type="button" class="btn btn-ghost-secondary me-auto" onclick="closeTutorialExplicitly()">
                    <i class="ti ti-x me-2"></i>{{ __('Skip / Close') }}
                </button>
                <button type="button" class="btn btn-outline-primary shadow-sm px-4 me-2 d-none" id="btn-back-topics" onclick="showTopics()">
                    <i class="ti ti-grid-dots me-2"></i> {{ __('View All Topics') }}
                </button>
                <button type="button" class="btn btn-primary btn-pill shadow-sm px-4" id="btn-next-tutorial" onclick="showTopics()">
                    {{ __('Next: View Topics') }} <i class="ti ti-arrow-right ms-2"></i>
                </button>
            </div>
        </div>
    </div>
</div>
