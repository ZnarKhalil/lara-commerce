<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->user = User::factory()->create(['role' => UserRole::USER]);
        $this->product = Product::factory()->create();
    }

    public function test_authenticated_user_can_fetch_products_list()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                            'description',
                            'price',
                            'stock',
                            'sku',
                            'is_active',
                        ],
                    ],
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);
    }

    public function test_authenticated_user_can_fetch_single_product()
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/products/{$this->product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'slug' => $this->product->slug,
                ],
            ]);
    }

    public function test_admin_can_create_product()
    {
        $categories = Category::factory()->count(2)->create();

        $productData = [
            'name' => 'New Product',
            'description' => 'Product Description',
            'price' => 99.99,
            'stock' => 100,
            'sku' => 'PRD-001',
            'is_active' => true,
            'category_ids' => $categories->pluck('id')->toArray(),
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/products', $productData);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'name' => 'New Product',
                    'slug' => 'new-product',
                    'price' => '99.99',
                    'stock' => 100,
                    'sku' => 'PRD-001',
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'New Product',
            'slug' => 'new-product',
        ]);
    }

    public function test_admin_can_update_product()
    {
        $categories = Category::factory()->count(2)->create();

        $updateData = [
            'name' => 'Updated Product',
            'description' => 'Updated Description',
            'price' => 149.99,
            'stock' => 50,
            'category_ids' => $categories->pluck('id')->toArray(),
        ];

        $response = $this->actingAs($this->admin)
            ->putJson("/api/products/{$this->product->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'name' => 'Updated Product',
                    'slug' => 'updated-product',
                    'price' => '149.99',
                    'stock' => 50,
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
            'name' => 'Updated Product',
            'slug' => 'updated-product',
        ]);
    }

    public function test_admin_can_delete_product()
    {
        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/products/{$this->product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Product deleted successfully',
            ]);

        $this->assertDatabaseMissing('products', [
            'id' => $this->product->id,
        ]);
    }

    public function test_regular_user_cannot_create_product()
    {
        $productData = [
            'name' => 'New Product',
            'description' => 'Product Description',
            'price' => 99.99,
            'stock' => 100,
            'sku' => 'PRD-001',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/products', $productData);

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_update_product()
    {
        $updateData = [
            'name' => 'Updated Product',
            'description' => 'Updated Description',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/products/{$this->product->id}", $updateData);

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_delete_product()
    {
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/products/{$this->product->id}");

        $response->assertStatus(403);
    }

    public function test_product_sku_must_be_unique()
    {
        $existingProduct = Product::factory()->create(['sku' => 'SKU-001']);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/products', [
                'name' => 'New Product',
                'description' => 'Product Description',
                'price' => 99.99,
                'stock' => 100,
                'sku' => 'SKU-001',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sku']);
    }

    public function test_can_get_related_products()
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create();
        $product->categories()->attach($category);

        // Create related products in same category
        $relatedProducts = Product::factory()->count(3)->create();
        foreach ($relatedProducts as $relatedProduct) {
            $relatedProduct->categories()->attach($category);
        }

        // Create unrelated product in different category
        $unrelatedProduct = Product::factory()->create();
        $unrelatedProduct->categories()->attach(Category::factory()->create());

        $response = $this->actingAs($this->user)
            ->getJson("/api/products/{$product->id}/related");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_unauthenticated_user_cannot_access_products()
    {
        $response = $this->getJson('/api/products');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}
