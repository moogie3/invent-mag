<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    private function productWith(array $attributes): Product
    {
        $product = new Product;
        foreach ($attributes as $key => $value) {
            $product->setAttribute($key, $value);
        }

        return $product;
    }

    #[Test]
    public function it_returns_the_local_file_url_when_an_image_is_cached()
    {
        $product = $this->productWith(['image' => 'shopee_1_abc.jpg', 'cover_url' => 'https://cf.shopee.co.id/file/x']);

        $this->assertSame(asset('storage/image/shopee_1_abc.jpg'), $product->image);
    }

    #[Test]
    public function it_falls_back_to_the_remote_cover_while_no_local_file_exists()
    {
        $product = $this->productWith(['image' => null, 'cover_url' => 'https://cf.shopee.co.id/file/x']);

        $this->assertSame('https://cf.shopee.co.id/file/x', $product->image);
    }

    #[Test]
    public function it_returns_the_placeholder_when_there_is_no_image_and_no_cover()
    {
        $product = $this->productWith(['image' => null, 'cover_url' => null]);

        $this->assertSame(asset('img/default_placeholder.png'), $product->image);
    }
}
