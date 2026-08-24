<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Enums\Purpose;
use App\Enums\WishlistStatus;
use App\Models\Category;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WishlistItem>
 */
class WishlistItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'name' => fake()->randomElement([
                'Mouse', 'Keyboard', 'Headphones', 'Monitor', 'Skincare Set',
                'Parfum', 'Running Shoes', 'Backpack', 'Coffee Grinder', 'Desk Lamp',
            ]),
            'priority' => Priority::Medium,
            'purpose' => Purpose::Need,
            'estimated_price' => fake()->numberBetween(50_000, 2_000_000),
            'notes' => fake()->boolean(30) ? fake()->sentence() : null,
            'status' => WishlistStatus::Active,
        ];
    }

    public function high(): static
    {
        return $this->state(fn (): array => [
            'priority' => Priority::High,
        ]);
    }

    public function medium(): static
    {
        return $this->state(fn (): array => [
            'priority' => Priority::Medium,
        ]);
    }

    public function low(): static
    {
        return $this->state(fn (): array => [
            'priority' => Priority::Low,
        ]);
    }

    public function need(): static
    {
        return $this->state(fn (): array => [
            'purpose' => Purpose::Need,
        ]);
    }

    public function want(): static
    {
        return $this->state(fn (): array => [
            'purpose' => Purpose::Want,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => WishlistStatus::Archived,
        ]);
    }

    public function purchased(): static
    {
        return $this->state(fn (): array => [
            'status' => WishlistStatus::Purchased,
        ]);
    }
}
