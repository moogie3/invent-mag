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
     * @group Reports
     * @title Get Stock Adjustment Log
     * @queryParam page int The page number to retrieve. Example: 1
     *
     * @response {
     *  "data": []
     * }
     */
    public function adjustmentLog(Request $request)
    {
        $adjustments = StockAdjustment::with(['product:id,name', 'adjustedBy:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json($adjustments);
    }

    /**
     * @group Reports
     * @title Get Recent Transactions
     * @queryParam per_page int The number of transactions per page. Example: 25
     * @queryParam type string Filter by type (e.g., 'purchase', 'sale').
     * @queryParam status string Filter by status (e.g., 'paid', 'due').
     * @queryParam date_range string Date range filter ('today', 'this_week', 'this_month', 'all'). Example: 'this_month'
     * @queryParam search string Search term.
     *
     * @response {
     *  "data": [],
     *  "summary": {}
     * }
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
     * @group Reports
     * @title Bulk Mark Transactions as Paid
     * @bodyParam transaction_ids array required An array of transaction IDs to mark as paid. Example: [1, 2, 3]
     *
     * @response {
     *  "success": true,
     *  "message": "Successfully marked 3 transaction(s) as paid.",
     *  "updated_count": 3
     * }
     */
    public function bulkMarkAsPaid(Request $request)
    {
        try {
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

        } catch (\Exception $e) {
            Log::error('Bulk mark as paid error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating transactions.'
            ], 500);
        }
    }

    /**
     * @group Reports
     * @title Mark a Single Transaction as Paid
     * @urlParam id integer required The ID of the transaction. Example: 1
     * @bodyParam type string required The type of the transaction ('purchase' or 'sale'). Example: "sale"
     *
     * @response {
     *  "success": true,
     *  "message": "Transaction marked as paid."
     * }
     */
    public function markAsPaid(Request $request, $id)
    {
        try {
            $type = $request->input('type');
            $result = $this->transactionService->markTransactionAsPaid($id, $type);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating transaction: ' . $e->getMessage(),
            ], 500);
        }
    }
}
