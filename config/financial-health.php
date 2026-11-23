<?php

/*
|--------------------------------------------------------------------------
| Financial Health
|--------------------------------------------------------------------------
|
| Thresholds behind every red/amber/green on the Financial Health strip and
| page (App\Services\FinancialHealthService). Values are ratios unless the
| key says otherwise; every status the service produces can be traced back
| to a rule below, so "why is this red?" always has one stated answer.
|
*/

return [

    // Trend window (months). All "vs average" comparisons use COMPLETE
    // months only — the in-progress month is excluded so day-2-of-month
    // never looks like a revenue crash.
    'months' => 6,

    // Weights for the 3-month weighted estimate (oldest -> newest) used in
    // the next-month projection: (1*m1 + 2*m2 + 3*m3) / 6.
    'weights' => [1, 2, 3],

    // Cash: how many months of spending the current cash balance covers
    // (cash / average monthly expenses over the last 3 complete months).
    'cash' => [
        'cover_good' => 3.0,
        'cover_warn' => 1.0,
    ],

    // Revenue: last complete month vs the average of the 3 before it.
    'revenue' => [
        'good' => 0.95, // >= 95% of the recent average is still healthy
        'warn' => 0.80, // below 80% is a problem
    ],

    // Profit margin over the trailing 3 complete months (GL, posted only).
    'margin' => [
        'good' => 0.10, // 10%+ net margin
        'warn' => 0.00, // any positive margin survives; negative is red
    ],

    // Purchases vs sales over the trailing 3 complete months (subledger).
    'purchases' => [
        'good' => 1.00, // spending within means
        'warn' => 1.15, // slightly over revenue is tolerable
    ],

    // Receivables: share of total outstanding that is 31+ days overdue.
    'receivables' => [
        'warn' => 0.10,
        'danger' => 0.25,
    ],

    // Payables: overdue 31+ payables relative to cash on hand
    // (1.0 = overdue bills exactly equal the cash you hold).
    'payables' => [
        'warn' => 1.0,
        'danger' => 1.5,
    ],

    // Payroll: paid payroll vs GL revenue (trailing 3 months). Above 1.0
    // the wage bill alone exceeds income.
    'payroll' => [
        'burden_danger' => 1.0,
    ],

    // ── Period filter ──────────────────────────────────────────────
    // window_months: complete months the cards aggregate when no filter is
    // active (the trailing quarter the page has always opened on).
    // period_max_months: hard cap on a custom range, so one selection can
    // never ask the service to scan an unbounded stretch of ledger.
    'window_months' => 3,
    'period_max_months' => 24,

    // Debt-to-Equity: total liabilities / total equity from posted GL
    // (equity includes revenue − expense, exactly what the balance sheet
    // reports). At 1.0 lenders and owners have equal claims; past 2.0 the
    // business is mostly funded by debt.
    'debt_to_equity' => [
        'good' => 1.0,
        'warn' => 2.0,
    ],

    // Days Sales Outstanding: current receivables balance ÷ revenue over
    // the trailing 3 complete months, scaled to that window's day count.
    // 60 days ≈ two months of sales still outstanding; 90 ≈ a full quarter.
    'dso' => [
        'good' => 60,
        'warn' => 90,
    ],

    // Days Payable Outstanding: open payables ÷ purchases over the
    // selected window, scaled to that window's day count, so the figure
    // reads as "unpaid bills equal about N days of purchases". Mirrors
    // the DSO bands; actual overdue tracking stays on the payables card.
    'dpo' => [
        'good' => 60,
        'warn' => 90,
    ],

    // Collection: cash collected ÷ invoices booked across the trailing
    // ledger window (GL 1130). 1.0 = every invoiced rupiah has arrived.
    'collection' => [
        'good' => 0.90,
        'warn' => 0.75,
    ],

    // Unposted (draft) journal entries. Zero means every GL-derived card
    // on the page reads final; one draft and the numbers are provisional.
    'draft_jes' => [
        'warn' => 1,
    ],

    // Inventory turnover: annualised COGS (6-month GL) / inventory GL
    // balance. Green >= 4 turns/year, amber >= 1.5, red below.
    'turnover' => [
        'good' => 4.0,
        'warn' => 1.5,
    ],

    // Gross margin: (revenue − COGS) / revenue over the trailing 3 complete
    // months — the unit economics BEFORE overheads. Distinct from the net
    // 'margin' above (which is after every expense). 40%+ is a healthy
    // product; under 20% the product itself is barely earning.
    'gross_margin' => [
        'good' => 0.40,
        'warn' => 0.20,
    ],

    // Liquidity: current assets (GL 11xx) vs current liabilities (GL 21xx).
    // 'current_ratio' good >= 1.5, warn >= 1.0 (below 1.0 = can't cover
    // short-term bills). 'working_capital' bad below 0 (owed more than
    // collectable within the year).
    'liquidity' => [
        'current_ratio_good' => 1.5,
        'current_ratio_warn' => 1.0,
    ],

    // Runway: months of cash left while burning at the current net rate.
    'runway' => [
        'warn' => 6.0,
        'bad' => 3.0,
    ],

    // Collection ratio (GL: AR credits / AR debits over the window) — what
    // share of booked receivables actually turned into money. When AR
    // activity is negligible (cash business) this fallback is used.
    'collection_ratio_fallback' => 1.0,

    // GL accounts that carry payroll (per PayrollService postings):
    // 6300 payroll expense, 2140 accrued payroll liability. These are
    // excluded from the "operations" expense series so payroll is never
    // double counted (it enters the projection from the schedule instead).
    'payroll_gl_codes' => [6300],

    // Noise floors — an insight is skipped when both its absolute size is
    // under 'amount' (in currency units) and its relative move is under
    // 'percent'. Keeps the narrative to what actually matters.
    'noise' => [
        'amount' => 100000.0,
        'percent' => 0.05,
    ],

    // Maximum narrative lines (the closing outlook statement is always
    // added on top and does not count against this).
    'narrative_max' => 6,
];
