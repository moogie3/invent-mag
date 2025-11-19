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
     * @queryParam per_page int The number of transactions to return per page. Defaults to 15. Example: 25
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $transactions = Transaction::with(['account', 'user', 'transactionable'])->paginate($perPage);
        return TransactionResource::collection($transactions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified transaction.
     *
     * @urlParam transaction required The ID of the transaction. Example: 1
     */
    public function show(Transaction $transaction)
    {
        return new TransactionResource($transaction->load(['account', 'user', 'transactionable']));
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
