<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\Request;

/**
 * @group Transactions
 *
 * APIs for managing transactions
 */
class TransactionController extends Controller
{
    /**
     * Display a listing of the transactions.
     *
     * @group Transactions
     * @authenticated
     * @queryParam per_page int The number of transactions to return per page. Defaults to 15. Example: 25
     *
     * @responseField data object[] A list of transactions.
     * @responseField data[].id integer The ID of the transaction.
     * @responseField data[].journal_entry_id integer The ID of the journal entry.
     * @responseField data[].account_id integer The ID of the account.
     * @responseField data[].type string The type of transaction (debit, credit).
     * @responseField data[].amount number The amount of the transaction.
     * @responseField data[].created_at string The date and time the transaction was created.
     * @responseField data[].updated_at string The date and time the transaction was last updated.
     * @responseField data[].account object The account associated with the transaction.
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
        $transactions = Transaction::with(['account'])->paginate($perPage);
        return TransactionResource::collection($transactions);
    }

    /**
     * Display the specified transaction.
     *
     * @group Transactions
     * @authenticated
     * @urlParam transaction required The ID of the transaction. Example: 1
     *
     * @responseField id integer The ID of the transaction.
     * @responseField journal_entry_id integer The ID of the journal entry.
     * @responseField account_id integer The ID of the account.
     * @responseField type string The type of transaction (debit, credit).
     * @responseField amount number The amount of the transaction.
     * @responseField created_at string The date and time the transaction was created.
     * @responseField updated_at string The date and time the transaction was last updated.
     * @responseField account object The account associated with the transaction.
     */
    public function show(Transaction $transaction)
    {
        return new TransactionResource($transaction->load(['account']));
    }
}
