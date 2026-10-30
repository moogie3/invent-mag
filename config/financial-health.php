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

    // Inventory turnover: annualised COGS (6-month GL) / inventory GL
    // balance. Green >= 4 turns/year, amber >= 1.5, red below.
    'turnover' => [
        'good' => 4.0,
        'warn' => 1.5,
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
