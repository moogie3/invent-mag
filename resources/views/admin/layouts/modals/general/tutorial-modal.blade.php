@php
    $routeName = Route::currentRouteName();
    $contextTitle = __('Help & Tutorial');
    $contextDesc = __('Need help navigating this page? Here is what you can do.');
    $contextIcon = 'ti-help';
    $contextSteps = [];

    // Contextual logic
    if ($routeName === 'admin.dashboard') {
        $contextTitle = __('Dashboard');
        $contextIcon = 'ti-dashboard';
        $contextDesc = __('Your business overview at a glance — metrics, charts, and quick actions.');
        $contextSteps = [
            __('<b>Key Metrics:</b> The top cards show today\'s sales, purchases, profit, and outstanding receivables/payables updated in real time.'),
            __('<b>Sales & Purchase Chart:</b> Toggle between Sales and Purchases using the tabs. Filter by date range (This Week, This Month, This Year, Custom) using the dropdown above the chart.'),
            __('<b>Sales Forecast:</b> If enabled on your plan, a predictive forecast chart is shown below the main chart, using historical data to project future sales trends.'),
            __('<b>Accounting Summary:</b> If accounting is enabled, a financial summary panel shows revenue, expenses, and net income for the selected period.'),
            __('<b>Recent Transactions:</b> The main content section shows your latest sales and purchase orders for quick access and review.'),
            __('<b>Quick Actions:</b> Use the "New Invoice" button (top right) to quickly create a Sales Order or Purchase Order without navigating through the menu.'),
            __('<b>System Alerts:</b> Low stock warnings and product expiry alerts are surfaced here so you can take action immediately.'),
        ];
    } elseif ($routeName === 'admin.product.create') {
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
    } elseif ($routeName === 'admin.product.edit') {
        $contextTitle = __('Edit Product');
        $contextIcon = 'ti-edit';
        $contextDesc = __('Modify product information, manage stock levels, and update pricing details.');
        $contextSteps = [
            __('<b>Basic Information:</b> Edit the product name, code, barcode, and description in the first section.'),
            __('<b>Category & Supplier:</b> Update the assigned category and supplier from the dropdown menus.'),
            __('<b>Unit:</b> Change the measurement unit if needed (e.g., from pcs to box).'),
            __('<b>Pricing:</b> Modify the buying price and selling price. The selling price is what customers pay.'),
            __('<b>Stock by Warehouse:</b> View current stock levels across all warehouses. Stock is adjusted through "Adjust Stock" (for individual products) or "Bulk Update" (for multiple products) from the product list page, not here.'),
            __('<b>Stock Transfer:</b> Click "Transfer Stock" to open the stock transfer page. Select source warehouse, destination warehouse, enter quantity, and submit to move stock between warehouses.')
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
    } elseif ($routeName === 'admin.reports.stock-transfer.page') {
        $contextTitle = __('Stock Transfer');
        $contextIcon = 'ti-forklift';
        $contextDesc = __('Move stock between warehouses to manage inventory distribution across locations.');
        $contextSteps = [
            __('<b>Select Source Warehouse:</b> Choose the warehouse where the stock will be taken from. Available products will be loaded based on this selection.'),
            __('<b>Select Destination Warehouse:</b> Choose the warehouse where the stock will be transferred to. Source and destination must be different.'),
            __('<b>Select Product:</b> Choose the product to transfer. The available quantity from the source warehouse will be displayed automatically.'),
            __('<b>Enter Quantity:</b> Specify how many units to transfer. The quantity cannot exceed the available stock in the source warehouse.'),
            __('<b>Add Reason:</b> Optionally add a reason for the transfer for better tracking and audit purposes.'),
            __('<b>Submit Transfer:</b> Click Transfer to complete the process. Stock will be deducted from source and added to destination warehouse.'),
            __('<b>View History:</b> Click "View Adjustment Log" to see all stock transfers and adjustments made in the system.')
        ];
    } elseif ($routeName === 'admin.reports.adjustment-log') {
        $contextTitle = __('Adjustment Log');
        $contextIcon = 'ti-file-text';
        $contextDesc = __('View history of all stock adjustments and transfers for audit and tracking purposes.');
        $contextSteps = [
            __('<b>View All Adjustments:</b> The table shows a complete history of all stock changes including adjustments and transfers. Each row shows the date, type (Adjustment or Transfer), product, warehouse changes, user who made the change, and reason.'),
            __('<b>Filter by Type:</b> Use the Type dropdown to filter between "Adjustment" (stock changes from Adjust Stock or Bulk Update) and "Transfer" (stock moved between warehouses).'),
            __('<b>Filter by Product:</b> Use the Product dropdown to see all adjustments for a specific product across all warehouses.'),
            __('<b>Filter by User:</b> Use the User dropdown to see all changes made by a specific user.'),
            __('<b>Filter by Date:</b> Use the Date From and Date To fields to narrow down the adjustment history to a specific period.'),
            __('<b>Export:</b> Use the Export button to download the adjustment log as PDF or CSV for external reporting or auditing.'),
            __('<b>Quick Actions:</b> Click the eye icon on any row to view detailed information about that specific adjustment or transfer.')
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
    } elseif ($routeName === 'admin.sales.create') {
        $contextTitle = __('Create Sales Order');
        $contextIcon = 'ti-plus';
        $contextDesc = __('Create a new sales order by selecting a customer, adding products, and setting payment terms.');
        $contextSteps = [
            __('<b>Sales Information:</b> Enter invoice number (optional, auto-generated if empty), select a customer and set the order date. The due date auto-fills based on the customer payment terms.'),
            __('<b>Customer Credit:</b> When you select a customer, any available customer credits from previous returns will be displayed. You can apply credit to reduce the order total.'),
            __('<b>Product Selection:</b> Search and select products from the dropdown. The product icon shows the current remaining stock. Click "Add Product" to add multiple products.'),
            __('<b>Auto-Pricing:</b> When you select a product, the system auto-fills prices based on the customer name. It shows the last purchase price and your selling price. You can manually adjust the price if needed.'),
            __('<b>Quantity & Discount:</b> Enter quantity for each product. The system calculates line totals automatically. You can add per-item discounts as percentage or fixed amount.'),
            __('<b>Invoice Discount:</b> Apply a global discount to the entire order at the bottom (percentage or fixed amount) in addition to per-item discounts.'),
            __('<b>Order Summary:</b> View subtotal, tax (PPN), discount, and grand total. Tax rate is pulled from your tax settings.'),
            __('<b>Credit Applied:</b> If you applied customer credit, it will reduce the final balance. The invoice status will update to "Paid" if credit covers the full amount.'),
            __('<b>Submit:</b> Click "Create Sales Order" to save. The order will be created as "Unpaid" and you can record payments later on the Sales Order Edit page.')
        ];
    } elseif ($routeName === 'admin.sales.edit') {
        $contextTitle = __('Edit Sales Order');
        $contextIcon = 'ti-edit';
        $contextDesc = __('View and edit sales order details, adjust items, record payments, and manage returns.');
        $contextSteps = [
            __('<b>Sales Information:</b> The invoice number, order date, and due date are read-only. You can change the warehouse and payment type (if not yet paid).'),
            __('<b>Customer Info:</b> View customer details and payment terms. Customer cannot be changed once the order has payments.'),
            __('<b>Customer Credit:</b> If this invoice used customer credits from previous returns, it will be shown in the header. Credits reduce the balance due.'),
            __('<b>Fully Paid Warning:</b> If the invoice is already fully paid, you will see a warning modal immediately when you open the page. Most fields become read-only and cannot be edited.'),
            __('<b>Edit Items:</b> You can adjust quantities and prices for each item. Use the discount column for per-item discounts (percentage or fixed amount).'),
            __('<b>Invoice Discount:</b> Apply a global discount to the entire order at the bottom in addition to per-item discounts.'),
            __('<b>Record Payment:</b> If the order is not fully paid, click "Add Payment" to record customer payments. You can record partial or full payments.'),
            __('<b>Payment Info:</b> Total Paid shows the amount the customer has already paid. Balance shows the remaining amount the customer needs to pay (after credit applied).'),
            __('<b>Track Returns:</b> The quantity field shows the FINAL quantity (original quantity minus returned items). A "Returned" badge shows how many items were returned per product (e.g., "-3 Returned"). The amounts shown are already ADJUSTED to reflect the final quantity after returns. At the bottom, the "Returned (Adjusted)" row shows only the returned amount, not the original total. To create a new return, go to the Sales Order Returns menu.'),
            __('<b>Save:</b> Click "Save Changes" to update the sales order. Note: You cannot change customer if payments have already been recorded.')
        ];
    } elseif ($routeName === 'admin.po.edit') {
        $contextTitle = __('Edit Purchase Order');
        $contextIcon = 'ti-edit';
        $contextDesc = __('View and edit purchase order details, adjust items, record payments, and manage returns.');
        $contextSteps = [
            __('<b>Purchase Information:</b> The invoice number, order date, and due date are read-only. You can change the warehouse and payment type (if not yet paid).'),
            __('<b>Supplier Info:</b> View supplier details and payment terms.'),
            __('<b>Fully Paid Warning:</b> If the invoice is already fully paid, you will see a warning modal immediately when you open the page. Most fields become read-only and cannot be edited.'),
            __('<b>Edit Items:</b> You can adjust quantities and prices for each item. Use the discount column for per-item discounts (percentage or fixed amount).'),
            __('<b>Invoice Discount:</b> Apply a global discount to the entire order at the bottom in addition to per-item discounts.'),
            __('<b>Record Payment:</b> If the order is not fully paid, click "Add Payment" to record supplier payments. You can record partial or full payments.'),
            __('<b>Payment Info:</b> Total Paid shows the amount that has already been paid. Balance shows the remaining amount to be paid.'),
            __('<b>Track Returns:</b> The quantity field shows the FINAL quantity (original quantity minus returned items). A returned icon shows when items have been returned. The amounts shown are already ADJUSTED to reflect the final quantity after returns. At the bottom, the "Returned" row shows only the returned amount, not the original total. To create a new return, go to the Purchase Order Returns menu.'),
            __('<b>Save:</b> Click "Save Changes" to update the purchase order.')
        ];
    } elseif ($routeName === 'admin.sales.view') {
        $contextTitle = __('messages.view_sales_order');
        $contextIcon = 'ti-eye';
        $contextDesc = __('messages.view_sales_order_desc');
        $contextSteps = [
            __('<b>Order Information:</b> View the invoice number, customer, order date, and due date. Sales order status shows Paid (fully paid), Partially Paid (partial payment made), Unpaid (awaiting payment, shows as Pending, Due Today, Due in X Days, or Overdue based on due date), or Returned (items returned). Status is updated based on payment records and due date.'),
            __('<b>Customer Credit Applied:</b> If this invoice used customer credits from previous returns, it will be shown in the header.'),
            __('<b>Items Table:</b> Shows all products sold in this order. The quantity shown is the FINAL quantity (original quantity minus any returned items).'),
            __('<b>Returned Items:</b> If items have been returned, a "Returned" badge shows how many items were returned per product (e.g., "-3 Returned").'),
            __('<b>Amount Display:</b> The amounts shown are already ADJUSTED - they reflect the final quantity after returns. The original total is not shown here.'),
            __('<b>Returned (Adjusted):</b> At the bottom in the amount summary, the "Returned" row shows only the returned amount, not the original total. The adjusted subtotal shows the final total after deducting returns.'),
            __('<b>To Create Return:</b> To process a new return for this invoice, go to the Sales Order Returns menu and create a new return for this invoice.'),
            __('<b>Actions:</b> Use the "Print Invoice" button to print or download PDF. Click "Edit Invoice" to modify the order.')
        ];
    } elseif ($routeName === 'admin.po.view') {
        $contextTitle = __('View Purchase Order');
        $contextIcon = 'ti-eye';
        $contextDesc = __('View detailed purchase order information including items, payment status, and history.');
        $contextSteps = [
            __('<b>Order Information:</b> View the invoice number, supplier, order date, and due date. Purchase order status shows Paid (fully paid), Partially Paid (partial payment made), Pending (awaiting payment, shows as Due Today, Due in X Days, or Overdue based on due date), or Returned (items returned to supplier). Status is updated based on payment records and due date.'),
            __('<b>Supplier Info:</b> View supplier details including name, address, and contact information.'),
            __('<b>Items Table:</b> Shows all products purchased in this order. The quantity shown is the FINAL quantity (original quantity minus any returned items).'),
            __('<b>Returned Items:</b> If items have been returned, a returned icon appears in the items list. The quantity reflects the adjusted amount after returns.'),
            __('<b>Amount Display:</b> The amounts shown are already ADJUSTED - they reflect the final quantity after returns. The original total is not shown here.'),
            __('<b>Returned (Adjusted):</b> At the bottom in the amount summary, the "Returned" row shows only the returned amount, not the original total. The adjusted subtotal shows the final total after deducting returns.'),
            __('<b>To Create Return:</b> To process a new return for this purchase order, go to the Purchase Order Returns menu and create a new return for this invoice.'),
            __('<b>Actions:</b> Use the "Print Invoice" button to print or download PDF. Click "Edit Invoice" to modify the order.')
        ];
    } elseif ($routeName === 'admin.sales-returns.index') {
        $contextTitle = __('Sales Returns List');
        $contextIcon = 'ti-receipt-refund';
        $contextDesc = __('View and manage all sales returns, track returned items, and handle refund or credit operations.');
        $contextSteps = [
            __('<b>View Returns:</b> View all sales returns in the table. Use filters to find specific returns by status or date.'),
            __('<b>Create Return:</b> Click "New Sales Return" to create a return. Select the sales invoice, add items to return, specify quantities, and set return date.'),
            __('<b>Return Status:</b> Returns have three statuses: Pending (awaiting review), Completed (processed), and Canceled (rejected).'),
            __('<b>Credit Note:</b> When a return is marked "Completed" AND the original invoice is fully paid, a customer credit note is automatically created. The Credit column shows a credit card icon when credit was created.'),
            __('<b>Completed Returns:</b> When a return is marked Completed, inventory is added back to stock, accounting journal entries are reversed, and if paid, a credit note is created for the customer.'),
            __('<b>Customer Credit:</b> The created credit note is tied to the customer (not the invoice). The customer can use it to pay for future sales orders.'),
            __('<b>View Credit:</b> Click on the customer name in CRM to see all their credits in the "Customer Credits" tab.'),
            __('<b>Bulk Actions:</b> Select multiple returns to perform bulk Complete, bulk Cancel, or bulk Delete operations.'),
            __('<b>Export:</b> Use the export dropdown to download return data as CSV or PDF for external use or reporting.')
        ];
    } elseif ($routeName === 'admin.sales-returns.create') {
        $contextTitle = __('Create Sales Return');
        $contextIcon = 'ti-plus';
        $contextDesc = __('Create a new sales return for customer returns, specifying items and quantities.');
        $contextSteps = [
            __('<b>Select Invoice:</b> Choose the sales invoice to return items from. Only invoices without existing returns are shown.'),
            __('<b>Return Date:</b> Set the date when the return was processed.'),
            __('<b>Reason:</b> Optionally add a reason for the return for tracking and audit purposes.'),
            __('<b>Select Items:</b> After selecting an invoice, choose which items to return. Each product shows the quantity originally sold.'),
            __('<b>Return Quantity:</b> Enter the quantity to return for each item. You cannot return more than what was originally sold.'),
            __('<b>IMPORTANT - Credit Note Condition:</b> A customer credit note is ONLY created when BOTH conditions are met: (1) Return status is "Completed" AND (2) Original invoice status is "Paid". If the invoice is Unpaid or Partially Paid, no credit is created.'),
            __('<b>Status:</b> The return is created as "Pending" by default. Once reviewed and processed, change it to "Completed" to apply inventory, accounting changes, and create credit note (if applicable).'),
            __('<b>Save:</b> Click "Create Sales Return" to save. You can edit the return later while it is still in Pending status.')
        ];
    } elseif ($routeName === 'admin.sales-returns.edit') {
        $contextTitle = __('Edit Sales Return');
        $contextIcon = 'ti-edit';
        $contextDesc = __('Modify an existing sales return, update items, quantities, or status.');
        $contextSteps = [
            __('<b>Return Information:</b> View the linked sales invoice in the first section. Click the invoice number to view the original sales order.'),
            __('<b>Return Date:</b> Modify the return date if needed.'),
            __('<b>Reason:</b> Update the reason for the return for tracking and audit purposes.'),
            __('<b>Edit Items:</b> You can modify the quantities of items to return. Available quantity shows how much can still be returned.'),
            __('<b>Return Quantity:</b> Adjust the quantity for each item. You cannot return more than the available quantity.'),
            __('<b>Status:</b> Change the status to "Completed" to process the return, or "Canceled" to reject it. Once Completed or Canceled, edits are no longer allowed.'),
            __('<b>IMPORTANT - Credit Note Condition:</b> A customer credit note is ONLY created when BOTH conditions are met: (1) Return status is "Completed" AND (2) Original invoice status is "Paid". If the invoice is Unpaid or Partially Paid, no credit is created.'),
            __('<b>Save:</b> Click "Update Sales Return" to save your changes.')
        ];
    } elseif ($routeName === 'admin.sales-returns.show') {
        $contextTitle = __('Sales Return Details');
        $contextIcon = 'ti-eye';
        $contextDesc = __('View detailed information about a sales return.');
        $contextSteps = [
            __('<b>Return Details:</b> View the full return information including linked sales invoice, return date, and reason.'),
            __('<b>Items:</b> View all items being returned with their quantities and prices.'),
            __('<b>Status:</b> View the current status of the return (Pending, Completed, or Canceled).'),
            __('<b>Credit Information:</b> If a credit note was created, view the credit details.'),
            __('<b>Print:</b> Use the Print button to generate a PDF of the return slip.')
        ];
    } elseif (Str::startsWith($routeName, 'admin.sales') && !Str::startsWith($routeName, 'admin.sales_pipeline')) {
        $contextTitle = __('Sales Orders');
        $contextIcon = 'ti-shopping-cart';
        $contextDesc = __('Manage customer orders, invoicing, payments, and tracking of sales transactions.');
        $contextSteps = [
            __('<b>Create Sales Order:</b> Click "Create Sales Order" to start a new sale. Select a customer, add products from inventory, specify quantities, and the system calculates totals automatically.'),
            __('<b>View Details:</b> Click the View button to open a detailed modal showing the full sales order including customer info, items, totals, payment status, and payment history.'),
            __('<b>Payment Status:</b> Sales order status shows Paid (fully paid), Partially Paid (partial payment made), Unpaid (awaiting payment, shows as Pending, Due Today, Due in X Days, or Overdue based on due date), or Returned (items returned). Status is updated based on payment records and due date.'),
            __('<b>Returned Indicator:</b> If an invoice has any returned items, a returned icon appears in the Amount column. The amount shown is already adjusted (original total minus returns).'),
            __('<b>Sales Returns:</b> For returned items, create a sales return order which automatically adjusts inventory and issues customer credits. Returns are linked to the original sales order for tracking. You can access the Sales Order Return menu to manage returns.'),
            __('<b>Accounting:</b> When a sales order is created, journal entries are automatically generated: Accounts Receivable (debit), Sales Revenue (credit), COGS (debit), and Inventory (credit).'),
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
            __('<b>Customer Credit:</b> When a customer has available credits from previous returns, you can apply them to reduce the order balance in the Create Sales Order page.'),
            __('<b>Sales Pipeline:</b> Track prospects and opportunities through customizable stages (Lead, Quoted, Won, Lost) to visualize the sales funnel and forecast revenue.'),
            __('<b>Payments:</b> Record customer payments against generated invoices, including partial payments, full payments, and payment due date tracking.'),
            __('<b>Sales Returns:</b> Process customer returns through dedicated return orders. When a return is completed on a paid invoice, a customer credit note is automatically created.'),
            __('<b>Customer Credits:</b> View all customer credits in the CRM modal (click customer name → Customer Credits tab). Credits can be used on future purchases.'),
            __('<b>Invoices:</b> Generate professional invoices for printing or PDF export, customizable with business logo and terms.')
        ];
} elseif (Str::startsWith($routeName, 'admin.pos')) {
        $contextTitle = __('Point of Sales (POS)');
        $contextIcon = 'ti-cash';
        $contextDesc = __('Process retail sales transactions with product selection, cart management, and multiple payment options.');
        $contextSteps = [
            __('<b>Product Catalog:</b> Browse products by category in the left panel. Use the search bar to quickly find products by name or code. Click on a product to add it to the cart.'),
            __('<b>Quick Create Product:</b> Need a new product? Click "Create Product" in the product selection area to add it on-the-fly without leaving the POS.'),
            __('<b>Shopping Cart:</b> The right panel shows your current cart with product details, quantities, and running total. Adjust quantities directly in the cart or remove items.'),
            __('<b>Customer Selection:</b> Select an existing customer or create a new customer on-the-fly for the transaction. Click "Create Customer" to add new customers without leaving the POS. Every transaction links the sale to the customer for CRM tracking.'),
            __('<b>Sale Summary:</b> Click "Pay" to open the payment modal showing full sale details, subtotal, discount, tax (PPN), and grand total.'),
            __('<b>Payment Methods:</b> Choose from Cash, Card, Transfer, or E-Wallet. For cash payments, enter the amount received and the system calculates change automatically.'),
            __('<b>Complete Sale:</b> Use the exact amount button to auto-fill the received amount with the grand total. The complete transaction button processes the sale and generates a receipt.')
        ];
    } elseif ($routeName === 'admin.po.create') {
        $contextTitle = __('Create Purchase Order');
        $contextIcon = 'ti-plus';
        $contextDesc = __('Create a new purchase order to record stock purchases from suppliers.');
        $contextSteps = [
            __('<b>Purchase Information:</b> Enter invoice number (optional, auto-generated if empty), select a supplier and warehouse, set the order date. The due date auto-fills based on the supplier payment terms.'),
            __('<b>Product Selection:</b> Search and select products from the dropdown. The product icon shows the current available stock in the selected warehouse. Click "Add Product" to add multiple products.'),
            __('<b>Supplier Price & New Price:</b> When you select a product, the Supplier Price (past price from this supplier) auto-fills. You can adjust the New Price if needed.'),
            __('<b>Autofill Info:</b> Past price & due date are based on supplier. The Supplier Price field shows the last price from this supplier.'),
            __('<b>Quantity:</b> Enter quantity for each product. The system calculates line totals automatically. Quantity cannot exceed available stock.'),
            __('<b>Invoice Discount:</b> Apply a global discount to the entire order (percentage or fixed amount) at the bottom.'),
            __('<b>Order Summary:</b> View subtotal, discount, and grand total.'),
            __('<b>Submit:</b> Click "Create Purchase Order" to save. The order will be created as "Unpaid/Pending" and you can record payments later on the Purchase Order Edit page.')
        ];
    } elseif (Str::startsWith($routeName, 'admin.po')) {
        $contextTitle = __('Purchase Orders (PO)');
        $contextIcon = 'ti-truck-delivery';
        $contextDesc = __('Manage stock replenishment from suppliers through purchase order creation and tracking.');
        $contextSteps = [
            __('<b>Create Purchase Order:</b> Initiate a purchase order by selecting a supplier, adding products, specifying quantities with pricing, and reviewing the total cost.'),
            __('<b>View Details:</b> Click the View button to open a detailed modal showing the full PO including supplier info, order details, items with prices, totals, and payment status.'),
            __('<b>Payment Status:</b> Purchase order status shows Paid (fully paid), Partially Paid (partial payment made), Pending (awaiting payment, shows as Due Today, Due in X Days, or Overdue based on due date), or Returned (items returned to supplier). Status is updated based on payment records and due date.'),
            __('<b>Returned Indicator:</b> If a PO has any returned items, a returned icon appears in the Amount column. The amount shown is already adjusted (original total minus returns).'),
            __('<b>Purchase Returns:</b> For returned items, create a purchase return order which automatically adjusts inventory. Returns are linked to the original purchase order for tracking. You can access the Purchase Order Return menu to manage returns.'),
            __('<b>Accounting:</b> When a purchase order is created, journal entries are automatically generated: Accounts Payable (credit), Purchases (debit), COGS (credit), and Inventory (debit).'),
            __('<b>Bulk Actions:</b> Select multiple POs using checkboxes to perform Bulk Delete, Bulk Mark as Paid, or Bulk Export (PDF/CSV). Note: Deleting unpaid POs restores stock, while paid POs do not.'),
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
    } elseif ($routeName === 'admin.accounting.ledger') {
        $contextTitle = __('General Ledger');
        $contextIcon = 'ti-book';
        $contextDesc = __('View detailed transaction history for each account, track debits and credits, and monitor account balances.');
        $contextSteps = [
            __('<b>Account Selection:</b> Select an account from the dropdown to view its transaction history. The dropdown shows all available accounts with their codes.'),
            __('<b>Date Range:</b> Set the start date and end date to filter transactions within a specific period.'),
            __('<b>Filter:</b> Click the filter button to load transactions for the selected account and date range.'),
            __('<b>Opening Balance:</b> The first row shows the opening balance at the start of the selected period.'),
            __('<b>Transactions:</b> Each transaction shows the date, description, debit amount, credit amount, and running balance.'),
            __('<b>View Source:</b> For transactions linked to source documents (sales, purchases, payments), a "View Source" link appears. Click to open the related document.'),
            __('<b>Closing Balance:</b> The last row shows the closing balance at the end of the selected period.'),
            __('<b>Export:</b> Use the export dropdown to download the ledger as PDF or CSV for external reporting.')
        ];
    } elseif ($routeName === 'admin.accounting.journal') {
        $contextTitle = __('General Journal');
        $contextIcon = 'ti-notebook';
        $contextDesc = __('View all journal entries, both system-generated and manual, for comprehensive accounting records.');
        $contextSteps = [
            __('<b>Date Filter:</b> Set the start date and end date to filter journal entries within a specific period.'),
            __('<b>Filter:</b> Click the filter button to load journal entries for the selected date range.'),
            __('<b>Entry List:</b> View all journal entries in the table. Each entry shows the date, description, accounts, debit and credit amounts.'),
            __('<b>View Source:</b> For system-generated entries (from sales, purchases, etc.), a "View Source" link appears. Click to open the related document.'),
            __('<b>Manual Entries:</b> To create new manual journal entries, use the Journal Entries menu (separate from General Journal).'),
            __('<b>Export:</b> Use the export dropdown to download journal entries as PDF or CSV for external reporting.')
        ];
    } elseif ($routeName === 'admin.accounting.journal-entries.index') {
        $contextTitle = __('Manual Journal Entries');
        $contextIcon = 'ti-notebook';
        $contextDesc = __('Create and manage manual journal entries for adjusting entries, accruals, and other accounting adjustments.');
        $contextSteps = [
            __('<b>Entry List:</b> View all manual journal entries in the table. Each entry shows the date, description, debit, credit, and status.'),
            __('<b>Status:</b> Entries can be Draft (not yet posted), Posted (confirmed and recorded), or Voided (canceled).'),
            __('<b>Reversed Entries:</b> A reversed entry icon appears if an entry has been reversed.'),
            __('<b>Create Entry:</b> Click the "Create Journal Entry" button to create a new manual entry.'),
            __('<b>View:</b> Click the eye icon to view full entry details including all debit and credit line items.'),
            __('<b>Edit:</b> Only draft entries can be edited. Click the edit icon to modify.'),
            __('<b>Delete:</b> Only draft entries can be deleted. Click the trash icon to remove.'),
            __('<b>Reverse:</b> Only posted entries can be reversed. Click the reverse icon to create a reversing entry.')
        ];
    } elseif ($routeName === 'admin.accounting.journal-entries.create') {
        $contextTitle = __('Create Journal Entry');
        $contextIcon = 'ti-plus';
        $contextDesc = __('Create a new manual journal entry with debit and credit transactions.');
        $contextSteps = [
            __('<b>Date:</b> Set the transaction date for the journal entry.'),
            __('<b>Description:</b> Enter a description for the journal entry.'),
            __('<b>Notes:</b> Optionally add notes for additional context.'),
            __('<b>Add Transaction:</b> Click "Add Row" to add more transaction lines. Each line requires an account, debit amount, or credit amount.'),
            __('<b>Account Selection:</b> Select an account from the dropdown. Use the search to find accounts by name or code.'),
            __('<b>Debit & Credit:</b> Enter either a debit or credit amount for each line. Amounts must be balanced (total debits must equal total credits).'),
            __('<b>Balance Check:</b> The summary section shows total debits, total credits, and the difference. The entry can only be saved when debits equal credits.'),
            __('<b>Save:</b> Click "Create Journal Entry" to save as draft. You can post the entry later from the entry list.')
        ];
    } elseif ($routeName === 'admin.accounting.journal-entries.edit') {
        $contextTitle = __('Edit Journal Entry');
        $contextIcon = 'ti-edit';
        $contextDesc = __('Edit an existing draft journal entry, modify transactions, and save changes.');
        $contextSteps = [
            __('<b>Date:</b> Modify the transaction date for the journal entry if needed.'),
            __('<b>Description:</b> Update the description for the journal entry.'),
            __('<b>Notes:</b> Add or modify notes for additional context.'),
            __('<b>Edit Transactions:</b> Modify existing transaction lines. You can change account, debit, or credit amounts.'),
            __('<b>Add/Remove Rows:</b> Use "Add Row" to add new transactions or remove existing ones.'),
            __('<b>Balance Check:</b> Ensure total debits equal total credits before saving.'),
            __('<b>Save:</b> Click "Update Journal Entry" to save changes. The entry remains in Draft status.')
        ];
    } elseif ($routeName === 'admin.accounting.journal-entries.show') {
        $contextTitle = __('Journal Entry Details');
        $contextIcon = 'ti-eye';
        $contextDesc = __('View full details of a journal entry, including all transactions and audit history.');
        $contextSteps = [
            __('<b>Entry Overview:</b> View the date, description, and status of the journal entry.'),
            __('<b>Transaction Lines:</b> See all debit and credit line items with their account names and amounts.'),
            __('<b>Balance:</b> Total debits and credits are shown at the bottom.'),
            __('<b>Audit Log:</b> View the history of changes made to this entry, including who made changes and when.'),
            __('<b>Related Entries:</b> If this entry was reversed, see links to the reversed and reversing entries.'),
            __('<b>Actions:</b> Draft entries can be edited or posted. Posted entries can be reversed or voided.'),
            __('<b>Post:</b> Click "Post Entry" to confirm the entry and record it in the general ledger.'),
            __('<b>Reverse:</b> Create a reversing entry to undo a posted entry.'),
            __('<b>Void:</b> Cancel a posted entry completely.')
        ];
    } elseif ($routeName === 'admin.accounting.chart') {
        $contextTitle = __('Chart of Accounts');
        $contextIcon = 'ti-list-details';
        $contextDesc = __('View all accounts in the chart of accounts, organized by account type for financial tracking.');
        $contextSteps = [
            __('<b>Account List:</b> View all accounts organized by type (Asset, Liability, Equity, Revenue, Cost of Sales, Expense).'),
            __('<b>Account Code:</b> Each account has a unique code for identification.'),
            __('<b>Account Name:</b> The official name of the account used in transactions.'),
            __('<b>Account Type:</b> Categories include Asset, Liability, Equity, Revenue, Cost of Sales, and Expense.'),
            __('<b>Description:</b> A brief explanation of what the account is used for.'),
            __('<b>System Accounts:</b> These are system-defined accounts used by the application for automatic journal entries.'),
            __('<b>Account Setup:</b> To modify default accounts for sales, purchases, and payments, go to Accounting Setup in System Settings.')
        ];
    } elseif ($routeName === 'admin.accounting.accounts.index') {
        $contextTitle = __('Chart of Accounts');
        $contextIcon = 'ti-building-bank';
        $contextDesc = __('View and manage all accounts in the chart of accounts for financial tracking.');
        $contextSteps = [
            __('<b>Account List:</b> View all accounts in a hierarchical tree structure. Sub-accounts are indented under their parent accounts.'),
            __('<b>Search:</b> Use the search box to find accounts by name or code.'),
            __('<b>Account Details:</b> Each account shows name, code, type, and active status.'),
            __('<b>Active Status:</b> Green badge shows active accounts, red badge shows inactive accounts.'),
            __('<b>Actions:</b> Use the edit icon to modify or trash icon to delete an account.'),
            __('<b>Add Account:</b> Click "Add Account" to create a new account in the chart.'),
            __('<b>Export:</b> Use the export dropdown to download accounts as PDF or CSV.')
        ];
    } elseif ($routeName === 'admin.accounting.accounts.create') {
        $contextTitle = __('Add Account');
        $contextIcon = 'ti-plus';
        $contextDesc = __('Create a new account in the chart of accounts.');
        $contextSteps = [
            __('<b>Name:</b> Enter the official name of the account.'),
            __('<b>Code:</b> Enter a unique code for the account. Use a numbering system that matches your accounting standards.'),
            __('<b>Type:</b> Select the account type: Asset, Liability, Equity, Revenue, Cost of Sales, or Expense.'),
            __('<b>Parent Account:</b> Optionally select a parent account to create a sub-account under an existing account. Leave empty for a main account.'),
            __('<b>Description:</b> Optionally add a description explaining the account purpose.'),
            __('<b>Active:</b> The account is active by default. Uncheck to disable the account.'),
            __('<b>Save:</b> Click "Add Account" to save the new account to the chart.')
        ];
    } elseif ($routeName === 'admin.accounting.accounts.edit') {
        $contextTitle = __('Edit Account');
        $contextIcon = 'ti-edit';
        $contextDesc = __('Modify an existing account in the chart of accounts.');
        $contextSteps = [
            __('<b>Name:</b> Modify the account name if needed.'),
            __('<b>Code:</b> The account code cannot be changed after creation.'),
            __('<b>Type:</b> Select the account type: Asset, Liability, Equity, Revenue, Cost of Sales, or Expense.'),
            __('<b>Parent Account:</b> Optionally change the parent account to create a sub-account under a different account.'),
            __('<b>Description:</b> Update the description if needed.'),
            __('<b>Active:</b> Toggle to enable or disable the account.'),
            __('<b>Save:</b> Click "Update Account" to save changes.')
        ];
    } elseif ($routeName === 'admin.accounting.trial_balance') {
        $contextTitle = __('Trial Balance');
        $contextIcon = 'ti-scale';
        $contextDesc = __('View the trial balance report to verify that debits equal credits across all accounts.');
        $contextSteps = [
            __('<b>Date Filter:</b> Set the "As of Date" to determine which transactions to include in the report.'),
            __('<b>Filter:</b> Click the filter button to load the trial balance for the selected date.'),
            __('<b>Account List:</b> View all accounts with their balances. Each account shows code, name, debit balance, and credit balance.'),
            __('<b>Balance Display:</b> Active accounts with transactions show their balances. Empty accounts are not displayed.'),
            __('<b>Debit & Credit:</b> Asset and Expense accounts normally have debit balances. Liability, Equity, and Revenue accounts normally have credit balances.'),
            __('<b>Totals:</b> The last row shows the total debits and total credits. For the trial balance to be correct, these should be equal.'),
            __('<b>Export:</b> Use the export dropdown to print or download as PDF or CSV.')
        ];
    } elseif ($routeName === 'admin.accounting.opening-balance.index') {
        $contextTitle = __('Opening Balance Setup');
        $contextIcon = 'ti-scale';
        $contextDesc = __('Set initial account balances for starting to use the accounting system.');
        $contextSteps = [
            __('<b>Balance Date:</b> Set the date for the opening balances. This is typically the first day of your fiscal year or when you started using the system.'),
            __('<b>Notes:</b> Optionally add notes to describe the opening balance setup.'),
            __('<b>Account List:</b> All accounts are shown grouped by type (Asset, Liability, Equity, Revenue, Expense).'),
            __('<b>Set Balances:</b> For each account, select the balance type (Debit/Credit) and enter the amount. Leave as 0 if no balance.'),
            __('<b>Balance Field:</b> The balance field automatically calculates based on the selected type and debit/credit amounts.'),
            __('<b>Balance Check:</b> Total Debits must equal Total Credits for the opening balances to be valid. The system validates this when saving.'),
            __('<b>Reset All:</b> Use "Reset All" to clear all entered balances and start fresh.'),
            __('<b>Save:</b> Click "Save Opening Balances" to save all entered balances. Existing balances will be updated.'),
            __('<b>Delete:</b> Use "Delete Opening Balances" to remove all saved opening balances.'),
            __('<b>Summary:</b> The bottom section shows the current saved opening balances with total debits and credits.')
        ];
    } elseif ($routeName === 'admin.reports.income-statement') {
        $contextTitle = __('Income Statement');
        $contextIcon = 'ti-file-analytics';
        $contextDesc = __('View the income statement (profit & loss report) showing revenue, expenses, and net income.');
        $contextSteps = [
            __('<b>Date Range:</b> Set the start date and end date to define the report period.'),
            __('<b>Generate Report:</b> Click "Generate Report" to load the income statement for the selected period.'),
            __('<b>Revenue Section:</b> Shows all revenue accounts with their amounts for the period, plus total revenue.'),
            __('<b>Expenses Section:</b> Shows all expense accounts with their amounts for the period, plus total expenses.'),
            __('<b>Net Income:</b> The bottom section shows Net Income (Revenue minus Expenses). Positive means profit, negative means loss.'),
            __('<b>Export:</b> Use the export dropdown to print or download the report as PDF or CSV.')
        ];
    } elseif ($routeName === 'admin.reports.balance-sheet') {
        $contextTitle = __('Balance Sheet');
        $contextIcon = 'ti-file-text';
        $contextDesc = __('View the balance sheet showing assets, liabilities, and equity at a specific point in time.');
        $contextSteps = [
            __('<b>As of Date:</b> Set the end date to show account balances as of that specific date.'),
            __('<b>Generate Report:</b> Click "Generate Report" to load the balance sheet for the selected date.'),
            __('<b>Assets Section:</b> Shows all asset accounts with their balances (debit balances). Includes total assets.'),
            __('<b>Liabilities Section:</b> Shows all liability accounts with their balances (credit balances). Includes total liabilities.'),
            __('<b>Equity Section:</b> Shows all equity accounts with their balances. Includes total equity.'),
            __('<b>Accounting Equation:</b> Total Liabilities + Total Equity should equal Total Assets. This verifies the balance sheet is correct.'),
            __('<b>Export:</b> Use the export dropdown to print or download the report as PDF or CSV.')
        ];
    } elseif ($routeName === 'admin.reports.aged-receivables') {
        $contextTitle = __('Aged Receivables');
        $contextIcon = 'ti-receipt-2';
        $contextDesc = __('View unpaid customer invoices grouped by how long they have been overdue.');
        $contextSteps = [
            __('<b>Aging Period:</b> Invoices are grouped by days overdue: Current (0-30 days), 31-60 days, 61-90 days, and 91+ days.'),
            __('<b>Customer List:</b> Each aging bucket shows invoices from customers with their invoice number, due date, days overdue, and amount.'),
            __('<b>Days Overdue:</b> Shows how many days each invoice is past its due date.'),
            __('<b>Totals:</b> Each bucket shows the total amount for that period.'),
            __('<b>Total Outstanding:</b> The total of all unpaid invoices across all aging buckets.'),
            __('<b>Collection Priority:</b> Focus on older buckets (61-90 days and 91+ days) for collection efforts as these are more likely to be problematic.'),
            __('<b>Export:</b> Use the export dropdown to print or download the report as PDF or CSV.')
        ];
    } elseif ($routeName === 'admin.reports.aged-payables') {
        $contextTitle = __('Aged Payables');
        $contextIcon = 'ti-receipt-tax';
        $contextDesc = __('View unpaid supplier bills grouped by how long they have been overdue.');
        $contextSteps = [
            __('<b>Aging Period:</b> Bills are grouped by days overdue: Current (0-30 days), 31-60 days, 61-90 days, and 91+ days.'),
            __('<b>Supplier List:</b> Each aging bucket shows bills from suppliers with their invoice number, due date, days overdue, and amount.'),
            __('<b>Days Overdue:</b> Shows how many days each bill is past its due date.'),
            __('<b>Totals:</b> Each bucket shows the total amount for that period.'),
            __('<b>Total Outstanding:</b> The total of all unpaid bills across all aging buckets.'),
            __('<b>Payment Priority:</b> Focus on older buckets (61-90 days and 91+ days) to maintain good supplier relationships.'),
            __('<b>Export:</b> Use the export dropdown to print or download the report as PDF or CSV.')
        ];
    } elseif ($routeName === 'admin.reports.recent-transactions') {
        $contextTitle = __('Recent Transactions');
        $contextIcon = 'ti-history';
        $contextDesc = __('View all recent sales and purchase transactions with filtering and bulk actions.');
        $contextSteps = [
            __('<b>Date Range:</b> Filter by time period: All Time, Today, This Week, This Month, Last Month, or Custom Range.'),
            __('<b>Transaction Type:</b> Filter by Sales or Purchases, or view All types.'),
            __('<b>Payment Status:</b> Filter by Paid, Partially Paid, or Unpaid status.'),
            __('<b>Apply Filters:</b> Click "Apply" to load transactions matching your filters, or "Clear" to reset.'),
            __('<b>Summary Cards:</b> View summary cards showing Total Transactions, Total Amount, Paid Transactions, and Outstanding.'),
            __('<b>Transactions Table:</b> View all transactions with invoice number, date, type, customer/supplier, amount, status, and actions.'),
            __('<b>Bulk Actions:</b> Select multiple transactions to perform bulk delete or export.'),
            __('<b>Export:</b> Use the export dropdown to download as PDF or CSV.')
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

<div class="modal modal-blur fade" id="tutorial-modal" tabindex="-1" role="dialog" aria-hidden="true" data-completed="{{ auth()->check() && auth()->user()->tutorial_completed ? 'true' : 'false' }}" data-route="{{ Route::currentRouteName() }}">
    <div class="modal-dialog modal-dialog-centered modal-lg tutorial-modal-dialog" role="document">

{{-- Sub-topic route map: generated by Blade so URLs are always correct.
     Exposed as window.tutorialSubTopics for tutorial-modal.js --}}
<script>
window.tutorialSubTopics = {
    configuration: {
        label: 'Configuration',
        subtitle: 'System setup and preferences',
        color: 'primary',
        icon: 'ti-adjustments',
        items: [
            { icon: 'ti-settings', label: 'System Settings', url: '{{ route('admin.setting.index') }}' },
            { icon: 'ti-ruler-2', label: 'Units of Measure', url: '{{ route('admin.setting.unit') }}' },
            { icon: 'ti-tag', label: 'Categories', url: '{{ route('admin.setting.category') }}' },
            { icon: 'ti-receipt-tax', label: 'Taxes', url: '{{ route('admin.setting.tax') }}' },
            { icon: 'ti-currency-dollar', label: 'Currencies', url: '{{ route('admin.setting.currency.edit') }}' },
            { icon: 'ti-building', label: 'Company Profile', url: '{{ route('admin.setting.profile.edit') }}' },
            { icon: 'ti-calculator', label: 'Accounting Setup', url: '{{ route('admin.setting.accounting') }}' },
        ]
    },
    masterdata: {
        label: 'Master Data',
        subtitle: 'Core business entities',
        color: 'success',
        icon: 'ti-packages',
        items: [
            { icon: 'ti-packages', label: 'Products', url: '{{ route('admin.product') }}' },
            { icon: 'ti-building-warehouse', label: 'Warehouses', url: '{{ route('admin.warehouse') }}' },
            { icon: 'ti-users', label: 'Customers', url: '{{ route('admin.customer') }}' },
            { icon: 'ti-truck', label: 'Suppliers', url: '{{ route('admin.supplier') }}' },
        ]
    },
    operations: {
        label: 'Operations',
        subtitle: 'Daily business transactions',
        color: 'warning',
        icon: 'ti-shopping-cart',
        items: [
            { icon: 'ti-report-money', label: 'Sales Orders', url: '{{ route('admin.sales') }}' },
            { icon: 'ti-truck-delivery', label: 'Purchase Orders', url: '{{ route('admin.po') }}' },
            { icon: 'ti-cash', label: 'Point of Sale (POS)', url: '{{ route('admin.pos') }}' },
            { icon: 'ti-receipt-refund', label: 'Sales Returns', url: '{{ route('admin.sales-returns.index') }}' },
            { icon: 'ti-arrow-back-up', label: 'Purchase Returns', url: '{{ route('admin.por.index') }}' },
            { icon: 'ti-chart-line', label: 'Sales Pipeline', url: '{{ route('admin.sales_pipeline.index') }}' },
        ]
    },
    accounting: {
        label: 'Accounting',
        subtitle: 'Financial records and journals',
        color: 'info',
        icon: 'ti-calculator',
        items: [
            { icon: 'ti-building-bank', label: 'Chart of Accounts', url: '{{ route('admin.accounting.accounts.index') }}' },
            { icon: 'ti-book', label: 'General Ledger', url: '{{ route('admin.accounting.ledger') }}' },
            { icon: 'ti-notebook', label: 'General Journal', url: '{{ route('admin.accounting.journal') }}' },
            { icon: 'ti-pencil', label: 'Manual Journal Entries', url: '{{ route('admin.accounting.journal-entries.index') }}' },
            { icon: 'ti-scale', label: 'Trial Balance', url: '{{ route('admin.accounting.trial_balance') }}' },
            { icon: 'ti-scale', label: 'Opening Balance', url: '{{ route('admin.accounting.opening-balance.index') }}' },
        ]
    },
    reports: {
        label: 'Reports',
        subtitle: 'Financial and inventory reports',
        color: 'indigo',
        icon: 'ti-report-analytics',
        items: [
            { icon: 'ti-file-analytics', label: 'Income Statement', url: '{{ route('admin.reports.income-statement') }}' },
            { icon: 'ti-file-text', label: 'Balance Sheet', url: '{{ route('admin.reports.balance-sheet') }}' },
            { icon: 'ti-receipt-2', label: 'Aged Receivables', url: '{{ route('admin.reports.aged-receivables') }}' },
            { icon: 'ti-receipt-tax', label: 'Aged Payables', url: '{{ route('admin.reports.aged-payables') }}' },
            { icon: 'ti-history', label: 'Recent Transactions', url: '{{ route('admin.reports.recent-transactions') }}' },
            { icon: 'ti-forklift', label: 'Stock Transfer', url: '{{ route('admin.reports.stock-transfer.page') }}' },
            { icon: 'ti-file-text', label: 'Adjustment Log', url: '{{ route('admin.reports.adjustment-log') }}' },
        ]
    },
    administration: {
        label: 'Administration',
        subtitle: 'System monitoring and access control',
        color: 'purple',
        icon: 'ti-shield-lock',
        items: [
            { icon: 'ti-shield-lock', label: 'Audit Log', url: '{{ route('admin.audit-log.index') }}' },
            { icon: 'ti-users', label: 'User Management', url: '{{ route('admin.users.index') }}' },
        ]
    },
};
</script>
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
                    <p class="text-secondary">{{ __('Select a category to see available pages.') }}</p>
                </div>

                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card topic-card h-100 shadow-sm bg-primary-lt cursor-pointer" onclick="showSubTopics('configuration')">
                            <div class="card-body text-center p-4">
                                <div class="icon-wrapper bg-primary text-white mx-auto shadow">
                                    <i class="ti ti-adjustments"></i>
                                </div>
                                <h4 class="fw-bold text-dark">Configuration</h4>
                                <p class="text-secondary small mb-0">Units, Categories, Taxes, Currencies, Company Profile</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card topic-card h-100 shadow-sm bg-success-lt cursor-pointer" onclick="showSubTopics('masterdata')">
                            <div class="card-body text-center p-4">
                                <div class="icon-wrapper bg-success text-white mx-auto shadow">
                                    <i class="ti ti-packages"></i>
                                </div>
                                <h4 class="fw-bold text-dark">Master Data</h4>
                                <p class="text-secondary small mb-0">Products, Warehouses, Suppliers, Customers</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card topic-card h-100 shadow-sm bg-warning-lt cursor-pointer" onclick="showSubTopics('operations')">
                            <div class="card-body text-center p-4">
                                <div class="icon-wrapper bg-warning text-white mx-auto shadow">
                                    <i class="ti ti-shopping-cart"></i>
                                </div>
                                <h4 class="fw-bold text-dark">Operations</h4>
                                <p class="text-secondary small mb-0">Sales, Purchases, POS, Returns, Pipeline</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card topic-card h-100 shadow-sm bg-info-lt cursor-pointer" onclick="showSubTopics('accounting')">
                            <div class="card-body text-center p-4">
                                <div class="icon-wrapper bg-info text-white mx-auto shadow">
                                    <i class="ti ti-calculator"></i>
                                </div>
                                <h4 class="fw-bold text-dark">Accounting</h4>
                                <p class="text-secondary small mb-0">Chart of Accounts, Journals, Trial Balance, Ledgers</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card topic-card h-100 shadow-sm bg-indigo-lt cursor-pointer" onclick="showSubTopics('reports')">
                            <div class="card-body text-center p-4">
                                <div class="icon-wrapper bg-indigo text-white mx-auto shadow">
                                    <i class="ti ti-report-analytics"></i>
                                </div>
                                <h4 class="fw-bold text-dark">Reports</h4>
                                <p class="text-secondary small mb-0">Income Statement, Balance Sheet, Stock Reports</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card topic-card h-100 shadow-sm bg-purple-lt cursor-pointer" onclick="showSubTopics('administration')">
                            <div class="card-body text-center p-4">
                                <div class="icon-wrapper bg-purple text-white mx-auto shadow">
                                    <i class="ti ti-shield-lock"></i>
                                </div>
                                <h4 class="fw-bold text-dark">Administration</h4>
                                <p class="text-secondary small mb-0">Audit Logs, User Management, Settings</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 2b: Sub-Topics List (drills into a topic category) -->
            <div class="modal-body py-4 px-4 px-md-5 d-none tutorial-step" id="tutorial-step-subtopics">
                <div class="d-flex align-items-center mb-4">
                    <button type="button" class="btn btn-ghost-secondary btn-sm me-3 px-2" onclick="backToTopics()">
                        <i class="ti ti-arrow-left me-1"></i>Back
                    </button>
                    <div>
                        <h3 class="fw-bold text-dark mb-0" id="subtopics-title">Topics</h3>
                        <p class="text-secondary mb-0 small" id="subtopics-subtitle">Select a page to navigate to.</p>
                    </div>
                </div>
                <div id="subtopics-list" class="list-group list-group-flush">
                    {{-- Populated dynamically by JS --}}
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
            <div class="modal-footer" style="border-top: 1px solid rgba(0,0,0,0.05); padding: 16px 24px;">
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
