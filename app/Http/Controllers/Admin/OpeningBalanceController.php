<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OpeningBalanceService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OpeningBalanceController extends Controller
{
    protected $openingBalanceService;

    public function __construct(OpeningBalanceService $openingBalanceService)
    {
        $this->openingBalanceService = $openingBalanceService;
    }

    /**
     * Display the opening balance setup page.
     */
    public function index(Request $request)
    {
        $accounts = $this->openingBalanceService->getAccountsForSetup();
        $balanceDate = $request->input('date', Carbon::now()->startOfMonth()->toDateString());
        
        $existingBalances = $this->openingBalanceService->getAllOpeningBalances()
            ->where('balance_date', $balanceDate)
            ->keyBy('account_id')
            ->toArray();

        $summary = $this->openingBalanceService->getSummary($balanceDate);

        return view('admin.accounting.opening-balance.index', [
            'accounts' => $accounts,
            'existingBalances' => $existingBalances,
            'balanceDate' => $balanceDate,
            'summary' => $summary,
        ]);
    }

    /**
     * Validate opening balances via AJAX.
     */
    public function validate(Request $request)
    {
        $balances = $request->input('balances', []);
        
        $validation = $this->openingBalanceService->validateBalances($balances);

        return response()->json($validation);
    }

    /**
     * Save opening balances.
     */
    public function store(Request $request)
    {
        $request->validate([
            'balance_date' => 'required|date',
            'balances' => 'required|array',
        ]);

        $balances = $request->input('balances', []);
        $balanceDate = $request->input('balance_date');
        $notes = $request->input('notes', '');

        $result = $this->openingBalanceService->saveOpeningBalances(
            $balances,
            $balanceDate,
            $notes
        );

        if ($result['success']) {
            return redirect()->route('admin.accounting.opening-balance.index')
                ->with('success', $result['message']);
        }

        return redirect()->back()
            ->with('error', $result['message'])
            ->withInput();
    }

    /**
     * Delete opening balances for a specific date.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'balance_date' => 'required|date',
        ]);

        $balanceDate = $request->input('balance_date');

        $result = $this->openingBalanceService->deleteOpeningBalances($balanceDate);

        if ($result['success']) {
            return redirect()->route('admin.accounting.opening-balance.index')
                ->with('success', $result['message']);
        }

        return redirect()->back()
            ->with('error', $result['message']);
    }

    /**
     * Get summary of opening balances.
     */
    public function summary(Request $request)
    {
        $balanceDate = $request->input('date', Carbon::now()->startOfMonth()->toDateString());
        
        $summary = $this->openingBalanceService->getSummary($balanceDate);

        return response()->json($summary);
    }
}
