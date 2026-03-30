# Accounting Module Setup Guide

## Table of Contents
1. [Introduction](#introduction)
2. [Prerequisites](#prerequisites)
3. [Understanding the Accounting Workflow](#understanding-the-accounting-workflow)
4. [Step-by-Step Setup](#step-by-step-setup)
5. [Understanding Opening Balance](#understanding-opening-balance)
6. [Accounting Menu Explained](#accounting-menu-explained)
7. [How Transactions Flow](#how-transactions-flow)
8. [Best Practices](#best-practices)
9. [Troubleshooting](#troubleshooting)

---

## Introduction

This guide explains how to set up and use the Accounting module in Invent-MAG. The accounting module is a double-entry bookkeeping system that automatically tracks all financial transactions from your sales, purchases, and other business activities.

### Why Do We Need Accounting?

As a business grows, simply tracking income and expenses is not enough. You need:

1. **Financial Accuracy** - Every transaction affects at least two accounts (double-entry)
2. **Compliance** - Generate accurate financial statements for tax and legal purposes
3. **Business Insights** - Understand where money comes from and where it goes
4. **Audit Trail** - Track every financial decision with complete history

---

## Prerequisites

### Plan Requirement

The Accounting module is available only on the **Professional plan** or higher.

| Plan | Accounting Access |
|------|-------------------|
| Starter | ❌ Not available |
| Professional | ✅ Available |
| Enterprise | ✅ Available |

### Permissions Required

Users need the following permissions to access accounting features:

| Permission | Description |
|------------|-------------|
| `view-accounting` | View accounting menus and reports |
| `view-chart-of-accounts` | View Chart of Accounts |
| `edit-chart-of-accounts` | Edit Chart of Accounts |
| `manage-opening-balance` | Set and manage opening balances |
| `view-manual-journal` | View and create manual journal entries |
| `view-general-ledger` | View General Ledger |
| `view-trial-balance` | View Trial Balance |

---

## Understanding the Accounting Workflow

### The Accounting Cycle

```
┌─────────────────────────────────────────────────────────────────┐
│                    ACCOUNTING WORKFLOW                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│   1. INITIAL SETUP                                             │
│      ├── Reset Chart of Accounts (create default accounts)      │
│      ├── Configure Accounting Integration                        │
│      └── Set Opening Balances (if migrating from other system)  │
│                                                                 │
│   2. DAILY OPERATIONS                                           │
│      ├── Sales Orders create journal entries                    │
│      ├── Purchase Orders create journal entries                 │
│      ├── Payments are recorded                                  │
│                                                                 │
│   3. PERIODIC REVIEW                                            │
│      ├── View General Ledger                                    │
│      ├── Review Journal Entries                                 │
│      ├── Check Trial Balance                                    │
│                                                                 │
│   4. FINANCIAL REPORTS                                          │
│      ├── Income Statement (Profit & Loss)                       │
│      ├── Balance Sheet                                          │
│      └── Other Reports                                          │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Why This Workflow Matters

1. **You cannot skip Step 1**: Without a Chart of Accounts, the system doesn't know which accounts to use for transactions.

2. **Opening Balance is crucial for accuracy**: If you've been in business for 6 months before starting to use this system, your financial reports will be incorrect without opening balances.

3. **Transactions are automatic**: Once configured, you don't need to manually create journal entries for every sale or purchase.

---

## Step-by-Step Setup

### Step 1: Access Accounting Settings

**Navigation:** `Settings → Accounting`

```
URL: /admin/settings/accounting
```

This page allows you to:
- Reset the Chart of Accounts to default
- Configure accounting integrations (map accounts to business actions)

### Step 2: Reset Chart of Accounts

**Why is this necessary?**

When you create a new tenant (workspace), the accounting database tables are empty. You need to create the default Chart of Accounts before any financial transactions can be recorded.

**How to do it:**

1. Go to **Settings → Accounting**
2. Click the **"Reset to Default"** button
3. A confirmation modal will appear warning that this will delete all existing accounts and journal entries
4. Click **"Confirm"** to proceed

**What happens:**

The system creates the following account structure:

#### Assets (Code: 1000 series)
| Code | Account Name | Description |
|------|--------------|-------------|
| 1100 | Current Assets | Parent account for liquid assets |
| 1110 | Cash | Cash on hand |
| 1120 | Bank | Money in bank accounts |
| 1130 | Accounts Receivable | Money owed by customers |
| 1135 | Allowance for Doubtful Accounts | Contra-asset for bad debts |
| 1140 | Inventory | Goods available for sale |
| 1150 | Prepaid Expenses | Expenses paid in advance |
| 1160 | Input VAT | VAT paid on purchases |
| 1200 | Fixed Assets | Parent for long-term assets |
| 1210 | Land | Company land |
| 1220 | Buildings | Company buildings |
| 1225 | Accumulated Depreciation - Buildings | Contra-asset |
| 1230 | Equipment | Office and operational equipment |
| 1235 | Accumulated Depreciation - Equipment | Contra-asset |

#### Liabilities (Code: 2000 series)
| Code | Account Name | Description |
|------|--------------|-------------|
| 2100 | Current Liabilities | Parent for short-term debts |
| 2110 | Accounts Payable | Money owed to suppliers |
| 2120 | Accrued Expenses | Expenses incurred but not yet paid |
| 2130 | Output VAT | VAT collected from customers |
| 2200 | Long-Term Liabilities | Parent for long-term debts |
| 2210 | Bank Loans | Loans with maturity > 1 year |

#### Equity (Code: 3000 series)
| Code | Account Name | Description |
|------|--------------|-------------|
| 3010 | Opening Balance Equity | Used for balancing opening entries |
| 3100 | Owner's Equity | Owner's investment in the business |
| 3200 | Retained Earnings | Accumulated profits retained in business |

#### Revenue (Code: 4000 series)
| Code | Account Name | Description |
|------|--------------|-------------|
| 4100 | Sales Revenue | Income from sales |
| 4110 | Sales Returns | Contra-revenue for returns |
| 4120 | Sales Discounts | Contra-revenue for discounts |

#### Cost of Goods Sold (Code: 5000 series)
| Code | Account Name | Description |
|------|--------------|-------------|
| 5100 | Purchases | Cost of goods bought |
| 5110 | Freight-In | Shipping costs on purchases |
| 5120 | Purchase Returns | Contra-COGS for returns |
| 5130 | Purchase Discounts | Contra-COGS for discounts |
| 5200 | Cost of Goods Sold | Total COGS for the period |

#### Expenses (Code: 6000 series)
| Code | Account Name | Description |
|------|--------------|-------------|
| 6100 | Selling Expenses | Sales-related expenses |
| 6110 | Salaries - Selling | Sales team salaries |
| 6120 | Advertising Expenses | Marketing and advertising |
| 6200 | Administrative Expenses | General operations |
| 6210 | Salaries - Administrative | Admin team salaries |
| 6220 | Rent Expense | Office/store rent |
| 6230 | Utility Expenses | Electricity, water, internet |
| 6240 | Depreciation Expense | Asset depreciation |

---

### Step 3: Configure Accounting Integration

**Why is this necessary?**

The system needs to know which specific accounts to use when recording transactions. For example, when a sale is made, the system needs to know which "Sales Revenue" account to credit.

**How to do it:**

1. Go to **Settings → Accounting**
2. In the "Accounting Integration" section, fill in the following fields:

| Field | What to Select | Why |
|-------|----------------|-----|
| Sales Revenue Account | 4100 - Sales Revenue | Income from sales |
| Accounts Receivable Account | 1130 - Accounts Receivable | Customer debt tracking |
| Cost of Goods Sold Account | 5200 - Cost of Goods Sold | Cost of sold items |
| Inventory Account | 1140 - Inventory | Inventory tracking |
| Accounts Payable Account | 2110 - Accounts Payable | Supplier debt tracking |
| Cash Account | 1110 or 1120 - Cash/Bank | Payment tracking |

**Important:** These settings affect all future transactions. Changing them won't affect past transactions.

---

### Step 4: Set Opening Balances (CRITICAL)

**Why is this necessary?**

If your business has been operating before you started using Invent-MAG, your financial reports will show inaccurate data unless you enter your starting balances.

#### Example Scenario:

You started your business on January 1, 2024. Today is July 1, 2024. You've been tracking sales in a simple Excel file:
- Total Sales: Rp 100,000,000
- Total Purchases: Rp 60,000,000
- Cash in Bank: Rp 50,000,000
- Accounts Receivable: Rp 10,000,000
- Accounts Payable: Rp 5,000,000

Without setting opening balances:
- Your Income Statement will show only July transactions
- Your Balance Sheet will show Rp 0 for Assets and Equity
- Your Trial Balance won't balance

With opening balances:
- All accounts show correct starting amounts
- Reports accurately reflect your business position
- Future transactions build on correct foundation

---

## Understanding Opening Balance

### How It Works

#### 1. The Balance Equation

In accounting, the fundamental equation is:

```
Assets = Liabilities + Equity
```

This must always be true. When setting opening balances, **Total Debits must equal Total Credits**.

#### 2. Account Types and Balance Direction

| Account Type | Normal Balance | Enter As |
|--------------|---------------|----------|
| Assets | Debit (Positive) | Debit |
| Liabilities | Credit (Negative) | Credit |
| Equity | Credit (Negative) | Credit |
| Revenue | Credit (Negative) | Credit |
| Expenses | Debit (Positive) | Debit |

#### 3. Setting Opening Balances in Invent-MAG

**Navigation:** `Accounting → Opening Balance`

**Process:**

1. **Select Balance Date**
   - Choose the date from which you're starting to track finances
   - Typically: January 1 of the current year, or the date you started business

2. **Enter Debit Amounts**
   - For Asset accounts (Cash, Bank, Accounts Receivable, Inventory)
   - Enter the amount in the "Debit" column

3. **Enter Credit Amounts**
   - For Liability accounts (Accounts Payable)
   - For Equity accounts (Owner's Equity, Retained Earnings)
   - Enter the amount in the "Credit" column

4. **Validation**
   - The system checks that Total Debits = Total Credits
   - If they don't match, you cannot save

5. **Auto-Balancing**
   - If debits don't equal credits, the system automatically creates a balancing entry to "Opening Balance Equity"

#### Example: Setting Opening Balance

```
Current Business Position:
- Cash: Rp 50,000,000
- Accounts Receivable: Rp 10,000,000
- Inventory: Rp 20,000,000
- Accounts Payable: Rp 5,000,000
- Owner's Equity: Rp 75,000,000

Entry in Opening Balance Setup:

Account                    | Type    | Debit       | Credit
--------------------------|---------|-------------|------------
Cash (1110)               | Asset   | 50,000,000  |
Accounts Receivable (1130)| Asset   | 10,000,000  |
Inventory (1140)          | Asset   | 20,000,000  |
Accounts Payable (2110)   | Liability|            | 5,000,000
Owner's Equity (3100)     | Equity  |             | 75,000,000
                          |         |             |
TOTAL                     |         | 80,000,000  | 80,000,000 ✓
```

The system will automatically create a journal entry like:

```
Journal Entry - Opening Balance
Date: January 1, 2024
Entry Type: Opening Balance

Account                    | Debit       | Credit
---------------------------|-------------|------------
Cash                       | 50,000,000  |
Accounts Receivable        | 10,000,000  |
Inventory                  | 20,000,000  |
Accounts Payable           |             | 5,000,000
Owner's Equity             |             | 75,000,000
                           |             |
TOTAL                      | 80,000,000  | 80,000,000 ✓
```

---

## Accounting Menu Explained

### General Ledger

**Purpose:** View detailed transaction history for each account

**Use when:**
- Investigating a specific transaction
- Auditing account activity
- Finding the source of a discrepancy

**Navigation:** `Accounting → General Ledger`

---

### Journal

**Purpose:** View all journal entries in chronological order

**Use when:**
- Reviewing all transactions for a specific date range
- Finding all changes to the books

**Navigation:** `Accounting → Journal`

---

### Manual Journal Entries

**Purpose:** Create journal entries manually for adjustments

**Common uses:**
- Recording depreciation
- Accruing expenses
- Correcting errors
- Recording owner investments

**Important:** These entries affect financial reports. Use with caution.

**Navigation:** `Accounting → Manual Journal Entries`

---

### Chart of Accounts

**Purpose:** View and manage all accounts in the system

**Use when:**
- Adding new accounts
- Editing account details
- Deactivating accounts

**Navigation:** `Accounting → Chart of Accounts`

---

### Trial Balance

**Purpose:** Verify that debits equal credits

**Use when:**
- At the end of each accounting period
- Before closing the books
- Troubleshooting不平衡 issues

**What it shows:** All accounts with their debit or credit balances

**Navigation:** `Accounting → Trial Balance`

---

### Opening Balance

**Purpose:** Set initial account balances when starting to use the system

**Use when:**
- First setting up accounting
- Starting a new fiscal year
- Migrating from another system

**Navigation:** `Accounting → Opening Balance`

---

## How Transactions Flow

### Automatic Journal Entry Creation

Invent-MAG automatically creates journal entries for the following transactions:

### 1. Sales Order

When you create a sales order:

```
Transaction: Sale of Rp 1,000,000

Journal Entry:
┌─────────────────────────────────────┬──────────┬──────────┐
│ Account                             │ Debit    │ Credit   │
├─────────────────────────────────────┼──────────┼──────────┤
│ Accounts Receivable (1130)          │ 1,100,000│          │
│   Sales Revenue (4100)              │          │ 1,000,000│
│   Output VAT (2130)                 │          │  100,000 │
└─────────────────────────────────────┴──────────┴──────────┘
```

### 2. Payment Received

When payment is received:

```
Transaction: Receive Rp 1,100,000 from customer

Journal Entry:
┌─────────────────────────────────────┬──────────┬──────────┐
│ Account                             │ Debit    │ Credit   │
├─────────────────────────────────────┼──────────┼──────────┤
│ Cash/Bank (1110/1120)              │ 1,100,000│          │
│   Accounts Receivable (1130)        │          │ 1,100,000│
└─────────────────────────────────────┴──────────┴──────────┘
```

### 3. Purchase Order

When you create a purchase order:

```
Transaction: Purchase of Rp 500,000 worth of inventory

Journal Entry:
┌─────────────────────────────────────┬──────────┬──────────┐
│ Account                             │ Debit    │ Credit   │
├─────────────────────────────────────┼──────────┼──────────┤
│ Purchases (5100)                    │  500,000 │          │
│ Input VAT (1160)                    │   50,000 │          │
│   Accounts Payable (2110)           │          │  550,000 │
└─────────────────────────────────────┴──────────┴──────────┘
```

### 4. Payment to Supplier

When paying supplier:

```
Transaction: Pay Rp 550,000 to supplier

Journal Entry:
┌─────────────────────────────────────┬──────────┬──────────┐
│ Account                             │ Debit    │ Credit   │
├─────────────────────────────────────┼──────────┼──────────┤
│ Accounts Payable (2110)            │ 550,000  │          │
│   Cash/Bank (1110/1120)             │          │ 550,000  │
└─────────────────────────────────────┴──────────┴──────────┘
```

---

## Best Practices

### 1. Set Up Opening Balances Immediately

If you're migrating from another system or starting mid-year, set opening balances on day one. It's much harder to go back later.

### 2. Review Trial Balance Monthly

Before closing each month, run the Trial Balance to ensure your books are in order.

### 3. Don't Delete Transactions

Never delete sales orders or payments to "undo" them. Instead:
- For returns: Create a sales return
- For corrections: Create adjusting journal entries

### 4. Keep Opening Balance Equity Clean

The Opening Balance Equity account is used for balancing. Don't manually post to it except through the Opening Balance setup.

### 5. Regular Account Reconciliation

- Reconcile bank statements monthly
- Reconcile Accounts Receivable with customer statements
- Reconcile Accounts Payable with supplier statements

---

## Troubleshooting

### Problem: Trial Balance doesn't balance

**Possible causes:**
1. Transactions with incorrect debit/credit entries
2. Missing opening balances
3. Manual entries that don't balance

**Solution:**
- Review recent manual journal entries
- Check Opening Balance setup
- Run through each account systematically

---

### Problem: Opening Balance won't save

**Possible causes:**
1. Total Debits ≠ Total Credits
2. No accounts selected (all amounts are zero)
3. Opening Balance Equity account doesn't exist

**Solution:**
- Ensure debits equal credits
- Reset Chart of Accounts first
- Enter at least one account balance

---

### Problem: Financial reports look wrong

**Possible causes:**
1. Opening balances not set
2. Accounting integration not configured
3. Transactions posted to wrong accounts

**Solution:**
- Set opening balances if migrating from another system
- Verify accounting integration settings
- Review individual account transactions

---

## Quick Reference Card

### For New Setup:

```
Day 1:
□ Upgrade to Professional plan
□ Go to Settings → Accounting
□ Click "Reset to Default"
□ Configure Accounting Integration
□ Set Opening Balances (if applicable)

Regular:
□ Create Sales Orders (auto-journals created)
□ Create Purchase Orders (auto-journals created)
□ Record Payments (auto-journals created)
□ Monthly: Review Trial Balance
□ Monthly: Generate financial reports
```

---

## Support

If you encounter issues:
1. Check this documentation
2. Review Trial Balance for errors
3. Contact support with specific error messages

---

*Document Version: 1.0*
*Last Updated: 2026*
