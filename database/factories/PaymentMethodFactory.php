<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PaymentMethod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Credit Card', 'PayPal', 'Bank Transfer', 'Google Pay', 'Apple Pay']),
            'code' => fake()->unique()->slug(2),
            'provider' => fake()->randomElement(['stripe', 'paypal', 'razorpay', 'internal']),
            'icon' => fake()->randomElement(['credit-card', 'paypal', 'bank', 'google', 'apple']),
            'is_active' => true,
            'supports_deposit' => fake()->boolean(80),
            'supports_withdrawal' => fake()->boolean(60),
            'min_amount' => fake()->randomFloat(2, 5, 20),
            'max_amount' => fake()->randomElement([null, 1000, 5000, 10000]),
            'fee_fixed' => fake()->randomFloat(2, 0, 5),
            'fee_percentage' => fake()->randomFloat(2, 0, 3),
            'processing_time_hours' => fake()->randomElement([0, 1, 24, 48, 72]),
            'instructions' => fake()->boolean(70) ? fake()->paragraph() : null,
        ];
    }

    /**
     * Define a credit card payment method.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function creditCard(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'Credit Card',
            'code' => 'credit_card',
            'provider' => 'stripe',
            'icon' => 'credit-card',
            'supports_deposit' => true,
            'supports_withdrawal' => false,
            'config' => [
                'api_key' => 'pk_test_example',
                'secret_key' => 'sk_test_example',
                'webhook_secret' => 'whsec_example',
            ],
        ]);
    }

    /**
     * Define a PayPal payment method.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function paypal(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'PayPal',
            'code' => 'paypal',
            'provider' => 'paypal',
            'icon' => 'paypal',
            'supports_deposit' => true,
            'supports_withdrawal' => true,
            'config' => [
                'client_id' => 'client_id_example',
                'client_secret' => 'client_secret_example',
                'environment' => 'sandbox',
            ],
        ]);
    }

    /**
     * Define a bank transfer payment method.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function bankTransfer(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'Bank Transfer',
            'code' => 'bank_transfer',
            'provider' => 'internal',
            'icon' => 'bank',
            'supports_deposit' => true,
            'supports_withdrawal' => true,
            'processing_time_hours' => 48,
            'instructions' => 'Please transfer the amount to our bank account. Use your player ID as the reference.',
            'config' => [
                'account_name' => 'Company Name',
                'account_number' => '1234567890',
                'bank_name' => 'Example Bank',
                'ifsc_code' => 'EXMPL12345',
            ],
        ]);
    }
}