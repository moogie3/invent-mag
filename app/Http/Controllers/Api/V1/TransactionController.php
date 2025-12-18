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
    public function __construct()
    {
        $this->middleware('permission:view-transactions')->only(['index', 'show']);
    }
    /**
     * Display a listing of the transactions.
     *
     * @group Transactions
     * @authenticated
     * @queryParam per_page int The number of transactions to return per page. Defaults to 15. Example: 25
     *
     * @response 200 scenario="Success" {"data":[{"id":1,"journal_entry_id":1,"account_id":1,"type":"debit",...}],"links":{...},"meta":{...}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
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
     * @response 200 scenario="Success" {"data":{"id":1,"journal_entry_id":1,"account_id":1,"type":"debit",...}}
     * @response 404 scenario="Not Found" {"message": "Transaction not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function show(Transaction $transaction)
    {
        return new TransactionResource($transaction->load(['account']));
    }
}
