<?php

namespace Tests\Unit\Jobs;

use App\Jobs\DownloadShopeeCoverImages;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
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
}
