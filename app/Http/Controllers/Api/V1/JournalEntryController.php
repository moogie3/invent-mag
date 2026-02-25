<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreJournalEntryRequest;
use App\Http\Requests\Api\V1\UpdateJournalEntryRequest;
use App\Http\Resources\JournalEntryResource;
use App\Models\JournalEntry;
use Illuminate\Http\Request;

/**
 * @group Journal Entries
 *
 * APIs for managing journal entries
 */
class JournalEntryController extends Controller
{
    protected $accountingService;

    public function __construct(\App\Services\AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
        $this->middleware('permission:view-journal-entries')->only(['index', 'show']);
        $this->middleware('permission:create-journal-entries')->only(['store']);
        $this->middleware('permission:edit-journal-entries')->only(['update']);
        $this->middleware('permission:delete-journal-entries')->only(['destroy']);
    }

    /**
     * Display a listing of the journal entries.
     *
     * @group Journal Entries
     * @authenticated
     * @queryParam per_page int The number of entries to return per page. Defaults to 15. Example: 25
     *
     * @response 200 scenario="Success" {"data":[{"id":1,"date":"2025-11-28","description":"Initial investment",...}],"links":{...},"meta":{...}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $entries = JournalEntry::with('transactions.account')->paginate($perPage);
        return JournalEntryResource::collection($entries);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @group Journal Entries
     * @authenticated
     * @bodyParam date date required The date of the journal entry. Example: 2025-11-28
     * @bodyParam description string required The description of the journal entry. Example: Initial investment
     * @bodyParam sourceable_id integer The ID of the sourceable model. Example: 1
     * @bodyParam sourceable_type string The type of the sourceable model. Example: App\\Models\\User
     *
     * @response 201 scenario="Success" {"data":{"id":1,"date":"2025-11-28","description":"Initial investment",...}}
     * @response 422 scenario="Validation Error" {"message":"The date field is required.","errors":{"date":["The date field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function store(StoreJournalEntryRequest $request)
    {
        $validated = $request->validated();
        $transactions = json_decode($validated['transactions'], true);

        $journalEntry = $this->accountingService->createJournalEntry(
            $validated['description'],
            \Carbon\Carbon::parse($validated['date']),
            $transactions,
            auth()->user()
        );

        return new JournalEntryResource($journalEntry);
    }

    /**
     * Display the specified journal entry.
     *
     * @group Journal Entries
     * @authenticated
     * @urlParam journal_entry required The ID of the journal entry. Example: 1
     *
     * @response 200 scenario="Success" {"data":{"id":1,"date":"2025-11-28","description":"Initial investment",...}}
     * @response 404 scenario="Not Found" {"message": "Journal entry not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function show(JournalEntry $journal_entry)
    {
        return new JournalEntryResource($journal_entry->load('transactions.account'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @group Journal Entries
     * @authenticated
     * @urlParam journal_entry required The ID of the journal entry. Example: 1
     * @bodyParam date date required The date of the journal entry. Example: 2025-11-28
     * @bodyParam description string required The description of the journal entry. Example: Initial investment
     * @bodyParam sourceable_id integer The ID of the sourceable model. Example: 1
     * @bodyParam sourceable_type string The type of the sourceable model. Example: App\\Models\\User
     *
     * @response 200 scenario="Success" {"data":{"id":1,"date":"2025-11-28","description":"Initial investment (Updated)",...}}
     * @response 404 scenario="Not Found" {"message": "Journal entry not found."}
     * @response 422 scenario="Validation Error" {"message":"The date field is required.","errors":{"date":["The date field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function update(UpdateJournalEntryRequest $request, JournalEntry $journal_entry)
    {
        $validated = $request->validated();

        $journal_entry->update($validated);

        return new JournalEntryResource($journal_entry);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @group Journal Entries
     * @authenticated
     * @urlParam journal_entry required The ID of the journal entry. Example: 1
     *
     * @response 204 scenario="Success"
     * @response 404 scenario="Not Found" {"message": "Journal entry not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function destroy(JournalEntry $journal_entry)
    {
        $journal_entry->delete();

        return response()->noContent();
    }
}