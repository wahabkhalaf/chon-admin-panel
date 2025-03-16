<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use App\Models\Player;
use App\Models\PlayerWallet;
use App\Models\Transaction;
use App\Models\TransactionLog;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all existing players
        $players = Player::all();

        if ($players->isEmpty()) {
            // Create some players if none exist
            $players = Player::factory()->count(10)->create();
        }

        // Get payment methods
        $paymentMethods = PaymentMethod::all();
        if ($paymentMethods->isEmpty()) {
            // Run payment method seeder if no payment methods exist
            $this->call(PaymentMethodSeeder::class);
            $paymentMethods = PaymentMethod::all();
        }

        // Filter payment methods by type
        $depositMethods = $paymentMethods->filter(fn($method) => $method->supports_deposit)->values();
        $withdrawalMethods = $paymentMethods->filter(fn($method) => $method->supports_withdrawal)->values();

        // Create wallets for players
        foreach ($players as $player) {
            // Create wallet if it doesn't exist
            PlayerWallet::firstOrCreate(
                ['player_id' => $player->id],
                ['balance' => 0]
            );

            // Create some deposit transactions
            Transaction::factory()
                ->deposit()
                ->completed()
                ->count(rand(1, 5))
                ->create([
                    'player_id' => $player->id,
                    'payment_method' => $this->getRandomPaymentMethod($depositMethods, 'deposit'),
                    'payment_provider' => $this->getRandomPaymentProvider(),
                    'payment_details' => $this->getRandomPaymentDetails(),
                ]);

            // Create some withdrawal transactions
            if (rand(0, 1)) {
                Transaction::factory()
                    ->withdrawal()
                    ->completed()
                    ->count(rand(1, 3))
                    ->create([
                        'player_id' => $player->id,
                        'payment_method' => $this->getRandomPaymentMethod($withdrawalMethods, 'withdrawal'),
                        'payment_provider' => $this->getRandomPaymentProvider(),
                        'payment_details' => $this->getRandomPaymentDetails(),
                    ]);
            }

            // Create some entry fee transactions
            Transaction::factory()
                ->entryFee()
                ->completed()
                ->count(rand(2, 8))
                ->create([
                    'player_id' => $player->id,
                ]);

            // Create some prize transactions
            if (rand(0, 1)) {
                Transaction::factory()
                    ->prize()
                    ->completed()
                    ->count(rand(1, 4))
                    ->create([
                        'player_id' => $player->id,
                    ]);
            }

            // Create some pending transactions
            Transaction::factory()
                ->pending()
                ->count(rand(0, 2))
                ->create([
                    'player_id' => $player->id,
                    'payment_method' => rand(0, 1) ? $this->getRandomPaymentMethod($depositMethods, 'deposit') : null,
                    'payment_provider' => rand(0, 1) ? $this->getRandomPaymentProvider() : null,
                ]);
        }

        // Create transaction logs for all transactions
        $transactions = Transaction::all();
        foreach ($transactions as $transaction) {
            // Create creation log
            TransactionLog::factory()
                ->creation()
                ->create([
                    'transaction_id' => $transaction->id,
                    'created_at' => $transaction->created_at,
                ]);

            // Create completion log for completed transactions
            if ($transaction->isCompleted()) {
                TransactionLog::factory()
                    ->completion()
                    ->create([
                        'transaction_id' => $transaction->id,
                        'created_at' => $transaction->updated_at ?? now(),
                    ]);
            }

            // Create failure log for failed transactions
            if ($transaction->status === Transaction::STATUS_FAILED) {
                TransactionLog::factory()
                    ->failure()
                    ->create([
                        'transaction_id' => $transaction->id,
                        'created_at' => $transaction->updated_at ?? now(),
                    ]);
            }
        }

        // Update wallet balances based on completed transactions
        foreach ($players as $player) {
            $balance = $player->transactions()
                ->where('status', Transaction::STATUS_COMPLETED)
                ->get()
                ->sum(function ($transaction) {
                    return $transaction->getSignedAmount();
                });

            $player->wallet()->update(['balance' => $balance]);
        }
    }

    /**
     * Get a random payment method code.
     *
     * @param \Illuminate\Support\Collection $methods
     * @param string $type
     * @return string|null
     */
    private function getRandomPaymentMethod($methods, $type)
    {
        if ($methods->isEmpty()) {
            return null;
        }

        return $methods->random()->code;
    }

    /**
     * Get a random payment provider.
     *
     * @return string|null
     */
    private function getRandomPaymentProvider()
    {
        $providers = ['stripe', 'paypal', 'razorpay', 'internal'];
        return $providers[array_rand($providers)];
    }

    /**
     * Get random payment details.
     *
     * @return array|null
     */
    private function getRandomPaymentDetails()
    {
        $types = ['card', 'bank', 'wallet'];
        $type = $types[array_rand($types)];

        switch ($type) {
            case 'card':
                return [
                    'card_type' => ['visa', 'mastercard', 'amex'][array_rand(['visa', 'mastercard', 'amex'])],
                    'last4' => rand(1000, 9999),
                    'exp_month' => rand(1, 12),
                    'exp_year' => date('Y') + rand(0, 5),
                ];
            case 'bank':
                return [
                    'bank_name' => ['Chase', 'Bank of America', 'Wells Fargo'][array_rand(['Chase', 'Bank of America', 'Wells Fargo'])],
                    'account_last4' => rand(1000, 9999),
                ];
            case 'wallet':
                return [
                    'email' => 'user' . rand(100, 999) . '@example.com',
                    'account_id' => 'acc_' . bin2hex(random_bytes(10)),
                ];
            default:
                return null;
        }
    }
}