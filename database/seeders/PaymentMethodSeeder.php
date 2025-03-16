<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Credit Card
        PaymentMethod::firstOrCreate(
            ['code' => 'credit_card'],
            [
                'name' => 'Credit Card',
                'provider' => 'stripe',
                'icon' => 'credit-card',
                'is_active' => true,
                'supports_deposit' => true,
                'supports_withdrawal' => false,
                'min_amount' => 10.00,
                'max_amount' => 10000.00,
                'fee_fixed' => 1.00,
                'fee_percentage' => 2.5,
                'processing_time_hours' => 0,
                'config' => [
                    'api_key' => env('STRIPE_KEY', 'pk_test_example'),
                    'secret_key' => env('STRIPE_SECRET', 'sk_test_example'),
                    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', 'whsec_example'),
                ],
            ]
        );

        // PayPal
        PaymentMethod::firstOrCreate(
            ['code' => 'paypal'],
            [
                'name' => 'PayPal',
                'provider' => 'paypal',
                'icon' => 'paypal',
                'is_active' => true,
                'supports_deposit' => true,
                'supports_withdrawal' => true,
                'min_amount' => 5.00,
                'max_amount' => 5000.00,
                'fee_fixed' => 0.30,
                'fee_percentage' => 3.0,
                'processing_time_hours' => 24,
                'config' => [
                    'client_id' => env('PAYPAL_CLIENT_ID', 'client_id_example'),
                    'client_secret' => env('PAYPAL_CLIENT_SECRET', 'client_secret_example'),
                    'environment' => env('PAYPAL_ENVIRONMENT', 'sandbox'),
                ],
            ]
        );

        // Bank Transfer
        PaymentMethod::firstOrCreate(
            ['code' => 'bank_transfer'],
            [
                'name' => 'Bank Transfer',
                'provider' => 'internal',
                'icon' => 'bank',
                'is_active' => true,
                'supports_deposit' => true,
                'supports_withdrawal' => true,
                'min_amount' => 50.00,
                'max_amount' => 50000.00,
                'fee_fixed' => 0.00,
                'fee_percentage' => 0.00,
                'processing_time_hours' => 48,
                'instructions' => 'Please transfer the amount to our bank account. Use your player ID as the reference.',
                'config' => [
                    'account_name' => env('BANK_ACCOUNT_NAME', 'Company Name'),
                    'account_number' => env('BANK_ACCOUNT_NUMBER', '1234567890'),
                    'bank_name' => env('BANK_NAME', 'Example Bank'),
                    'ifsc_code' => env('BANK_IFSC_CODE', 'EXMPL12345'),
                ],
            ]
        );

        // Google Pay
        PaymentMethod::firstOrCreate(
            ['code' => 'google_pay'],
            [
                'name' => 'Google Pay',
                'provider' => 'stripe',
                'icon' => 'google',
                'is_active' => true,
                'supports_deposit' => true,
                'supports_withdrawal' => false,
                'min_amount' => 10.00,
                'max_amount' => 5000.00,
                'fee_fixed' => 0.50,
                'fee_percentage' => 2.0,
                'processing_time_hours' => 0,
                'config' => [
                    'api_key' => env('STRIPE_KEY', 'pk_test_example'),
                    'secret_key' => env('STRIPE_SECRET', 'sk_test_example'),
                ],
            ]
        );

        // Apple Pay
        PaymentMethod::firstOrCreate(
            ['code' => 'apple_pay'],
            [
                'name' => 'Apple Pay',
                'provider' => 'stripe',
                'icon' => 'apple',
                'is_active' => true,
                'supports_deposit' => true,
                'supports_withdrawal' => false,
                'min_amount' => 10.00,
                'max_amount' => 5000.00,
                'fee_fixed' => 0.50,
                'fee_percentage' => 2.0,
                'processing_time_hours' => 0,
                'config' => [
                    'api_key' => env('STRIPE_KEY', 'pk_test_example'),
                    'secret_key' => env('STRIPE_SECRET', 'sk_test_example'),
                ],
            ]
        );
    }
}