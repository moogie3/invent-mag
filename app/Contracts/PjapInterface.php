<?php

namespace App\Contracts;

use App\Models\FakturPajak;

interface PjapInterface
{
    /**
     * Submit a single FakturPajak. Returns PJAP's submission tracking ID.
     *
     * @throws \RuntimeException when the PJAP API rejects the request
     */
    public function submit(FakturPajak $faktur): string;

    /**
     * Submit multiple FakturPajak in one batch call.
     *
     * @return array<int, string> [faktur_id => pjap_submission_id]
     *
     * @throws \RuntimeException when the PJAP API rejects the request
     */
    public function submitBatch(array $fakturIds): array;

    /**
     * Check submission status. Returns a normalized array:
     * ['status' => 'pending'|'approved'|'rejected', 'djp_reference' => ..., 'rejection_reason' => ..., 'raw' => ...]
     *
     * @return array{status: string, djp_reference: ?string, rejection_reason: ?string, raw: mixed}
     */
    public function checkStatus(string $pjapSubmissionId): array;

    /**
     * Cancel/void a previously submitted Faktur (Faktur Batal).
     *
     * @throws \RuntimeException when the PJAP endpoint cannot be reached
     *                            (a business refusal returns false instead)
     */
    public function cancel(string $pjapSubmissionId): bool;

    /**
     * Verify API credentials. Returns true if connection OK.
     */
    public function testConnection(): bool;
}
