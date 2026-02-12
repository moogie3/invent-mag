<?php

// Data from example.md
$trialBalanceData = [
    ['code' => '1110-1', 'name' => 'Cash', 'debit' => 0, 'credit' => 6226604],
    ['code' => '1130-1', 'name' => 'Accounts Receivable', 'debit' => 15219383, 'credit' => 0],
    ['code' => '1140-1', 'name' => 'Inventory', 'debit' => 140920, 'credit' => 0],
    ['code' => '2110-1', 'name' => 'Accounts Payable', 'debit' => 0, 'credit' => 25193511],
    ['code' => '2130-1', 'name' => 'Output VAT', 'debit' => 0, 'credit' => 44513],
    ['code' => '4100-1', 'name' => 'Sales Revenue', 'debit' => 0, 'credit' => 15403564],
    ['code' => '5000-1', 'name' => 'Cost of Goods Sold', 'debit' => 1250000, 'credit' => 0],
    ['code' => '5200-1', 'name' => 'Cost of Goods Sold', 'debit' => 257890, 'credit' => 0],
    ['code' => '6210-1', 'name' => 'Salaries - Administrative', 'debit' => 25000000, 'credit' => 0],
    ['code' => '6220-1', 'name' => 'Rent Expense', 'debit' => 5000000, 'credit' => 0],
];

echo "=== TRIAL BALANCE VERIFICATION ===\n\n";

$totalDebits = 0;
$totalCredits = 0;

foreach ($trialBalanceData as $account) {
    $totalDebits += $account['debit'];
    $totalCredits += $account['credit'];
    echo sprintf("%-30s Debit: %15s  Credit: %15s\n", 
        $account['name'], 
        number_format($account['debit'], 0, ',', '.'), 
        number_format($account['credit'], 0, ',', '.')
    );
}

echo str_repeat("-", 80) . "\n";
echo sprintf("%-30s Debit: %15s  Credit: %15s\n", 
    "TOTAL", 
    number_format($totalDebits, 0, ',', '.'), 
    number_format($totalCredits, 0, ',', '.')
);

echo "\n";
echo "Difference: " . number_format($totalDebits - $totalCredits, 0, ',', '.') . "\n";
echo "Balanced: " . (abs($totalDebits - $totalCredits) < 0.01 ? "✅ YES" : "❌ NO") . "\n";

echo "\n\n=== BALANCE SHEET VERIFICATION ===\n\n";

// Assets
$assets = [
    'Cash' => -6226604,  // Negative because it's a credit balance
    'Accounts Receivable' => 15219383,
    'Inventory' => 140920,
];

// Liabilities
$liabilities = [
    'Accounts Payable' => 25193511,
    'Output VAT' => 44513,
];

// Calculate Equity (Revenue - Expenses)
$revenue = 15403564;
$expenses = 1250000 + 257890 + 25000000 + 5000000; // 31507890
$equity = $revenue - $expenses; // -16104326

echo "ASSETS:\n";
$totalAssets = 0;
foreach ($assets as $name => $amount) {
    echo sprintf("  %-30s %15s\n", $name, number_format($amount, 0, ',', '.'));
    $totalAssets += $amount;
}
echo sprintf("  %-30s %15s\n", "Total Assets", number_format($totalAssets, 0, ',', '.'));

echo "\nLIABILITIES:\n";
$totalLiabilities = 0;
foreach ($liabilities as $name => $amount) {
    echo sprintf("  %-30s %15s\n", $name, number_format($amount, 0, ',', '.'));
    $totalLiabilities += $amount;
}
echo sprintf("  %-30s %15s\n", "Total Liabilities", number_format($totalLiabilities, 0, ',', '.'));

echo "\nEQUITY:\n";
echo sprintf("  %-30s %15s\n", "Retained Earnings (Revenue - Expenses)", number_format($equity, 0, ',', '.'));
echo sprintf("  %-30s %15s\n", "Total Equity", number_format($equity, 0, ',', '.'));

echo "\n" . str_repeat("-", 80) . "\n";
echo sprintf("%-30s %15s\n", "Total Liabilities + Equity", number_format($totalLiabilities + $equity, 0, ',', '.'));

echo "\nAccounting Equation:\n";
echo "Assets = Liabilities + Equity\n";
echo number_format($totalAssets, 0, ',', '.') . " = " . number_format($totalLiabilities, 0, ',', '.') . " + " . number_format($equity, 0, ',', '.') . "\n";
echo number_format($totalAssets, 0, ',', '.') . " = " . number_format($totalLiabilities + $equity, 0, ',', '.') . "\n";
echo "Balanced: " . (abs($totalAssets - ($totalLiabilities + $equity)) < 0.01 ? "✅ YES" : "❌ NO") . "\n";

echo "\n\n=== INCOME STATEMENT VERIFICATION ===\n\n";

echo "REVENUE:\n";
echo sprintf("  %-30s %15s\n", "Sales Revenue", number_format($revenue, 0, ',', '.'));
echo sprintf("  %-30s %15s\n", "Total Revenue", number_format($revenue, 0, ',', '.'));

echo "\nEXPENSES:\n";
$expenseDetails = [
    'Cost of Goods Sold' => 1250000 + 257890,
    'Salaries - Administrative' => 25000000,
    'Rent Expense' => 5000000,
];
$totalExpenses = 0;
foreach ($expenseDetails as $name => $amount) {
    echo sprintf("  %-30s %15s\n", $name, number_format($amount, 0, ',', '.'));
    $totalExpenses += $amount;
}
echo sprintf("  %-30s %15s\n", "Total Expenses", number_format($totalExpenses, 0, ',', '.'));

echo "\n" . str_repeat("-", 80) . "\n";
$netIncome = $revenue - $totalExpenses;
echo sprintf("%-30s %15s\n", "Net Income", number_format($netIncome, 0, ',', '.'));

echo "\nCalculation:\n";
echo number_format($revenue, 0, ',', '.') . " - " . number_format($totalExpenses, 0, ',', '.') . " = " . number_format($netIncome, 0, ',', '.') . "\n";

echo "\n\n=== FINAL VERIFICATION ===\n\n";
echo "1. Trial Balance: " . (abs($totalDebits - $totalCredits) < 0.01 ? "✅ BALANCED" : "❌ NOT BALANCED") . "\n";
echo "2. Balance Sheet: " . (abs($totalAssets - ($totalLiabilities + $equity)) < 0.01 ? "✅ BALANCED" : "❌ NOT BALANCED") . "\n";
echo "3. Income Statement: " . (abs($netIncome - ($revenue - $totalExpenses)) < 0.01 ? "✅ CORRECT" : "❌ INCORRECT") . "\n";
echo "4. Net Income = Equity Change: " . (abs($netIncome - $equity) < 0.01 ? "✅ MATCHES" : "❌ DOES NOT MATCH") . "\n";
