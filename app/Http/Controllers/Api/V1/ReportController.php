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
     * @responseField data object[] A list of stock adjustments.
     * @responseField data[].id integer The ID of the stock adjustment.
     * @responseField data[].product_id integer The ID of the product.
     * @responseField data[].adjustment_type string The type of adjustment (increase, decrease, correction).
     * @responseField data[].quantity_before integer The stock quantity before adjustment.
     * @responseField data[].quantity_after integer The stock quantity after adjustment.
     * @responseField data[].adjustment_amount integer The amount of stock adjusted.
     * @responseField data[].reason string The reason for the adjustment.
     * @responseField data[].adjusted_by integer The ID of the user who made the adjustment.
     * @responseField data[].created_at string The date and time the adjustment was created.
     * @responseField data[].updated_at string The date and time the adjustment was last updated.
     * @responseField data[].product object The product related to the adjustment.
     * @responseField data[].product.id integer The ID of the product.
     * @responseField data[].product.name string The name of the product.
     * @responseField data[].adjusted_by object The user who made the adjustment.
     * @responseField data[].adjusted_by.id integer The ID of the user.
     * @responseField data[].adjusted_by.name string The name of the user.
     * @responseField links object Links for pagination.
     * @responseField meta object Metadata for pagination.
     * @response 200 scenario="Success" {"data":[{"id":1,"product_id":1,"adjustment_type":"increase","quantity_before":10,"quantity_after":20,"adjustment_amount":10,"reason":"Stock correction","adjusted_by":1,"created_at":"2025-12-01T12:00:00.000000Z","updated_at":"2025-12-01T12:00:00.000000Z","product":{"id":1,"name":"Product 1"},"adjusted_by":{"id":1,"name":"Admin"}}]}
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
     * @responseField transactions object A paginated list of transactions.
     * @responseField transactions.data object[] A list of transactions.
     * @responseField transactions.data[].id integer The ID of the transaction.
     * @responseField transactions.data[].type string The type of transaction (purchase or sale).
     * @responseField transactions.data[].ref_id integer The ID of the related purchase or sale.
     * @responseField transactions.data[].date string The date of the transaction.
     * @responseField transactions.data[].amount number The amount of the transaction.
     * @responseField transactions.data[].description string A description of the transaction.
     * @responseField transactions.data[].status string The status of the transaction (paid, unpaid, partial).
     * @responseField transactions.links object Links for pagination.
     * @responseField transactions.meta object Metadata for pagination.
     * @responseField summary object Summary of transactions.
     * @responseField summary.total_sales number Total sales amount.
     * @responseField summary.total_purchases number Total purchases amount.
     * @responseField summary.total_paid number Total paid amount.
     * @responseField summary.total_due number Total due amount.
     * @response 200 scenario="Success" {"transactions":{"data":[{"id":1,"type":"sale","ref_id":1,"date":"2025-12-01","amount":100,"description":"Sale #1","status":"paid"}]},"summary":{"total_sales":100,"total_purchases":0,"total_paid":100,"total_due":0}}
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
     * @responseField success boolean Indicates whether the request was successful.
     * @responseField message string A message describing the result of the request.
     * @responseField updated_count integer The number of transactions successfully marked as paid.
     * @response 400 scenario="Bad Request" {"success": false, "message": "No transactions selected."}
     * @response 500 scenario="Error" {"success": false, "message": "An error occurred while updating transactions."}
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
     * Mark a Single Transaction as Paid
     *
     * @group Reports
     * @authenticated
     * @urlParam id integer required The ID of the transaction. Example: 1
     * @bodyParam type string required The type of the transaction ('purchase' or 'sale'). Example: "sale"
     *
     * @responseField success boolean Indicates whether the request was successful.
     * @responseField message string A message describing the result of the request.
     * @response 200 scenario="Success" {"success": true, "message": "Transaction marked as paid."}
     * @response 500 scenario="Error" {"success": false, "message": "Error updating transaction: <error message>"}
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