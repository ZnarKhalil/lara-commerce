<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    private Order $order;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->user = User::factory()->create(['role' => UserRole::USER]);
        $this->product = Product::factory()->create(['stock' => 10]);
        $this->order = Order::factory()
            ->for($this->user)
            ->create(['status' => OrderStatus::PENDING]);
    }

    public function test_user_can_view_their_orders()
    {
        // Create some orders for the user
        Order::factory()->count(3)->for($this->user)->create();
        // Create orders for another user
        Order::factory()->count(2)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'order_number',
                            'total_amount',
                            'status',
                            'payment_status',
                            'payment_method',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                    'current_page',
                    'per_page',
                    'total',
                ],
            ]);

        $this->assertEquals(4, count($response->json('data.data'))); // 3 new + 1 from setUp
    }

    public function test_admin_can_view_all_orders()
    {
        Order::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/orders');

        $response->assertStatus(200);
        $this->assertEquals(6, count($response->json('data.data'))); // 5 new + 1 from setUp
    }

    public function test_user_can_create_order()
    {
        $orderData = [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                ],
            ],
            'shipping_address' => [
                'street' => '123 Main St',
                'city' => 'Test City',
                'state' => 'TS',
                'zip' => '12345',
                'country' => 'Test Country',
            ],
            'billing_address' => [
                'street' => '123 Main St',
                'city' => 'Test City',
                'state' => 'TS',
                'zip' => '12345',
                'country' => 'Test Country',
            ],
            'payment_method' => PaymentMethod::CREDIT_CARD->value,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/orders', $orderData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'order_number',
                    'total_amount',
                    'status',
                    'payment_status',
                    'payment_method',
                    'shipping_address',
                    'billing_address',
                    'order_items' => [
                        '*' => [
                            'id',
                            'product_id',
                            'quantity',
                            'price',
                        ],
                    ],
                ],
            ]);

        // Check if stock was reduced
        $this->assertEquals(8, $this->product->fresh()->stock);
    }

    public function test_cannot_create_order_with_insufficient_stock()
    {
        $orderData = [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 20, // More than available stock
                ],
            ],
            'shipping_address' => [
                'street' => '123 Main St',
                'city' => 'Test City',
                'state' => 'TS',
                'zip' => '12345',
                'country' => 'Test Country',
            ],
            'billing_address' => [
                'street' => '123 Main St',
                'city' => 'Test City',
                'state' => 'TS',
                'zip' => '12345',
                'country' => 'Test Country',
            ],
            'payment_method' => PaymentMethod::CREDIT_CARD->value,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/orders', $orderData);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Insufficient stock for product: '.$this->product->name,
            ]);

        // Check if stock remained unchanged
        $this->assertEquals(10, $this->product->fresh()->stock);
    }

    public function test_user_can_view_their_order()
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/orders/{$this->order->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'order_number',
                    'total_amount',
                    'status',
                    'payment_status',
                    'payment_method',
                ],
            ]);
    }

    public function test_user_cannot_view_other_users_order()
    {
        $otherUser = User::factory()->create();
        $otherOrder = Order::factory()->for($otherUser)->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/orders/{$otherOrder->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_cancel_pending_order()
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/orders/{$this->order->id}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'status' => OrderStatus::CANCELLED->value,
                ],
            ]);
    }

    public function test_user_cannot_cancel_non_pending_order()
    {
        $this->order->update(['status' => OrderStatus::PROCESSING]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/orders/{$this->order->id}/cancel");

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Only pending orders can be cancelled',
            ]);
    }

    public function test_admin_can_update_order_status()
    {
        $response = $this->actingAs($this->admin)
            ->patchJson("/api/orders/{$this->order->id}/status", [
                'status' => OrderStatus::PROCESSING->value,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'status' => OrderStatus::PROCESSING->value,
                ],
            ]);
    }

    public function test_regular_user_cannot_update_order_status()
    {
        $response = $this->actingAs($this->user)
            ->patchJson("/api/orders/{$this->order->id}/status", [
                'status' => OrderStatus::PROCESSING->value,
            ]);

        $response->assertStatus(403);
    }
}
