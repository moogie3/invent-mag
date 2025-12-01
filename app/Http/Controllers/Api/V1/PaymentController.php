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
     * @group Payments
     * @authenticated
     * @queryParam per_page int The number of payments to return per page. Defaults to 15. Example: 25
     *
     * @responseField data object[] A list of payments.
     * @responseField data[].id integer The ID of the payment.
     * @responseField data[].paymentable_type string The type of the paymentable model.
     * @responseField data[].paymentable_id integer The ID of the paymentable model.
     * @responseField data[].amount number The amount of the payment.
     * @responseField data[].payment_date string The date of the payment.
     * @responseField data[].payment_method string The payment method.
     * @responseField data[].notes string The notes for the payment.
     * @responseField data[].created_at string The date and time the payment was created.
     * @responseField data[].updated_at string The date and time the payment was last updated.
     * @responseField data[].paymentable object The paymentable model.
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
        $payments = Payment::with('paymentable')->paginate($perPage);
        return PaymentResource::collection($payments);
    }

    /**
     * Display the specified payment.
     *
     * @group Payments
     * @authenticated
     * @urlParam payment required The ID of the payment. Example: 1
     *
     * @responseField id integer The ID of the payment.
     * @responseField paymentable_type string The type of the paymentable model.
     * @responseField paymentable_id integer The ID of the paymentable model.
     * @responseField amount number The amount of the payment.
     * @responseField payment_date string The date of the payment.
     * @responseField payment_method string The payment method.
     * @responseField notes string The notes for the payment.
     * @responseField created_at string The date and time the payment was created.
     * @responseField updated_at string The date and time the payment was last updated.
     * @responseField paymentable object The paymentable model.
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
     * @responseField id integer The ID of the payment.
     * @responseField paymentable_type string The type of the paymentable model.
     * @responseField paymentable_id integer The ID of the paymentable model.
     * @responseField amount number The amount of the payment.
     * @responseField payment_date string The date of the payment.
     * @responseField payment_method string The payment method.
     * @responseField notes string The notes for the payment.
     * @responseField created_at string The date and time the payment was created.
     * @responseField updated_at string The date and time the payment was last updated.
     */
    public function update(\App\Http\Requests\Api\V1\UpdatePaymentRequest $request, Payment $payment)
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
     */
    public function destroy(Payment $payment)
    {
        $payment->delete();

        return response()->noContent();
    }
}
