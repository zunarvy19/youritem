<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'wishlist_item_id' => WishlistItem::factory(),
            'actual_price' => 0,
            'purchased_at' => fake()->dateTimeBetween('-3 months'),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Purchase $purchase): void {
            /** @var WishlistItem|null $item */
            $item = $purchase->wishlist_item_id
                ? WishlistItem::find($purchase->wishlist_item_id)
                : null;

            if ($item !== null) {
                $purchase->user_id = $item->user_id;

                if ($purchase->actual_price === 0) {
                    $purchase->actual_price = max(
                        1,
                        $item->estimated_price + random_int(-50_000, 50_000),
                    );
                }
            }
        });
    }
}
