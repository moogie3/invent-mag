<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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
    /**
     * Display a listing of the payments.
     *
     * @queryParam per_page int The number of payments to return per page. Defaults to 15. Example: 25
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $payments = Payment::with('paymentable')->paginate($perPage);
        return PaymentResource::collection($payments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified payment.
     *
     * @urlParam payment required The ID of the payment. Example: 1
     */
    public function show(Payment $payment)
    {
        return new PaymentResource($payment->load('paymentable'));
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
