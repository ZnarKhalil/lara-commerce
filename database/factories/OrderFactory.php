<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'order_number' => 'ORD-'.fake()->unique()->numberBetween(1000, 9999),
            'total_amount' => fake()->randomFloat(2, 10, 1000),
            'status' => OrderStatus::PENDING,
            'payment_status' => PaymentStatus::PENDING,
            'payment_method' => PaymentMethod::CREDIT_CARD,
            'shipping_address' => json_encode([
                'street' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->stateAbbr(),
                'zip' => fake()->postcode(),
                'country' => fake()->country(),
            ]),
            'billing_address' => json_encode([
                'street' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->stateAbbr(),
                'zip' => fake()->postcode(),
                'country' => fake()->country(),
            ]),
        ];
    }
}
