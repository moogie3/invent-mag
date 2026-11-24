<?php

namespace Tests\Unit\Jobs;

use App\Jobs\DownloadShopeeCoverImages;
use App\Models\Product;
use App\Services\Shopee\ShopeeImportService;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTenant;

class DownloadShopeeCoverImagesTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        Storage::fake('public');
    }

    private function makeJob(Product $product, string $url): DownloadShopeeCoverImages
    {
        return new DownloadShopeeCoverImages($this->tenant->id, [
            ['product_id' => $product->id, 'url' => $url],
        ]);
    }

    #[Test]
    public function it_downloads_and_stores_the_cover()
    {
        $product = Product::factory()->create(['code' => 'IMG-001', 'image' => null]);
        Http::fake(['*' => Http::response('img-bytes', 200, ['Content-Type' => 'image/jpeg'])]);

        $this->makeJob($product, 'https://cf.shopee.co.id/file/abc')->handle();

        $product->refresh();
        $raw = $product->getRawOriginal('image');
        $this->assertNotNull($raw);
        $this->assertStringStartsWith('shopee_'.$product->id.'_', $raw);
        $this->assertTrue(Storage::disk('public')->exists('image/'.$raw));
        Http::assertSentCount(1);
    }

    #[Test]
    public function it_skips_http_when_the_cover_is_already_stored()
    {
        $product = Product::factory()->create(['code' => 'IMG-002']);
        $url = 'https://cf.shopee.co.id/file/def';
        $stored = 'shopee_'.$product->id.'_'.substr(sha1($url), 0, 12).'.jpg';
        Storage::disk('public')->put('image/'.$stored, 'cached');
        $product->update(['image' => $stored]);
        Http::fake();

        $this->makeJob($product, $url)->handle();

        Http::assertNothingSent();
    }

    #[Test]
    public function it_throws_when_a_download_fails_so_the_queue_retries_the_batch()
    {
        $product = Product::factory()->create(['code' => 'IMG-003', 'image' => null]);
        Http::fake(fn () => throw new ConnectionException('connect timed out'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('1 Shopee cover download(s) failed');

        $this->makeJob($product, 'https://cf.shopee.co.id/file/ghi')->handle();
    }

    #[Test]
    public function a_partially_failed_batch_counts_only_the_failed_items()
    {
        $token = 'partial-batch-token';
        $base = ShopeeImportService::mediaProgressCacheKey($this->user->id, $token);
        Cache::put($base, 2, 60);
        Cache::put($base.'.done', 0, 60);
        Cache::put($base.'.failed', 0, 60);

        $ok = Product::factory()->create(['code' => 'PART-OK', 'image' => null]);
        $bad = Product::factory()->create(['code' => 'PART-BAD', 'image' => null]);
        Http::fake([
            'https://cf.shopee.co.id/file/ok*' => Http::response('img-bytes', 200, ['Content-Type' => 'image/jpeg']),
            'https://cf.shopee.co.id/file/bad*' => fn () => throw new ConnectionException('connect timed out'),
        ]);

        $job = new DownloadShopeeCoverImages($this->tenant->id, [
            ['product_id' => $ok->id, 'url' => 'https://cf.shopee.co.id/file/ok1'],
            ['product_id' => $bad->id, 'url' => 'https://cf.shopee.co.id/file/bad1'],
        ], $this->user->id, $token);
        // Final attempt: this is the only run whose failures may be counted.
        $job->setJob(\Mockery::mock(JobContract::class, ['attempts' => 3]));

        try {
            $job->handle();
        } catch (\RuntimeException) {
            // The batch still fails so the queue retries the failed item.
        }

        $this->assertSame(1, (int) Cache::get($base.'.failed'), 'only the failed item counts as failed, not the whole batch');
        $this->assertSame(1, (int) Cache::get($base.'.done'), 'the successful item of the batch counts as done');
    }

    #[Test]
    public function media_counters_are_seeded_before_the_jobs_start_counting()
    {
        $this->actingAs($this->user);
        $token = 'seed-order-token';
        $base = ShopeeImportService::mediaProgressCacheKey($this->user->id, $token);

        $product = Product::factory()->create(['code' => 'SEED-001', 'image' => null]);
        Http::fake(['*' => Http::response('img-bytes', 200, ['Content-Type' => 'image/jpeg'])]);

        $service = app(ShopeeImportService::class);
        $covers = [[
            'product_id' => $product->id,
            'url' => 'https://cf.shopee.co.id/file/seed1',
            'current_image' => null,
        ]];
        $errors = [];

        (new \ReflectionMethod($service, 'queueCoverDownloads'))
            ->invokeArgs($service, [$covers, &$errors, $token]);

        $this->assertSame(1, (int) Cache::get($base), 'total is seeded for the modal');
        $this->assertSame(1, (int) Cache::get($base.'.done'), 'a fast job increments a counter that already exists');
    }

    #[Test]
    public function queue_retry_after_outlives_the_job_timeout()
    {
        // The longest-running job in the app (cover downloads and e-Faktur
        // batch filings both run 300s).
        $longestTimeout = 300;

        foreach (config('queue.connections') as $name => $connection) {
            if (! isset($connection['retry_after'])) {
                // sync has no retry_after; SQS uses a visibility timeout.
                continue;
            }

            $this->assertGreaterThan(
                $longestTimeout,
                (int) $connection['retry_after'],
                "retry_after on the [{$name}] connection must exceed the {$longestTimeout}s job timeout so a still-running job is never re-dispatched"
            );
        }
    }
}
