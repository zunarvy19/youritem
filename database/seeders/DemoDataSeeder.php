<?php

namespace Database\Seeders;

use App\Enums\Priority;
use App\Enums\Purpose;
use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'arvy@example.com'],
            [
                'name' => 'Arvy',
                'password' => Hash::make('password123'),
            ],
        );

        Budget::updateOrCreate(
            ['user_id' => $user->id],
            ['amount' => 1_500_000],
        );

        $categories = Category::pluck('id', 'name');

        $items = [
            ['name' => 'Logitech MX Master 3S', 'category' => 'Electronics', 'priority' => Priority::High, 'purpose' => Purpose::Need, 'price' => 1_250_000],
            ['name' => 'Sony WH-1000XM6', 'category' => 'Electronics', 'priority' => Priority::Medium, 'purpose' => Purpose::Want, 'price' => 5_800_000],
            ['name' => 'Mechanical Keyboard', 'category' => 'Electronics', 'priority' => Priority::Medium, 'purpose' => Purpose::Need, 'price' => 850_000],
            ['name' => 'Running Shoes', 'category' => 'Fashion', 'priority' => Priority::High, 'purpose' => Purpose::Need, 'price' => 1_200_000],
            ['name' => 'Parfum', 'category' => 'Makeup', 'priority' => Priority::Low, 'purpose' => Purpose::Want, 'price' => 700_000],
            ['name' => 'Skincare Set', 'category' => 'Skincare', 'priority' => Priority::Medium, 'purpose' => Purpose::Need, 'price' => 450_000],
            ['name' => 'Water Bottle', 'category' => 'Other', 'priority' => Priority::Low, 'purpose' => Purpose::Need, 'price' => 150_000],
        ];

        foreach ($items as $item) {
            WishlistItem::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'name' => $item['name'],
                ],
                [
                    'category_id' => $categories[$item['category']],
                    'priority' => $item['priority'],
                    'purpose' => $item['purpose'],
                    'estimated_price' => $item['price'],
                    'status' => 'ACTIVE',
                ],
            );
        }

        // One historical purchase to populate purchase history.
        $purchased = WishlistItem::firstOrCreate(
            [
                'user_id' => $user->id,
                'name' => 'Coffee Beans (Archangel)',
            ],
            [
                'category_id' => $categories['Food'],
                'priority' => Priority::Medium,
                'purpose' => Purpose::Want,
                'estimated_price' => 250_000,
                'status' => 'PURCHASED',
            ],
        );

        $user->purchases()->firstOrCreate([
            'wishlist_item_id' => $purchased->id,
        ], [
            'actual_price' => 220_000,
            'purchased_at' => now()->subDays(5),
        ]);
    }
}
