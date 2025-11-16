<?php

namespace Database\Factories;

use App\Models\JournalEntry;
use App\Models\Sales;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JournalEntry>
 */
class JournalEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => $this->faker->date(),
            'description' => $this->faker->sentence,
            'sourceable_id' => Sales::factory(),
            'sourceable_type' => Sales::class,
        ];
    }

    public function hasTransactions(int $count = 1, array $attributes = []): static
    {
        return $this->has(Transaction::factory()->count($count)->state($attributes), 'transactions');
    }
}