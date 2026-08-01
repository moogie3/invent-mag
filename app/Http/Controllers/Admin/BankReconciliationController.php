<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Services\ActivityLogService;
use App\Services\BankReconciliationService;
use Illuminate\Http\Request;

class BankReconciliationController extends Controller
{
    protected BankReconciliationService $service;

    public function __construct(BankReconciliationService $service)
    {
        $this->service = $service;
    }

    /**
     * Display the list of bank reconciliations.
     */
    public function index(Request $request)
    {
        $filters = [
            'month' => $request->get('month'),
            'year' => $request->get('year'),
        ];

        $data = $this->service->getIndexData($filters, (int) $request->get('per_page', 20));

        return view('admin.reports.bank-reconciliation.index', [
            'reconciliations' => $data['reconciliations'],
            'totalCompleted' => $data['totalCompleted'],
            'totalOpen' => $data['totalOpen'],
            'filters' => $filters,
        ]);
    }

    /**
     * Display the create/import form.
     */
    public function create()
    {
        $data = $this->service->getCreateData();

        return view('admin.reports.bank-reconciliation.create', $data);
    }

    /**
     * Import a bank statement and create a reconciliation.
     */
    public function store(Request $request)
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'bank' => 'required|in:bca,mandiri,bni,bri',
            'statement' => 'required|file|mimes:csv,txt|max:2048',
            'opening_balance' => 'required|numeric',
            'closing_balance' => 'required|numeric',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date|after_or_equal:period_start',
        ]);

        try {
            $content = $request->file('statement')->getContent();

            $reconciliation = $this->service->importStatement(
                (int) $request->account_id,
                $request->bank,
                $content,
                (float) $request->opening_balance,
                (float) $request->closing_balance,
                $request->period_start,
                $request->period_end
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to import the bank statement. Please check the file format and try again.');
        }

        try {
            app(ActivityLogService::class)->logCreate(
                ActivityLog::MODULE_BANK_RECONCILIATION,
                "Imported bank statement for {$request->bank} with {$reconciliation->statement_lines_count} lines"
            );
        } catch (\Throwable $e) {
            // never block the import for logging failure
        }

        return redirect()->route('admin.bank-reconciliation.show', $reconciliation)
            ->with('success', 'Bank statement imported. Review the matched lines and complete the reconciliation.');
    }

    /**
     * Display a reconciliation with its statement lines.
     */
    public function show(BankReconciliation $reconciliation)
    {
        $data = $this->service->getShowData($reconciliation);

        return view('admin.reports.bank-reconciliation.show', $data);
    }

    /**
     * Manually match a statement line to a journal entry.
     */
    public function matchItem(Request $request, BankReconciliation $reconciliation, BankReconciliationItem $item)
    {
        $request->validate([
            'journal_entry_id' => 'required|integer',
        ]);

        try {
            $this->service->manualMatch($reconciliation, $item, (int) $request->journal_entry_id);
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Could not match this line to the selected journal entry.');
        }

        return redirect()->back()->with('success', 'Statement line matched.');
    }

    /**
     * Unmatch a statement line from its journal entry.
     */
    public function unmatchItem(BankReconciliation $reconciliation, BankReconciliationItem $item)
    {
        try {
            $this->service->unmatch($item);
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Could not unmatch this statement line.');
        }

        return redirect()->back()->with('success', 'Statement line unmatched.');
    }

    /**
     * Mark the reconciliation as complete, locking its matched entries.
     */
    public function complete(BankReconciliation $reconciliation)
    {
        try {
            $this->service->complete($reconciliation);
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Could not complete this reconciliation.');
        }

        try {
            app(ActivityLogService::class)->logCreate(
                ActivityLog::MODULE_BANK_RECONCILIATION,
                "Completed bank reconciliation for {$reconciliation->account?->name}"
            );
        } catch (\Throwable $e) {
            // never block for logging failure
        }

        return redirect()->back()->with('success', 'Bank reconciliation completed. Matched journal entries are now locked.');
    }

    /**
     * Delete a reconciliation.
     */
    public function destroy(BankReconciliation $reconciliation)
    {
        try {
            $this->service->delete($reconciliation);
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Could not delete this reconciliation.');
        }

        return redirect()->route('admin.bank-reconciliation.index')
            ->with('success', 'Bank reconciliation deleted.');
    }
}
