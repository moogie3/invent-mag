@php
    $routeName = Route::currentRouteName();
    $contextTitle = __('Help & Tutorial');
    $contextDesc = __('Need help navigating this page? Here is what you can do.');
    $contextIcon = 'ti-help';
    $contextSteps = [];

    // Contextual logic
    if ($routeName === 'admin.product.create') {
        $contextTitle = __('Create Product');
        $contextIcon = 'ti-plus';
        $contextDesc = __('Add a new product to your inventory with basic information and pricing.');
        $contextSteps = [
            __('<b>Basic Information:</b> Enter the product code, barcode (optional), name, and description. The product code is required and must be unique.'),
            __('<b>Category & Supplier:</b> Select a category and supplier from the dropdown lists. These help organize your products and track supply sources.'),
            __('<b>Unit of Measure:</b> Choose the base unit for this product (e.g., Pieces, kg, Liters). This is required for inventory tracking.'),
            __('<b>Pricing:</b> Set the buying price (cost) and selling price (retail). The system will calculate your profit margin automatically.'),
            __('<b>Stock Information:</b> Note: Do NOT set opening stock here. After creating the product, use "Adjust Stock" or "Bulk Update" to add quantity. The product will automatically use the Main Warehouse.'),
            __('<b>Has Expiry:</b> Enable this option if the product has an expiration date. The actual expiry date will be set when receiving goods through a Purchase Order, not here.'),
            __('<b>Additional Details:</b> Optionally add product image, SKU, minimum stock alerts, and other optional information.')
        ];
    } elseif (Str::startsWith($routeName, 'admin.product')) {
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
            __('<b>Create Warehouse:</b> Click "Create Warehouse" to add a new warehouse with a name, address, and description. Use the "Set as Main Warehouse" checkbox to designate it as your default warehouse.'),
            __('<b>Main Warehouse:</b> Only ONE warehouse can be the main warehouse. It serves as the default for new products and stock adjustments. You must unset the current main warehouse before setting a new one.'),
            __('<b>Warehouse List:</b> View all warehouses in a table with their details. The main warehouse is clearly marked.'),
            __('<b>View Details:</b> Click the View button to see warehouse details including all products stored there, with PO reference, quantity per batch, and expiry dates for tracking.'),
            __('<b>Export:</b> Use the export dropdown to download warehouse data as CSV or PDF for external use or reporting.'),
            __('<b>Table Actions:</b> Each warehouse row has an action menu to View details (stock info), Edit information, or Delete. Note: The main warehouse cannot be deleted until another warehouse is set as main.'),
            __('<b>Stock Tracking:</b> Each warehouse maintains independent stock levels. Product views can be filtered by warehouse to display availability at each location.')
        ];
    } elseif (Str::startsWith($routeName, 'admin.customer')) {
        $contextTitle = __('Customer Management');
        $contextIcon = 'ti-users';
        $contextDesc = __('Manage your customers for sales orders, invoicing, and tracking customer history.');
        $contextSteps = [
            __('<b>Create Customer:</b> Click "Create Customer" to add a new customer with their name, email, phone, address, and optional image. Also set the payment terms (e.g., Net 30, Net 60) to define when payment is due.'),
            __('<b>Customer CRM:</b> Use the CRM features to track customer interactions, purchase history, and payment status. Access detailed analytics from the View option.'),
            __('<b>Customer List:</b> View all customers in a table with their details. Use the search box to find specific customers quickly.'),
            __('<b>Export:</b> Use the export dropdown to download customer data as CSV or PDF for external use or reporting.'),
            __('<b>Table Actions:</b> Each customer row has an action menu to View CRM details, Edit information, or Delete the customer.')
        ];
    } elseif (Str::startsWith($routeName, 'admin.supplier')) {
        $contextTitle = __('Supplier Management');
        $contextIcon = 'ti-truck';
        $contextDesc = __('Manage your suppliers for purchase orders and tracking supply relationships.');
        $contextSteps = [
            __('<b>Create Supplier:</b> Click "Create Supplier" to add a new supplier with their code, name, email, phone, address, and optional image. Also set the payment terms (e.g., Net 30, Net 60) and location (IN for local, OUT for international).'),
            __('<b>Supplier SRM:</b> Use the SRM (Supplier Relationship Management) features to track supplier performance, purchase history, and payment status. Access detailed analytics from the View option.'),
            __('<b>Supplier List:</b> View all suppliers in a table with their details. Use the search box to find specific suppliers quickly.'),
            __('<b>Export:</b> Use the export dropdown to download supplier data as CSV or PDF for external use or reporting.'),
            __('<b>Table Actions:</b> Each supplier row has an action menu to View SRM details, Edit information, or Delete the supplier.')
        ];
    } elseif (Str::startsWith($routeName, 'admin.sales') && !Str::startsWith($routeName, 'admin.sales_pipeline')) {
        $contextTitle = __('Sales Orders');
        $contextIcon = 'ti-shopping-cart';
        $contextDesc = __('Manage customer orders, invoicing, payments, and tracking of sales transactions.');
        $contextSteps = [
            __('<b>Create Sales Order:</b> Click "Create Sales Order" to start a new sale. Select a customer, add products from inventory, specify quantities, and the system calculates totals automatically.'),
            __('<b>View Details:</b> Click the View button to open a detailed modal showing the full sales order including customer info, items, totals, payment status, and payment history.'),
            __('<b>Payment Status:</b> Sales orders show status as Paid, Partial (partial payment), Unpaid (Pending, Due Today, Due in X Days, Overdue). The status updates based on payment records and due date.'),
            __('<b>Add Payment:</b> Click the payment button to record customer payments. You can record full payments or partial payments against the sales order.'),
            __('<b>Sales Returns:</b> For returned items, create a sales return order which automatically adjusts inventory and issues customer credits.'),
            __('<b>Bulk Actions:</b> Select multiple sales orders to perform bulk delete, bulk export (PDF/CSV), or bulk mark as completed.'),
            __('<b>Export:</b> Use the export dropdown to download sales data as CSV or PDF for external use or reporting.')
        ];
    } elseif (Str::startsWith($routeName, 'admin.sales_pipeline')) {
        $contextTitle = __('Sales Pipeline');
        $contextIcon = 'ti-chart-line';
        $contextDesc = __('Track and manage sales opportunities through customizable pipeline stages for revenue forecasting.');
        $contextSteps = [
            __('Invent-MAG is using the Kanban methodology to help you visualize and manage your sales pipeline. <b>What is Kanban?</b> Kanban is a visual method for managing work across different stages. Think of it like a whiteboard with sticky notes - each note represents an opportunity, and you move it from one column to another as progress is made.'),
            __('<b>Step 1 - Create Pipeline:</b> Click "Manage Pipelines" → "Add New Pipeline" tab to create your first pipeline. Give it a name (e.g., "Sales Pipeline 2024"), description, and optionally set it as default.'),
            __('<b>Step 2 - Configure Stages:</b> In the "Manage Pipelines" modal, go to the "Pipelines" tab and click the edit button on your pipeline. Use the stage form to add stages (e.g., Lead, Contacted, Quoted, Won, Lost). Mark stages as "Closed" if they are final stages like Won or Lost.'),
            __('<b>Step 3 - Create Opportunities:</b> Once your pipeline has stages, click the "+" button to create new opportunities. Select the customer, add products, set estimated value, expected close date, and notes.'),
            __('<b>Kanban Board:</b> View opportunities as cards organized by pipeline stages. Drag and drop cards to move them between stages to update their progress.'),
            __('<b>Edit Pipeline Info:</b> Click the edit button on a pipeline card in the "Pipelines" tab to modify its name, description, or default status.'),
            __('<b>Edit & Delete:</b> Choose the edit button or delete button on each opportunity card to edit details or delete the opportunity from the pipeline.'),
            __('<b>Convert to Sales:</b> Click the convert button on Won opportunities to transform them into actual sales orders for fulfillment.')
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
} elseif (Str::startsWith($routeName, 'admin.pos')) {
        $contextTitle = __('Point of Sales (POS)');
        $contextIcon = 'ti-cash-register';
        $contextDesc = __('Process retail sales transactions with product selection, cart management, and multiple payment options.');
        $contextSteps = [
            __('<b>Product Catalog:</b> Browse products by category in the left panel. Use the search bar to quickly find products by name or code. Click on a product to add it to the cart.'),
            __('<b>Shopping Cart:</b> The right panel shows your current cart with product details, quantities, and running total. Adjust quantities directly in the cart or remove items.'),
            __('<b>Customer Selection:</b> Select an existing customer or create a new customer on-the-fly for the transaction. This links the sale to the customer for CRM tracking.'),
            __('<b>Sale Summary:</b> Click "Pay" to open the payment modal showing full sale details, subtotal, discount, tax (PPN), and grand total.'),
            __('<b>Payment Methods:</b> Choose from Cash, Card, Transfer, or E-Wallet. For cash payments, enter the amount received and the system calculates change automatically.'),
            __('<b>Complete Sale:</b> Use the exact amount button to auto-fill the received amount with the grand total. The complete transaction button processes the sale and generates a receipt.')
        ];
    } elseif (Str::startsWith($routeName, 'admin.po')) {
        $contextTitle = __('Purchase Orders (PO)');
        $contextIcon = 'ti-truck-delivery';
        $contextDesc = __('Manage stock replenishment from suppliers through purchase order creation and tracking.');
        $contextSteps = [
            __('<b>Create Purchase Order:</b> Initiate a purchase order by selecting a supplier, adding products, specifying quantities with pricing, and reviewing the total cost.'),
            __('<b>View Details:</b> Click the View button to open a detailed modal showing the full PO including supplier info, order details, items with prices, totals, and payment status.'),
            __('<b>Bulk Actions:</b> Select multiple POs using checkboxes to perform Bulk Delete (removes all selected) or Bulk Mark as Paid (updates payment status for all). Note: Deleting unpaid POs restores stock, while paid POs do not.'),
            __('<b>Supplier Association:</b> Each purchase order is linked to a specific supplier, enabling tracking of purchasing history and maintenance of supplier relationships.'),
            __('<b>Goods Receipt:</b> When orders arrive, goods are received into the selected warehouse, automatically updating stock levels.'),
            __('<b>Export:</b> Use the export dropdown to download PO data as CSV or PDF for external use or reporting.')
        ];
    } elseif (Str::startsWith($routeName, 'admin.setting.index')) {
        $contextTitle = __('System Settings');
        $contextIcon = 'ti-settings';
        $contextDesc = __('Configure global preferences that determine workspace appearance and behavior.');
        $contextSteps = [
            __('<b>Interface Layout:</b> Choose between Sidebar (collapsible left menu), Navbar (top menu bar), or Both layouts. Additional options include Sidebar Lock (keeps sidebar fixed) and Sticky Navbar (keeps top bar visible while scrolling).'),
            __('<b>Theme Configuration:</b> Select Light or Dark theme mode. Toggle the theme switch button visibility to allow quick theme changes from any page.'),
            __('<b>Notifications:</b> Enable sound alerts and browser push notifications. Toggle success message display and set notification duration (3, 5, 10 seconds, or manual dismiss).'),
            __('<b>Session & Security:</b> Configure auto-logout after inactivity (15 min, 30 min, 1 hour, 2 hours, or disabled). Enable "Remember Last Page" to return to your previous location after login.'),
            __('<b>Performance:</b> Toggle UI animations, lazy loading for images, and fluid layout. Set automatic data refresh rate (15s, 30s, 1min, 5min, or manual only).'),
            __('<b>Language & Localization:</b> Set the system language for translations (English or Bahasa Indonesia).'),
            __('<b>Advanced Options:</b> Enable debug mode for development information, keyboard shortcuts for power users, and help tooltips throughout the interface. Click "View Shortcuts" to see available keyboard commands.')
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
        $contextDesc = __('Configure global tax rules and rates applied to sales orders and invoices.');
        $contextSteps = [
            __('<b>Define Tax Rates:</b> Create a primary tax type (e.g., VAT, GST, Sales Tax) and set the percentage rate (such as 10%, 15%, or 20%). Only one tax rate can be configured at a time.'),
            __('<b>Sales Application:</b> Enable or disable tax calculations for sales orders. When enabled, tax is automatically calculated on sales invoices. Note: Tax is not applied to purchase orders.')
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
            __('<b>Current Plan:</b> View your current subscription tier, monthly price, and status (Active, Trial, or Demo). Click "Upgrade Plan" to change your subscription.'),
            __('<b>Usage Statistics:</b> Monitor your current usage against plan limits for Users and Warehouses. Progress bars show how close you are to reaching limits (turns red when above 80%).'),
            __('<b>Plan Features:</b> See which premium features are included in your plan. Green checkmarks indicate enabled features, while gray X indicates features not available on your current tier.')
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
            __('<b>Activity Overview:</b> View statistics showing events logged today, this week, this month, and total count for quick monitoring.'),
            __('<b>Search & Filter:</b> Use the search box to find logs by description. Filter by User, Module (e.g., Product, Warehouse), Action (Created, Updated, Deleted, Login), or Date Range.'),
            __('<b>Activity Timeline:</b> View a chronological record of all system actions, including who performed each action, when, and what was affected.'),
            __('<b>Filter by Action Type:</b> Filter logs by action type (e.g., Created, Updated, Deleted, Login) to locate specific activity categories.'),
            __('<b>Filter by User:</b> View all activities performed by a specific user for individual behavior monitoring.'),
            __('<b>Security & Compliance:</b> Utilize audit logs for issue investigation, unauthorized change detection, and compliance demonstration during audits.')
        ];
    } elseif (Str::startsWith($routeName, 'admin.users')) {
        $contextTitle = __('User Management');
        $contextIcon = 'ti-users';
        $contextDesc = __('Control workspace access and manage user permission levels.');
        $contextSteps = [
            __('<b>User Statistics:</b> View the total number of users and a breakdown by role: Superuser (full admin access), Staff (general access), and POS (retail staff). Each role has different permission levels.'),
            __('<b>Create New User:</b> Click the "New User" button to add a new user. Enter their name, email, and assign a role to determine their access level.'),
            __('<b>User Table:</b> The table displays all users with their Name, Email, assigned Roles, and individual Permissions. Click column headers to sort the list.'),
            __('<b>Edit User:</b> Use the edit icon to modify a user\'s details or change their role and permissions.'),
            __('<b>Delete User:</b> Use the delete icon to remove a user from the system. This action cannot be undone.')
        ];
    } elseif ($routeName === 'admin.notifications' || $routeName === 'admin.setting.notifications') {
        $contextTitle = __('My Notifications');
        $contextIcon = 'ti-bell';
        $contextDesc = __('Stay informed about system alerts, important updates, and recent activity.');
        $contextSteps = [
            __('<b>Purchase Order & Sales:</b> Displays notifications related to purchase orders and sales activities, including order confirmations, payment updates, and delivery notifications.'),
            __('<b>Low Stock:</b> Shows alerts for products that are running low on inventory. Click to view which items need restocking and take action to replenish stock.'),
            __('<b>Expiring:</b> Notifies about products approaching or past their expiration date. Essential for managing perishable inventory and preventing waste.'),
            __('<b>System:</b> Contains general system notifications such as user activity, security alerts, and platform announcements.'),
            __('<b>Clear All:</b> Use the "Clear All Notifications" button at the top to remove all notifications in the current tab at once.'),
            __('<b>Clear Individual:</b> Each notification has a clear/checkmark button on the right side to mark it as read and remove it individually.')
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
