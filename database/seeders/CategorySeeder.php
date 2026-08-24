<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Electronics',
            'Makeup',
            'Skincare',
            'Fashion',
            'Food',
            'Hobby',
            'Other',
        ];

        foreach ($categories as $name) {
            Category::updateOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
