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
     * @group Journal Entries
     * @authenticated
     * @queryParam per_page int The number of entries to return per page. Defaults to 15. Example: 25
     *
     * @responseField data object[] A list of journal entries.
     * @responseField data[].id integer The ID of the journal entry.
     * @responseField data[].date string The date of the journal entry.
     * @responseField data[].description string The description of the journal entry.
     * @responseField data[].sourceable_type string The type of the sourceable model.
     * @responseField data[].sourceable_id integer The ID of the sourceable model.
     * @responseField data[].created_at string The date and time the journal entry was created.
     * @responseField data[].updated_at string The date and time the journal entry was last updated.
     * @responseField data[].transactions object[] A list of transactions for the journal entry.
     * @responseField links object Links for pagination.
     * @responseField links.first string The URL of the first page.
     * @responseField links.last string The URL of the last page.
     * @responseField links.prev string The URL of the previous page.
     * @responseField links.next string The URL of the next page.
     * @responseField meta object Metadata for pagination.
     * @responseField meta.current_page integer The current page number.
     * @responseField meta.from integer The starting number of the results on the current page.
     * @responseField meta.last_page integer The last page number.
     * @responseField meta.path string The URL path.
     * @responseField meta.per_page integer The number of results per page.
     * @responseField meta.to integer The ending number of the results on the current page.
     * @responseField meta.total integer The total number of results.
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
     * @responseField id integer The ID of the journal entry.
     * @responseField date string The date of the journal entry.
     * @responseField description string The description of the journal entry.
     * @responseField sourceable_type string The type of the sourceable model.
     * @responseField sourceable_id integer The ID of the sourceable model.
     * @responseField created_at string The date and time the journal entry was created.
     * @responseField updated_at string The date and time the journal entry was last updated.
     */
    public function store(\App\Http\Requests\Api\V1\StoreJournalEntryRequest $request)
    {
        $validated = $request->validated();

        $journalEntry = JournalEntry::create($validated);

        return new JournalEntryResource($journalEntry);
    }

    /**
     * Display the specified journal entry.
     *
     * @group Journal Entries
     * @authenticated
     * @urlParam journal_entry required The ID of the journal entry. Example: 1
     *
     * @responseField id integer The ID of the journal entry.
     * @responseField date string The date of the journal entry.
     * @responseField description string The description of the journal entry.
     * @responseField sourceable_type string The type of the sourceable model.
     * @responseField sourceable_id integer The ID of the sourceable model.
     * @responseField created_at string The date and time the journal entry was created.
     * @responseField updated_at string The date and time the journal entry was last updated.
     * @responseField transactions object[] A list of transactions for the journal entry.
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
     * @responseField id integer The ID of the journal entry.
     * @responseField date string The date of the journal entry.
     * @responseField description string The description of the journal entry.
     * @responseField sourceable_type string The type of the sourceable model.
     * @responseField sourceable_id integer The ID of the sourceable model.
     * @responseField created_at string The date and time the journal entry was created.
     * @responseField updated_at string The date and time the journal entry was last updated.
     */
    public function update(\App\Http\Requests\Api\V1\UpdateJournalEntryRequest $request, JournalEntry $journal_entry)
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
     */
    public function destroy(JournalEntry $journal_entry)
    {
        $journal_entry->delete();

        return response()->noContent();
    }
}