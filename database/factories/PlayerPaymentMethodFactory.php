<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use App\Models\Player;
use App\Models\PlayerPaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlayerPaymentMethod>
 */
class PlayerPaymentMethodFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PlayerPaymentMethod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'player_id' => Player::factory(),
            'payment_method_id' => PaymentMethod::factory(),
            'token' => fake()->uuid(),
            'external_id' => fake()->boolean(70) ? fake()->uuid() : null,
            'nickname' => fake()->boolean(50) ? fake()->words(2, true) : null,
            'is_default' => false,
            'last_used_at' => fake()->boolean(70) ? fake()->dateTimeBetween('-3 months', 'now') : null,
        ];
    }

    /**
     * Define a credit card payment method.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function creditCard(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_method_id' => PaymentMethod::factory()->creditCard(),
                'details' => [
                    'card_type' => fake()->randomElement(['visa', 'mastercard', 'amex']),
                    'last4' => fake()->numerify('####'),
                    'expiry_month' => fake()->numberBetween(1, 12),
                    'expiry_year' => fake()->numberBetween(date('Y'), date('Y') + 10),
                    'cardholder_name' => fake()->name(),
                ],
                'nickname' => fake()->boolean(70) ? fake()->randomElement(['My Card', 'Work Card', 'Personal Card']) : null,
            ];
        });
    }

    /**
     * Define a PayPal payment method.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function paypal(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_method_id' => PaymentMethod::factory()->paypal(),
                'details' => [
                    'email' => fake()->email(),
                    'account_id' => fake()->uuid(),
                ],
                'nickname' => fake()->boolean(70) ? fake()->randomElement(['My PayPal', 'Personal PayPal']) : null,
            ];
        });
    }

    /**
     * Define a bank account payment method.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function bankAccount(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_method_id' => PaymentMethod::factory()->bankTransfer(),
                'details' => [
                    'account_name' => fake()->name(),
                    'account_number' => fake()->numerify('##########'),
                    'bank_name' => fake()->company() . ' Bank',
                    'ifsc_code' => fake()->regexify('[A-Z]{4}[0-9]{7}'),
                ],
                'nickname' => fake()->boolean(70) ? fake()->randomElement(['My Bank Account', 'Savings Account']) : null,
            ];
        });
    }

    /**
     * Set this payment method as default.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function default(): Factory
    {
        return $this->state(fn(array $attributes) => [
            'is_default' => true,
        ]);
    }
}