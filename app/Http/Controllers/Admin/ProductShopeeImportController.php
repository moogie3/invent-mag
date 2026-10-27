<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Shopee\ShopeeImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;

class ProductShopeeImportController extends Controller
{
    /**
     * Import Shopee Seller Centre "mass update" XLSX files (basic/media/sales/shipping)
     * into the product catalog. Matched by products.code = Shopee SKU Induk.
     */
    public function store(Request $request, ShopeeImportService $importService)
    {
        $request->validate([
            // NOTE: use `extensions` (filename-based), NOT `mimes` — xlsx files are ZIP
            // containers and the MIME sniffer often guesses application/zip, which
            // falsely rejects genuine Shopee .xlsx exports. The parser validates content.
            // sales_file is optional: it is only needed when the import CREATES new
            // products — createProduct() refuses creations without price data, so
            // price-0 products can never be written. Updates-only imports may omit it.
            'basic_file' => 'required|file|extensions:xlsx|max:20480',
            'sales_file' => 'nullable|file|extensions:xlsx|max:20480',
            'media_file' => 'nullable|file|extensions:xlsx|max:20480',
            'shipping_file' => 'nullable|file|extensions:xlsx|max:20480',
            'progress_token' => ['nullable', 'string', 'regex:/^[A-Za-z0-9\-]{8,64}$/'],
        ]);

        $paths = ['basic' => $request->file('basic_file')->getRealPath()];

        foreach (['media' => 'media_file', 'sales' => 'sales_file', 'shipping' => 'shipping_file'] as $key => $field) {
            if ($request->hasFile($field)) {
                $paths[$key] = $request->file($field)->getRealPath();
            }
        }

        $progressToken = $request->input('progress_token');
        $progressToken = is_string($progressToken) ? $progressToken : null;

        try {
            $summary = $importService->import($paths, $progressToken);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $message = __('messages.import_shopee_summary', [
            'updated' => $summary['updated'],
            'created' => $summary['created'],
            'skipped' => $summary['skipped_plan_limit'],
            'errors' => count($summary['errors']),
        ]);

        return response()->json([
            'success' => true,
            'message' => $message,
            'summary' => $summary,
        ]);
    }

    /**
     * Progress polled by the modal while the import POST is still running.
     * The token is client-generated per submission and scoped to the user,
     * so progress stays invisible across accounts.
     */
    public function progress(Request $request)
    {
        $token = (string) $request->query('token', '');

        if (! preg_match('/^[A-Za-z0-9\-]{8,64}$/', $token)) {
            return response()->json(['message' => 'Invalid progress token.'], 422);
        }

        $progress = Cache::get(
            ShopeeImportService::progressCacheKey($request->user()?->id, $token),
            ['phase' => 'idle', 'processed' => 0, 'total' => 0, 'updated_at' => null]
        );

        // Live media counters for this import (total seeded by the import,
        // done/failed incremented by each download batch). Null when the
        // import queued no images or the counters already expired.
        $media = null;
        $userId = $request->user()?->id;
        if ($userId !== null) {
            $base = ShopeeImportService::mediaProgressCacheKey($userId, $token);
            $total = Cache::get($base);
            if (is_int($total)) {
                $media = [
                    'total' => $total,
                    'done' => (int) Cache::get($base.'.done', 0),
                    'failed' => (int) Cache::get($base.'.failed', 0),
                ];
            }
        }

        return response()->json(array_merge($progress, [
            // Pending cover-download jobs, so the modal can show the image phase
            'queue_pending' => Queue::size(),
            'media' => $media,
        ]));
    }
}
