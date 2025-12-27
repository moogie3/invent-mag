<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StockAdjustment;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @group Reports
 *
 * APIs for generating reports and viewing transaction data
 */
class ReportController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Get Stock Adjustment Log
     *
     * @group Reports
     * @authenticated
     * @queryParam page int The page number to retrieve. Example: 1
     * @queryParam per_page int The number of items to return per page. Defaults to 20. Example: 20
     *
     * @response 200 scenario="Success" {"data":[{"id":1,"product_id":1,"adjustment_type":"increase",...}],"links":{...},"meta":{...}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function adjustmentLog(Request $request)
    {
        $adjustments = StockAdjustment::with(['product:id,name', 'adjustedBy:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json($adjustments);
    }

    /**
     * Get Recent Transactions
     *
     * @group Reports
     * @authenticated
     * @queryParam per_page int The number of transactions per page. Example: 25
     * @queryParam type string Filter by type (e.g., 'purchase', 'sale').
     * @queryParam status string Filter by status (e.g., 'paid', 'due').
     * @queryParam date_range string Date range filter ('today', 'this_week', 'this_month', 'all'). Example: 'this_month'
     * @queryParam search string Search term.
     * @queryParam start_date date Start date for filtering. Example: 2023-01-01
     * @queryParam end_date date End date for filtering. Example: 2023-12-31
     * @queryParam sort string Field to sort by. Example: date
     * @queryParam direction string Sort direction (asc or desc). Example: desc
     *
     * @response 200 scenario="Success" {"transactions":{"data":[{"id":1,"type":"sale","ref_id":1,"date":"2025-12-01","amount":100,"description":"Sale #1","status":"paid"}]},"summary":{"total_sales":100,"total_purchases":0,"total_paid":100,"total_due":0}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function recentTransactions(Request $request)
    {
        $filters = [
            'per_page' => $request->get('per_page', 25),
            'type' => $request->get('type'),
            'status' => $request->get('status'),
            'date_range' => $request->get('date_range', 'all'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'search' => $request->get('search'),
            'sort' => $request->get('sort', 'date'),
            'direction' => $request->get('direction', 'desc'),
        ];

        $transactions = $this->transactionService->getTransactions($filters, $filters['per_page']);
        $summary = $this->transactionService->getTransactionsSummary($filters);

        return response()->json([
            'transactions' => $transactions,
            'summary' => $summary
        ]);
    }

    /**
     * Bulk Mark Transactions as Paid
     *
     * @group Reports
     * @authenticated
     * @bodyParam transaction_ids array required An array of transaction IDs to mark as paid. Example: [1, 2, 3]
     *
     * @response 200 scenario="Success" {"success":true,"message":"Successfully marked 3 transaction(s) as paid.","updated_count":3}
     * @response 422 scenario="Validation Error" {"message":"The transaction_ids field is required.","errors":{"transaction_ids":["The transaction_ids field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function bulkMarkAsPaid(Request $request)
    {
        $transactionIds = $request->input('transaction_ids', []);

        if (empty($transactionIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No transactions selected.'
            ], 400);
        }

        $updatedCount = $this->transactionService->bulkMarkAsPaid($transactionIds);

        return response()->json([
            'success' => true,
            'message' => "Successfully marked {$updatedCount} transaction(s) as paid.",
            'updated_count' => $updatedCount
        ]);
    }

    /**
     * Mark a Single Transaction as Paid
     *
     * @group Reports
     * @authenticated
     * @urlParam id integer required The ID of the transaction. Example: 1
     * @bodyParam type string required The type of the transaction ('purchase' or 'sale'). Example: "sale"
     *
     * @response 200 scenario="Success" {"success": true, "message": "Transaction marked as paid."}
     * @response 404 scenario="Not Found" {"message": "Transaction not found."}
     * @response 422 scenario="Validation Error" {"message":"The type field is required.","errors":{"type":["The type field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function markAsPaid(Request $request, $id)
    {
        $type = $request->input('type');
        $result = $this->transactionService->markTransactionAsPaid($id, $type);

        return response()->json($result);
    }
}