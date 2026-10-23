<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Downloads Shopee CDN cover images for products imported via
 * ShopeeImportService and stores them on the public disk (storage/app/public/image),
 * mirroring the manual product image upload flow.
 */
class DownloadShopeeCoverImages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Shopee CDN hosts we allow downloads from (SSRF guard). */
    private const ALLOWED_HOST_SUFFIXES = [
        'shopee.co.id',
        'shopee.co',
    ];

    private const MAX_BYTES = 5 * 1024 * 1024;

    public int $tries = 3;

    /** Short backoff: the modal polls the queue live, so retry gaps stay visible but brief. */
    public array $backoff = [10, 30];

    public int $timeout = 300;

    /**
     * @param  array<int, array{product_id: int, url: string}>  $items
     */
    public function __construct(
        public int $tenantId,
        public array $items,
    ) {}

    public function handle(): void
    {
        $tenant = Tenant::find($this->tenantId);
        if (! $tenant) {
            return;
        }
        $tenant->makeCurrent();

        $failures = 0;
        foreach ($this->items as $item) {
            try {
                $this->downloadFor((int) $item['product_id'], (string) $item['url']);
            } catch (\Throwable $e) {
                $failures++;
                Log::warning('Shopee cover download failed', [
                    'tenant_id' => $this->tenantId,
                    'product_id' => $item['product_id'],
                    'url' => $item['url'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($failures > 0) {
            // Fail the batch so the queue retries it (already-stored covers fast-path
            // on the next attempt); after $tries the batch lands in failed_jobs and
            // the modal's image phase drains to 0 instead of hanging silently.
            throw new \RuntimeException("{$failures} Shopee cover download(s) failed");
        }
    }

    private function downloadFor(int $productId, string $url): void
    {
        if (! $this->urlIsAllowed($url)) {
            return;
        }

        $product = Product::find($productId);
        if (! $product) {
            return;
        }

        // Fast path: this cover URL is already the stored image — never issue
        // an HTTP request for it (repeat imports / re-released jobs stay free).
        $oldName = $product->getRawOriginal('image');
        $expectedPrefix = 'shopee_'.$productId.'_'.Str::substr(sha1($url), 0, 12).'.';
        if ($oldName
            && Str::startsWith($oldName, $expectedPrefix)
            && Storage::disk('public')->exists('image/'.$oldName)) {
            return;
        }

        $response = Http::timeout(15)
            ->connectTimeout(5)
            ->withHeaders(['User-Agent' => 'invent-mag/1.0'])
            ->get($url);

        if (! $response->successful()) {
            return;
        }

        $content = $response->body();
        if ($content === '' || strlen($content) > self::MAX_BYTES) {
            return;
        }

        $extension = $this->extensionFromContentType((string) $response->header('Content-Type'));
        if ($extension === null) {
            return;
        }

        $newName = 'shopee_'.$productId.'_'.Str::substr(sha1($url), 0, 12).'.'.$extension;

        // Skip write when the existing stored image is byte-identical
        $oldName = $product->getRawOriginal('image');
        if ($oldName) {
            $oldPath = 'image/'.$oldName;
            if (Storage::disk('public')->exists($oldPath)
                && sha1(Storage::disk('public')->get($oldPath)) === sha1($content)) {
                return;
            }
        }

        Storage::disk('public')->put('image/'.$newName, $content);
        $product->update(['image' => $newName]);

        if ($oldName && $oldName !== $newName) {
            Storage::disk('public')->delete('image/'.$oldName);
        }
    }

    private function urlIsAllowed(string $url): bool
    {
        $parts = parse_url($url);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower($parts['host']);
        foreach (self::ALLOWED_HOST_SUFFIXES as $suffix) {
            if ($host === $suffix || Str::endsWith($host, '.'.$suffix)) {
                return true;
            }
        }

        return false;
    }

    private function extensionFromContentType(string $contentType): ?string
    {
        $contentType = strtolower(trim(explode(';', $contentType)[0]));

        return match ($contentType) {
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => null,
        };
    }
}
