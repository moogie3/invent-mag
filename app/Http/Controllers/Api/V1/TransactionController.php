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
        $transactions = Transaction::with(['account'])->paginate($perPage);
        return TransactionResource::collection($transactions);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @bodyParam journal_entry_id integer required The ID of the journal entry. Example: 1
     * @bodyParam account_id integer required The ID of the account. Example: 1
     * @bodyParam type string required The type of transaction (e.g., debit, credit). Example: credit
     * @bodyParam amount numeric required The amount of the transaction. Example: 50.00
     *
     * @response 201 {
     *     "data": {
     *         "id": 1,
     *         "journal_entry_id": 1,
     *         "account_id": 1,
     *         "type": "credit",
     *         "amount": 50.00,
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-26T12:00:00.000000Z"
     *     }
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'journal_entry_id' => 'required|exists:journal_entries,id',
            'account_id' => 'required|exists:accounts,id',
            'type' => 'required|string|in:debit,credit',
            'amount' => 'required|numeric',
        ]);

        $transaction = Transaction::create($validated);

        return new TransactionResource($transaction);
    }

    /**
     * Display the specified transaction.
     *
     * @urlParam transaction required The ID of the transaction. Example: 1
     */
    public function show(Transaction $transaction)
    {
        return new TransactionResource($transaction->load(['account']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @urlParam transaction integer required The ID of the transaction. Example: 1
     * @bodyParam journal_entry_id integer required The ID of the journal entry. Example: 1
     * @bodyParam account_id integer required The ID of the account. Example: 1
     * @bodyParam type string required The type of transaction (e.g., debit, credit). Example: debit
     * @bodyParam amount numeric required The amount of the transaction. Example: 100.00
     *
     * @response 200 {
     *     "data": {
     *         "id": 1,
     *         "journal_entry_id": 1,
     *         "account_id": 1,
     *         "type": "debit",
     *         "amount": 100.00,
     *         "created_at": "2023-10-26T12:00:00.000000Z",
     *         "updated_at": "2023-10-27T12:00:00.000000Z"
     *     }
     * }
     */
    public function update(Request $request, Transaction $transaction)
    {
        $validated = $request->validate([
            'journal_entry_id' => 'required|exists:journal_entries,id',
            'account_id' => 'required|exists:accounts,id',
            'type' => 'required|string|in:debit,credit',
            'amount' => 'required|numeric',
        ]);

        $transaction->update($validated);

        return new TransactionResource($transaction);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @urlParam transaction integer required The ID of the transaction to delete. Example: 1
     *
     * @response 204 scenario="Success"
     */
    public function destroy(Transaction $transaction)
    {
        $transaction->delete();

        return response()->noContent();
    }
}
