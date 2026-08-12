<?php

return [
    'superadmin.dashboard' => [
        'title' => 'Dashboard Overview',
        'icon' => 'ti ti-dashboard',
        'description' => 'Your central command center with platform statistics, charts, and quick actions.',
        'steps' => [
            'View the 4 stat cards at the top: <strong>Total Tenants</strong>, <strong>Active / Trial</strong>, <strong>Active Plans</strong>, and <strong>Subscriptions</strong> (with active and trial counts).',
            'Review the <strong>Tenants Created</strong> area chart showing the last 6 months of tenant registrations.',
            'Check the <strong>Subscription Status</strong> donut chart showing the ratio of active vs. trial subscriptions.',
            'See the <strong>Subscriptions by Plan</strong> horizontal bar chart displaying the top 5 plans by subscriber count.',
            'Use the 4 <strong>Quick Actions</strong> cards to jump to Manage Tenants, Create New Plan, Subscriptions, or Billing Overview.',
        ],
    ],

    'superadmin.tenants.index' => [
        'title' => 'Tenants',
        'icon' => 'ti ti-building-community',
        'description' => 'Manage all tenant workspaces on the platform.',
        'steps' => [
            'Browse the table of all tenants showing <strong>name</strong> (with avatar and email), <strong>domain</strong>, <strong>plan</strong>, <strong>status</strong>, and <strong>creation date</strong>.',
            'Status badges indicate: <span class="badge bg-success text-white">Active</span>, <span class="badge bg-primary text-white">Trial</span>, or <span class="badge bg-danger text-white">Expired</span>.',
            'Click a tenant name or the eye icon to view their full details.',
            'Use the pagination at the bottom to navigate through all tenants.',
        ],
    ],

    'superadmin.tenants.show' => [
        'title' => 'Tenant Details',
        'icon' => 'ti ti-building-community',
        'description' => 'View and manage individual tenant information.',
        'steps' => [
            'View the tenant <strong>profile card</strong> on the left showing avatar, name, email, and status badge.',
            'Click <strong>Impersonate Tenant</strong> to log in as the tenant admin for troubleshooting or support.',
            'Review the <strong>Tenant Information</strong> card on the right with domain, plan, status, trial end date, expiry, and creation date.',
            'Edit the <strong>domain</strong> inline by clicking the pencil icon, typing the new domain, and saving.',
            'Use the back button to return to the tenants list.',
        ],
    ],

    'superadmin.plans.index' => [
        'title' => 'Plans',
        'icon' => 'ti ti-packages',
        'description' => 'Manage subscription plans and pricing tiers.',
        'steps' => [
            'Plans are displayed as <strong>cards in a grid layout</strong> (up to 3 per row) with a colored status bar at the top.',
            'Each card shows: plan name, slug, status badge, price with billing cycle, description, and stats (<strong>subscribers</strong>, <strong>max users</strong>, <strong>trial days</strong>).',
            'Assigned features appear as green badges on each plan card.',
            'Click <strong>Edit Plan</strong> to modify any plan, or <strong>Delete</strong> to remove it (with confirmation).',
            'Click <strong>Create Plan</strong> to add a new pricing plan.',
        ],
    ],

    'superadmin.plans.create' => [
        'title' => 'Create Plan',
        'icon' => 'ti ti-packages',
        'description' => 'Define a new subscription plan with pricing, limits, and features.',
        'steps' => [
            'Fill in the plan <strong>name</strong>, <strong>slug</strong>, <strong>price</strong>, <strong>currency</strong>, and <strong>billing cycle</strong> (Monthly or Yearly).',
            'Optionally add a <strong>description</strong> visible to tenants.',
            'Set resource limits: <strong>max users</strong> (-1 for unlimited), <strong>max warehouses</strong>, <strong>max products</strong>, and <strong>trial days</strong>.',
            'Set the <strong>sort order</strong> for display positioning and check <strong>Active</strong> to make it available.',
            'Select which <strong>features</strong> to include, organized into Core, Professional, and Enterprise tiers.',
            'Click <strong>Create Plan</strong> to save or <strong>Cancel</strong> to go back.',
        ],
    ],

    'superadmin.plans.edit' => [
        'title' => 'Edit Plan',
        'icon' => 'ti ti-packages',
        'description' => 'Modify an existing subscription plan\'s pricing, limits, or features.',
        'steps' => [
            'All fields are pre-populated with the plan\'s current values.',
            'Update the plan <strong>name</strong>, <strong>slug</strong>, <strong>price</strong>, <strong>currency</strong>, or <strong>billing cycle</strong> as needed.',
            'Adjust resource limits: <strong>max users</strong>, <strong>max warehouses</strong>, <strong>max products</strong>, and <strong>trial days</strong>.',
            'Toggle individual <strong>features</strong> on or off across Core, Professional, and Enterprise tiers.',
            'Click <strong>Update Plan</strong> to save changes or <strong>Cancel</strong> to discard.',
        ],
    ],

    'superadmin.subscriptions.index' => [
        'title' => 'Subscriptions',
        'icon' => 'ti ti-receipt',
        'description' => 'Monitor all tenant subscriptions across the platform.',
        'steps' => [
            'View the 5 stat cards at the top: <strong>Total</strong>, <strong>Active</strong>, <strong>Trialing</strong>, <strong>Expired</strong>, and <strong>Terminated</strong>.',
            'The table shows each subscription\'s tenant, plan, status, trial end date, plan expiry, and billing period.',
            'Status badges indicate: <span class="badge bg-success text-white">Active</span>, <span class="badge bg-primary text-white">Trial</span>, <span class="badge bg-danger text-white">Expired</span>, or <span class="badge bg-secondary text-white">Terminated</span>.',
            'Use the dropdown actions on each row to <strong>Terminate</strong> an active subscription or <strong>Reactivate</strong> a terminated one (with confirmation prompts).',
            'Terminate disables the tenant\'s access; reactivate restores it.',
        ],
    ],

    'superadmin.features.index' => [
        'title' => 'Features',
        'icon' => 'ti ti-flag',
        'description' => 'Toggle feature availability across plans. Checked = enabled.',
        'steps' => [
            'The feature matrix table has one column per plan and rows for each feature.',
            'Features are grouped into 3 tiers: <strong>Core</strong> (12 features), <strong>Professional</strong> (9 features), and <strong>Enterprise</strong> (3 features).',
            'Each cell contains a <strong>toggle switch</strong> to enable or disable that feature for that plan.',
            'Click <strong>Save Feature Assignments</strong> to apply all changes. Changes take effect immediately.',
        ],
    ],

    'superadmin.billing.index' => [
        'title' => 'Billing',
        'icon' => 'ti ti-currency-dollar',
        'description' => 'Subscription orders and revenue.',
        'steps' => [
            'View the 4 stat cards: <strong>Total Revenue</strong>, <strong>Total Orders</strong>, <strong>Paid</strong>, and <strong>Pending</strong> orders.',
            'The table lists all orders with order number, tenant, plan, amount, status badge, and date.',
            'Status badges: <span class="badge bg-success text-white">Paid</span>, <span class="badge bg-warning text-white">Pending</span>, or <span class="badge bg-danger text-white">Failed</span>.',
            'Click the eye icon on any row to view the full order details.',
        ],
    ],

    'superadmin.billing.show' => [
        'title' => 'Order Details',
        'icon' => 'ti ti-currency-dollar',
        'description' => 'View detailed information about a specific billing order.',
        'steps' => [
            'The <strong>Order Information</strong> card shows order number, amount, status, change type, paid date, creation date, tenant, email, plan, and billing cycle.',
            'The <strong>Payment Details</strong> card (if available) displays all payment info fields dynamically, including gateway response data.',
            'Click <strong>Void Order</strong> to cancel an order that was created in error (only available for non-voided orders).',
            'Voiding is <strong>permanent</strong> and cannot be undone — a confirmation dialog will appear.',
        ],
    ],

    'superadmin.transactions.index' => [
        'title' => 'Transactions',
        'icon' => 'ti ti-arrows-exchange',
        'description' => 'All subscription payment transactions.',
        'steps' => [
            'View the 4 stat cards: <strong>Total</strong>, <strong>Paid</strong>, <strong>Pending</strong>, and <strong>Failed</strong> transactions.',
            'Use the <strong>Filter Transactions</strong> panel to filter by status (Paid, Pending, Failed, Expired, Canceled), date range, or search by order number / tenant name.',
            'The table shows order number, tenant, plan, amount, status, type (new/renewal/etc.), and date.',
            'Click <strong>Apply Filters</strong> to search or <strong>Reset</strong> to clear all filters.',
        ],
    ],

    'superadmin.pending-payments.index' => [
        'title' => 'Pending Payments',
        'icon' => 'ti ti-clock',
        'description' => 'Review and approve pending subscription payments.',
        'steps' => [
            'View the 2 stat cards: <strong>Pending Payments</strong> count and <strong>Total Pending Amount</strong>.',
            'The table lists pending orders with order number, tenant, plan, amount, and date.',
            'Click <strong>Approve</strong> to confirm a legitimate payment — this activates the tenant\'s subscription plan.',
            'Click <strong>Reject</strong> to cancel a payment — it will be marked as rejected.',
            'Both actions require confirmation via a browser dialog before executing.',
        ],
    ],

    'superadmin.payment-overview.index' => [
        'title' => 'Payment Overview',
        'icon' => 'ti ti-report-money',
        'description' => 'Revenue summary and transaction insights.',
        'steps' => [
            'View the 4 stat cards: <strong>Total Revenue</strong>, <strong>Total Orders</strong>, <strong>Paid</strong>, and <strong>Pending</strong> orders.',
            'The <strong>Monthly Revenue</strong> line chart (Chart.js) shows the last 12 months of revenue trends.',
            'The <strong>By Payment Gateway</strong> chart breaks down revenue by payment gateway with amounts and transaction counts listed below.',
            'The <strong>Recent Transactions</strong> table shows the latest orders — click <strong>View All</strong> to see the full transactions list.',
        ],
    ],

    'superadmin.invoices.index' => [
        'title' => 'Invoices',
        'icon' => 'ti ti-file-invoice',
        'description' => 'All paid subscription invoices.',
        'steps' => [
            'View the 2 stat cards: <strong>Total Invoices</strong> and <strong>Total Revenue</strong>.',
            'Use the <strong>Search Invoices</strong> panel to find invoices by order number or tenant name.',
            'The table shows invoice number, tenant, plan, amount, and paid date.',
            'Click the eye icon to view the full invoice in a print-ready format.',
        ],
    ],

    'superadmin.invoices.show' => [
        'title' => 'Invoice Details',
        'icon' => 'ti ti-file-invoice',
        'description' => 'View a detailed, print-ready invoice.',
        'steps' => [
            'The invoice displays the <strong>Invent-MAG</strong> header with invoice number and date.',
            'See <strong>Bill To</strong> information (tenant name and email) and <strong>Plan</strong> details (name and billing cycle).',
            'The line item table shows the plan subscription description and amount.',
            'The footer shows the <strong>total amount</strong>, payment status badge, and payment type if available.',
            'Click <strong>Print</strong> to open the browser print dialog for a physical copy or PDF export.',
        ],
    ],

    'superadmin.permissions.index' => [
        'title' => 'Permissions',
        'icon' => 'ti ti-shield-lock',
        'description' => 'View permissions and roles across the system.',
        'steps' => [
            'This is a <strong>read-only</strong> view — permissions and roles are managed via artisan commands or database seeders.',
            'The left card shows <strong>Permissions by Module</strong>, with each module listing its available permission badges.',
            'The right card shows <strong>Roles</strong>, with each role displaying its assigned permission badges.',
            'Use this to audit which roles have access to which features across the system.',
        ],
    ],

    'superadmin.settings.index' => [
        'title' => 'Application Settings',
        'icon' => 'ti ti-settings',
        'description' => 'Global application configuration written to the .env file.',
        'steps' => [
            'Use the <strong>left sidebar</strong> to switch between Application, Platform Currency, Billing Rate, and Preferences settings.',
            'Edit the <strong>Application Name</strong>, <strong>Application URL</strong>, and <strong>Debug Mode</strong> toggle.',
            'Some fields are <strong>read-only</strong> (Environment, Mail Driver, Mail From Address, Mail From Name) — these must be changed directly in the .env file.',
            'Click <strong>Save Application Settings</strong> to persist changes to the .env file.',
        ],
    ],

    'superadmin.settings.platform-currency' => [
        'title' => 'Platform Currency Settings',
        'icon' => 'ti ti-currency-dollar',
        'description' => 'Configure the currency symbol and formatting for the super admin panel. This does not affect tenant currency settings.',
        'steps' => [
            'Use the <strong>left sidebar</strong> to switch between Application, Platform Currency, Billing Rate, and Preferences settings.',
            'Select a <strong>Currency Code</strong> from the dropdown — the symbol and name auto-fill when a preset is selected.',
            'Set the <strong>Currency Symbol</strong> and choose <strong>position</strong> (prefix like $100 or suffix like 100$).',
            'Configure number formatting: <strong>decimal separator</strong>, <strong>thousand separator</strong>, and <strong>decimal places</strong>.',
            'The preview alert at the bottom shows a live example of how numbers will appear with current settings.',
            'Click <strong>Save Settings</strong> to apply changes.',
        ],
    ],

    'superadmin.settings.billing-rate' => [
        'title' => 'Billing Exchange Rate',
        'icon' => 'ti ti-cash',
        'description' => 'Set the USD → IDR rate used to convert plan prices to the IDR gross amount charged via the payment gateway at checkout.',
        'steps' => [
            'Use the <strong>left sidebar</strong> to switch between Application, Platform Currency, Billing Rate, and Preferences settings.',
            'Enter the <strong>exchange rate</strong> (1 USD = Rp ...). Plan prices are stored in USD.',
            'Review the <strong>price preview</strong> for Starter, Professional, and Enterprise plans at the current rate (before 12% PPN).',
            'Click <strong>Save Rate</strong> to apply — the new rate is used for all future checkout charges.',
            '<strong>Note:</strong> Keep the marketing site (<code>invent-mag-fe</code> <code>PUBLIC_USD_TO_IDR</code>) in sync with this rate.',
        ],
    ],

    'superadmin.settings.preferences' => [
        'title' => 'System Preferences',
        'icon' => 'ti ti-adjustments',
        'description' => 'Customize your interface, theme, notifications and more.',
        'steps' => [
            'Use the <strong>left sidebar</strong> to switch between Application, Platform Currency, Billing Rate, and Preferences settings.',
            '<strong>Interface Layout:</strong> Enable sidebar lock and sticky navbar for your preferred navigation behavior.',
            '<strong>Theme Configuration:</strong> Switch between Light and Dark themes, and toggle the theme switch button visibility.',
            '<strong>Notifications & Alerts:</strong> Configure sound and browser notifications, success message display, and notification duration.',
            '<strong>Session & Security:</strong> Set auto-logout timeout and enable "remember last page" for navigation persistence.',
            '<strong>Performance:</strong> Toggle UI animations, lazy loading, fluid layout, and set the auto-refresh rate.',
            '<strong>Language & Localization:</strong> Choose between English and Bahasa Indonesia.',
            '<strong>Advanced:</strong> Enable debug information, keyboard shortcuts, and help tooltips.',
            'Click <strong>Save Preferences</strong> to apply your customizations.',
        ],
    ],

    'superadmin.audit-log.index' => [
        'title' => 'Audit Log',
        'icon' => 'ti ti-file-text',
        'description' => 'System-wide activity log.',
        'steps' => [
            'Use the <strong>Filter</strong> form to narrow results by <strong>module</strong> (e.g., Tenant, Plan, Subscription) and <strong>action</strong> (e.g., Created, Updated, Deleted).',
            'The table shows each log entry\'s <strong>user</strong>, <strong>module</strong>, <strong>action</strong> (with colored badge), <strong>description</strong> (truncated), and <strong>date</strong>.',
            'Click the eye icon on any row to view the full log entry details.',
            'Click <strong>Reset</strong> to clear all filters and show all entries.',
        ],
    ],

    'superadmin.audit-log.show' => [
        'title' => 'Audit Log Entry',
        'icon' => 'ti ti-file-text',
        'description' => 'View detailed information about a specific audit log entry.',
        'steps' => [
            'The <strong>Activity Details</strong> card shows: user, user role, module, action badge, entity type, entity label, description, IP address, and timestamp.',
            'If the action involved data changes, <strong>Old Values</strong> and <strong>New Values</strong> JSON blocks are displayed side by side.',
            'The old/new values are shown in scrollable pre-formatted blocks for easy comparison.',
            'Use the back button to return to the audit log list.',
        ],
    ],

    'superadmin.backups.index' => [
        'title' => 'Backups',
        'icon' => 'ti ti-database',
        'description' => 'System and per-tenant backup management.',
        'steps' => [
            'View the 4 stat cards: <strong>Total Backup Files</strong>, <strong>System / Tenant</strong> counts, <strong>Total Storage Used</strong>, and <strong>Latest Backup</strong> date.',
            '<strong>System Backups</strong> tab: View, download, or delete system-wide backup ZIP files.',
            '<strong>Tenant Backups</strong> tab: View, download, or delete tenant-specific backups (shows tenant name and size).',
            '<strong>Create Tenant Backup</strong> tab: Select a tenant and type (Database Only or Full DB + Files) to create a tenant-specific backup.',
            '<strong>Restore Tenant</strong> tab: Upload a backup ZIP, select the target tenant, and optionally enter a password for encrypted backups. A warning confirmation will appear before restoring.',
            'Click <strong>Create System Backup</strong> at the top to generate a full system backup immediately.',
        ],
    ],

    'superadmin.cms.index' => [
        'title' => 'Landing Page CMS',
        'icon' => 'ti ti-world',
        'description' => 'Manage the public-facing website content for invent-mag-fe.',
        'alert' => 'Changes you save here are stored in <code>cms.json</code> and baked into the static landing page at build time.',
        'steps' => [
            'Edit any field below and click <strong>Save Changes</strong>.',
            'In local dev mode, changes appear immediately on the next page load.',
            'In production, changes go live after the next Vercel deployment (auto-triggered on Git push).',
        ],
        'env_table' => [
            ['label' => 'Local', 'value' => 'http://127.0.0.1.nip.io', 'where' => 'invent-mag-fe/.env'],
            ['label' => 'Production', 'value' => 'https://api.invent-mag.web.id', 'where' => 'Vercel Dashboard → Settings → Environment Variables'],
        ],
        'env_key' => 'PUBLIC_BACKEND_URL',
        'docs_file' => 'docs/landing-page-cms.md',
        'env_note' => 'The frontend needs <code>PUBLIC_BACKEND_URL</code> configured to fetch CMS data.',
    ],
];
