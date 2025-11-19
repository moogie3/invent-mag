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
        $entries = JournalEntry::with('account')->paginate($perPage);
        return JournalEntryResource::collection($entries);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified journal entry.
     *
     * @urlParam journal_entry required The ID of the journal entry. Example: 1
     */
    public function show(JournalEntry $journal_entry)
    {
        return new JournalEntryResource($journal_entry->load('account'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
