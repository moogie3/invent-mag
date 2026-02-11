<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\ManualJournalEntryService;
use App\Services\AccountingAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class JournalEntryController extends Controller
{
    protected $manualJournalService;
    protected $auditService;

    public function __construct(ManualJournalEntryService $manualJournalService, AccountingAuditService $auditService)
    {
        $this->manualJournalService = $manualJournalService;
        $this->auditService = $auditService;
    }

    /**
     * Display the manual journal entries dashboard.
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'draft');
        $filters = [
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'search' => $request->get('search'),
        ];

        $draftEntries = $this->manualJournalService->getDraftEntries($filters);
        $postedEntries = $this->manualJournalService->getPostedEntries($filters);

        return view('admin.accounting.journal-entries.index', compact('draftEntries', 'postedEntries', 'tab', 'filters'));
    }

    /**
     * Display the create manual journal entry form.
     */
    public function create()
    {
        $accounts = $this->manualJournalService->getAvailableAccounts();
        return view('admin.accounting.journal-entries.create', compact('accounts'));
    }

    /**
     * Store a new manual journal entry.
     */
    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required|string|max:500',
            'date' => 'required|date',
            'transactions' => 'required|string',
            'notes' => 'nullable|string|max:1000',
        ]);

        $data = $request->all();
        $data['transactions'] = json_decode($request->transactions, true);

        try {
            $entry = $this->manualJournalService->create($data);
            return redirect()->route('admin.accounting.journal-entries.index')
                ->with('success', 'Journal entry created successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to create journal entry: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating journal entry: ' . $e->getMessage());
        }
    }

    /**
     * Display a journal entry.
     */
    public function show(JournalEntry $journalEntry)
    {
        $entry = $this->manualJournalService->getEntryDetails($journalEntry);
        $auditLogs = $this->auditService->getEntryAuditLogs($journalEntry);
        return view('admin.accounting.journal-entries.show', compact('entry', 'auditLogs'));
    }

    /**
     * Display the edit form for a draft journal entry.
     */
    public function edit(JournalEntry $journalEntry)
    {
        if ($journalEntry->status !== JournalEntry::STATUS_DRAFT) {
            return redirect()->route('admin.accounting.journal-entries.show', $journalEntry)
                ->with('error', 'Only draft entries can be edited.');
        }

        $entry = $this->manualJournalService->getEntryDetails($journalEntry);
        $accounts = $this->manualJournalService->getAvailableAccounts();
        return view('admin.accounting.journal-entries.edit', compact('entry', 'accounts'));
    }

    /**
     * Update a draft journal entry.
     */
    public function update(Request $request, JournalEntry $journalEntry)
    {
        $request->validate([
            'description' => 'required|string|max:500',
            'date' => 'required|date',
            'transactions' => 'required|string',
            'notes' => 'nullable|string|max:1000',
        ]);

        $data = $request->all();
        $data['transactions'] = json_decode($request->transactions, true);

        try {
            $entry = $this->manualJournalService->update($journalEntry, $data);
            return redirect()->route('admin.accounting.journal-entries.show', $entry)
                ->with('success', 'Journal entry updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update journal entry: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating journal entry: ' . $e->getMessage());
        }
    }

    /**
     * Post a draft journal entry.
     */
    public function post(Request $request, JournalEntry $journalEntry)
    {
        try {
            $entry = $this->manualJournalService->post($journalEntry);
            return redirect()->route('admin.accounting.journal-entries.show', $entry)
                ->with('success', 'Journal entry posted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to post journal entry: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error posting journal entry: ' . $e->getMessage());
        }
    }

    /**
     * Void a journal entry.
     */
    public function void(Request $request, JournalEntry $journalEntry)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $entry = $this->manualJournalService->void($journalEntry, $request->reason);
            return redirect()->route('admin.accounting.journal-entries.show', $entry)
                ->with('success', 'Journal entry voided successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to void journal entry: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error voiding journal entry: ' . $e->getMessage());
        }
    }

    /**
     * Reverse a journal entry.
     */
    public function reverse(Request $request, JournalEntry $journalEntry)
    {
        try {
            $reversalEntry = $this->manualJournalService->reverse($journalEntry, $request->notes);
            return redirect()->route('admin.accounting.journal-entries.show', $reversalEntry)
                ->with('success', 'Journal entry reversed successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to reverse journal entry: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error reversing journal entry: ' . $e->getMessage());
        }
    }

    /**
     * Delete a draft journal entry.
     */
    public function destroy(JournalEntry $journalEntry)
    {
        try {
            $this->manualJournalService->delete($journalEntry);
            return redirect()->route('admin.accounting.journal-entries.index')
                ->with('success', 'Journal entry deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete journal entry: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error deleting journal entry: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate a journal entry.
     */
    public function duplicate(Request $request, JournalEntry $journalEntry)
    {
        try {
            $newEntry = $this->manualJournalService->duplicate($journalEntry, $request->get('auto_post', false));
            return redirect()->route('admin.accounting.journal-entries.edit', $newEntry)
                ->with('success', 'Journal entry duplicated. Please review and post.');
        } catch (\Exception $e) {
            Log::error('Failed to duplicate journal entry: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error duplicating journal entry: ' . $e->getMessage());
        }
    }

    /**
     * Search accounts for autocomplete.
     */
    public function searchAccounts(Request $request)
    {
        $query = $request->input('query', '');
        $accounts = $this->manualJournalService->searchAccounts($query);
        return response()->json([
            'success' => true,
            'accounts' => $accounts->map(function ($account) {
                return [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                ];
            }),
        ]);
    }

    /**
     * Validate journal entry before submission.
     */
    public function validateEntry(Request $request)
    {
        $request->validate([
            'transactions' => 'required|string',
        ]);

        $transactions = json_decode($request->transactions, true);
        $debits = 0;
        $credits = 0;

        foreach ($transactions as $tx) {
            if (!isset($tx['account_code'], $tx['type'], $tx['amount'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Each transaction must have account_code, type, and amount.',
                ], 422);
            }

            if (!in_array($tx['type'], ['debit', 'credit'])) {
                return response()->json([
                    'success' => false,
                    'message' => "Invalid transaction type: {$tx['type']}",
                ], 422);
            }

            if ($tx['type'] === 'debit') {
                $debits += $tx['amount'];
            } else {
                $credits += $tx['amount'];
            }
        }

        if (round($debits, 2) !== round($credits, 2)) {
            return response()->json([
                'success' => false,
                'message' => "Journal entry is not balanced. Debits: {$debits}, Credits: {$credits}",
                'data' => [
                    'total_debit' => $debits,
                    'total_credit' => $credits,
                    'difference' => $debits - $credits,
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Journal entry is valid and balanced.',
            'data' => [
                'total_debit' => $debits,
                'total_credit' => $credits,
            ],
        ]);
    }
}
