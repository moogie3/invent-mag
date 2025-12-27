<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'journal_entry_id' => JournalEntry::factory(),
            'account_id' => Account::factory(),
            'type' => $this->faker->randomElement(['debit', 'credit']),
            'amount' => $this->faker->randomFloat(2, 1, 1000),
        ];
    }
}