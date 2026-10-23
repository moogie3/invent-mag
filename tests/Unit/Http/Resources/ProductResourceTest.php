<?php

namespace Tests\Unit\Http\Resources;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductResourceTest extends TestCase
{
    private function imageFor(array $attributes): string
    {
        $product = new Product;
        foreach ($attributes as $key => $value) {
            $product->setAttribute($key, $value);
        }

        return (new ProductResource($product))->toArray(request())['image'];
    }

    #[Test]
    public function it_returns_a_single_complete_url_for_local_images()
    {
        $image = $this->imageFor(['image' => 'shopee_1_abc.jpg']);

        $this->assertSame(asset('storage/image/shopee_1_abc.jpg'), $image);
        // Regression: the resource used to wrap the accessor's full URL again.
        $this->assertStringNotContainsString('storage/image/http', $image);
    }

    #[Test]
    public function it_returns_the_remote_cover_verbatim_when_nothing_is_cached_locally()
    {
        $image = $this->imageFor(['image' => null, 'cover_url' => 'https://cf.shopee.co.id/file/x']);

        $this->assertSame('https://cf.shopee.co.id/file/x', $image);
    }

    #[Test]
    public function it_returns_the_placeholder_when_nothing_is_available()
    {
        $image = $this->imageFor(['image' => null, 'cover_url' => null]);

        $this->assertSame(asset('img/default_placeholder.png'), $image);
    }
}
