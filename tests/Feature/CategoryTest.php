<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->user = User::factory()->create(['role' => UserRole::USER]);
        $this->category = Category::factory()->create();
    }

    public function test_authenticated_user_can_fetch_categories_list()
    {
        Category::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/categories');

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
                            'is_active',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                    'current_page',
                    'per_page',
                    'total',
                ],
            ]);

    }

    public function test_authenticated_user_can_fetch_single_category()
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/categories/{$this->category->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_fetch_categories()
    {
        $response = $this->getJson('/api/categories');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_admin_can_create_category()
    {
        $categoryData = [
            'name' => 'New Category',
            'description' => 'Category Description',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/categories', $categoryData);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'name' => 'New Category',
                    'slug' => 'new-category',
                    'description' => 'Category Description',
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'New Category',
            'slug' => 'new-category',
        ]);
    }

    public function test_admin_can_update_category()
    {
        $updateData = [
            'name' => 'Updated Category',
            'description' => 'Updated Description',
            'is_active' => false,
        ];

        $response = $this->actingAs($this->admin)
            ->putJson("/api/categories/{$this->category->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'name' => 'Updated Category',
                    'slug' => 'updated-category',
                    'description' => 'Updated Description',
                    'is_active' => false,
                ],
            ]);

        $this->assertDatabaseHas('categories', [
            'id' => $this->category->id,
            'name' => 'Updated Category',
            'slug' => 'updated-category',
        ]);
    }

    public function test_admin_can_delete_category()
    {
        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/categories/{$this->category->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Category deleted successfully',
            ]);

        $this->assertDatabaseMissing('categories', [
            'id' => $this->category->id,
        ]);
    }

    public function test_regular_user_cannot_create_category()
    {
        $categoryData = [
            'name' => 'New Category',
            'description' => 'Category Description',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/categories', $categoryData);

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_update_category()
    {
        $updateData = [
            'name' => 'Updated Category',
            'description' => 'Updated Description',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/categories/{$this->category->id}", $updateData);

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_delete_category()
    {
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/categories/{$this->category->id}");

        $response->assertStatus(403);
    }

    public function test_category_name_must_be_unique()
    {
        $existingCategory = Category::factory()->create(['name' => 'Existing Category']);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/categories', [
                'name' => 'Existing Category',
                'description' => 'New Description',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_category_products_can_be_fetched()
    {
        $category = Category::factory()
            ->hasProducts(3)
            ->create();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/categories/{$category->id}/products");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                            'price',
                            'stock',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                    'current_page',
                    'per_page',
                    'total',
                ],
            ]);
    }
}
