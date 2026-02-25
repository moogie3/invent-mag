<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use Illuminate\Http\Request;

/**
 * @group Payments
 *
 * APIs for managing payments
 */
class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view-payments')->only(['index', 'show']);
        $this->middleware('permission:create-payments')->only(['store']);
        $this->middleware('permission:edit-payments')->only(['update']);
        $this->middleware('permission:delete-payments')->only(['destroy']);
    }
    /**
     * Display a listing of the payments.
     *
     * @group Payments
     * @authenticated
     * @queryParam per_page int The number of payments to return per page. Defaults to 15. Example: 25
     *
     * @response 200 scenario="Success" {"data":[{"id":1,"amount":100,"payment_date":"2025-11-28",...}],"links":{...},"meta":{...}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $payments = Payment::with('paymentable')->paginate($perPage);
        return PaymentResource::collection($payments);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @group Payments
     * @authenticated
     * @bodyParam amount number required The amount of the payment. Example: 100.00
     * @bodyParam payment_date date required The date of the payment. Example: 2025-11-28
     * @bodyParam payment_method string required The payment method. Example: Cash
     * @bodyParam notes string The notes for the payment. Example: Paid in full
     *
     * @response 201 scenario="Success" {"data":{"id":1,"amount":100,"payment_date":"2025-11-28",...}}
     * @response 422 scenario="Validation Error" {"message":"The amount field is required.","errors":{"amount":["The amount field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function store(\App\Http\Requests\Api\V1\StorePaymentRequest $request)
    {
        $validated = $request->validated();

        $payment = Payment::create($validated);

        return new PaymentResource($payment);
    }

    /**
     * Display the specified payment.
     *
     * @group Payments
     * @authenticated
     * @urlParam payment required The ID of the payment. Example: 1
     *
     * @response 200 scenario="Success" {"data":{"id":1,"amount":100,"payment_date":"2025-11-28",...}}
     * @response 404 scenario="Not Found" {"message": "Payment not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function show(Payment $payment)
    {
        return new PaymentResource($payment->load('paymentable'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @group Payments
     * @authenticated
     * @urlParam payment required The ID of the payment. Example: 1
     * @bodyParam paymentable_id integer required The ID of the paymentable model (e.g., Purchase, Sales). Example: 1
     * @bodyParam paymentable_type string required The type of the paymentable model (e.g., "App\\Models\\Purchase", "App\\Models\\Sales"). Example: App\\Models\\Purchase
     * @bodyParam amount number required The amount of the payment. Example: 100.00
     * @bodyParam payment_date date required The date of the payment. Example: 2025-11-28
     * @bodyParam payment_method string required The payment method. Example: Cash
     * @bodyParam notes string The notes for the payment. Example: Paid in full
     *
     * @response 200 scenario="Success" {"data":{"id":1,"amount":100,"payment_date":"2025-11-28",...}}
     * @response 404 scenario="Not Found" {"message": "Payment not found."}
     * @response 422 scenario="Validation Error" {"message":"The amount field is required.","errors":{"amount":["The amount field is required."]}}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function update(UpdatePaymentRequest $request, Payment $payment)
    {
        $validated = $request->validated();

        $payment->update($validated);

        return new PaymentResource($payment);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @group Payments
     * @authenticated
     * @urlParam payment required The ID of the payment. Example: 1
     *
     * @response 204 scenario="Success"
     * @response 404 scenario="Not Found" {"message": "Payment not found."}
     * @response 401 scenario="Unauthenticated" {"message": "Unauthenticated."}
     */
    public function destroy(Payment $payment)
    {
        $payment->delete();

        return response()->noContent();
    }
}
