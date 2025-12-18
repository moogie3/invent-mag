<?php

namespace Tests\Feature\Api\V1;

use App\Models\POItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class POItemControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $userWithoutPermission;
    private Purchase $purchase;
    private Product $product;
    private POItem $poItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->setupUser([
            'view-po-items',
            'create-po-items',
            'edit-po-items',
            'delete-po-items',
        ]);

        $this->userWithoutPermission = $this->setupUser();

        $this->purchase = Purchase::factory()->create();
        $this->product = Product::factory()->create();

        $this->poItem = POItem::factory()->create([
            'po_id' => $this->purchase->id,
            'product_id' => $this->product->id,
        ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_po_items_api()
    {
        $this->getJson('/api/v1/po-items')->assertStatus(401);
        $this->postJson('/api/v1/po-items')->assertStatus(401);
        $this->getJson("/api/v1/po-items/{$this->poItem->id}")->assertStatus(401);
        $this->putJson("/api/v1/po-items/{$this->poItem->id}")->assertStatus(401);
        $this->deleteJson("/api/v1/po-items/{$this->poItem->id}")->assertStatus(401);
    }

    #[Test]
    public function user_without_permission_is_forbidden_from_po_items_api()
    {
        $this->actingAs($this->userWithoutPermission, 'sanctum')
            ->getJson('/api/v1/po-items')
            ->assertStatus(403);

        $this->actingAs($this->userWithoutPermission, 'sanctum')
            ->postJson('/api/v1/po-items', [])
            ->assertStatus(403);

        $this->actingAs($this->userWithoutPermission, 'sanctum')
            ->putJson("/api/v1/po-items/{$this->poItem->id}", [])
            ->assertStatus(403);

        $this->actingAs($this->userWithoutPermission, 'sanctum')
            ->deleteJson("/api/v1/po-items/{$this->poItem->id}")
            ->assertStatus(403);
    }

    #[Test]
    public function authenticated_user_can_list_po_items()
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/po-items')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'po_id', 'product_id', 'quantity', 'price'],
                ],
            ]);
    }

    #[Test]
    public function authenticated_user_can_view_a_po_item()
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/po-items/{$this->poItem->id}")
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $this->poItem->id]);
    }
}
