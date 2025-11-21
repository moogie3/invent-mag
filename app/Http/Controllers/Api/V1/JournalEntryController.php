<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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
    /**
     * Display a listing of the journal entries.
     *
     * @queryParam per_page int The number of entries to return per page. Defaults to 15. Example: 25
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
     * @response 201 scenario="Success"
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'description' => 'required|string',
            'sourceable_id' => 'nullable|integer',
            'sourceable_type' => 'nullable|string',
        ]);

        $journalEntry = JournalEntry::create($validated);

        return new JournalEntryResource($journalEntry);
    }

    /**
     * Display the specified journal entry.
     *
     * @urlParam journal_entry required The ID of the journal entry. Example: 1
     */
    public function show(JournalEntry $journal_entry)
    {
        return new JournalEntryResource($journal_entry->load('transactions.account'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @response 200 scenario="Success"
     */
    public function update(Request $request, JournalEntry $journal_entry)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'description' => 'required|string',
            'sourceable_id' => 'nullable|integer',
            'sourceable_type' => 'nullable|string',
        ]);

        $journal_entry->update($validated);

        return new JournalEntryResource($journal_entry);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @response 204 scenario="Success"
     */
    public function destroy(JournalEntry $journal_entry)
    {
        $journal_entry->delete();

        return response()->noContent();
    }
}
