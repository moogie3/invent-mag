<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactFormMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    /**
     * Handle the marketing-site contact form submission.
     *
     * Validates the form, passes the built-in honeypot (`botcheck` must stay
     * empty), and emails the platform contact address.
     *
     * @group Contact
     * Marketing-site contact form submission.
     *
     * @unauthenticated
     *
     * @bodyParam name string required The sender's full name.
     * @bodyParam email string required The sender's email address.
     * @bodyParam company string optional The sender's company name.
     * @bodyParam topic string required The selected topic. Enum: sales, support, demo, partnership, other.
     * @bodyParam message string required The message body.
     * @bodyParam botcheck string optional Honeypot field — leave empty to pass spam protection.
     *
     * @response scenario=success {
     *   "success": true,
     *   "message": "Message sent successfully!"
     * }
     * @response status=422 scenario="validation error" {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {
     *     "email": [
     *       "The email field must be a valid email address."
     *     ]
     *   }
     * }
     * @response status=500 scenario="mail failure" {
     *   "success": false,
     *   "message": "Failed to send message. Please try again."
     * }
     */
    public function submit(Request $request): JsonResponse
    {
        if ($request->filled('botcheck')) {
            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully!',
            ]);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'company' => 'nullable|string|max:255',
            'topic' => 'required|string|in:sales,support,demo,partnership,other',
            'message' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            Mail::to(config('app.contact_email'))
                ->send(new ContactFormMail($validator->validated()));

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message. Please try again.',
            ], 500);
        }
    }
}
