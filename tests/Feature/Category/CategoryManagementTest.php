<?php

use App\Models\Category;
use App\Models\User;
use App\Models\WishlistItem;

it('requires authentication for category management', function (): void {
    $category = Category::factory()->create();

    $this->getJson('/api/categories')->assertUnauthorized();
    $this->postJson('/api/categories', ['name' => 'Travel'])->assertUnauthorized();
    $this->putJson("/api/categories/{$category->id}", ['name' => 'Trips'])->assertUnauthorized();
    $this->deleteJson("/api/categories/{$category->id}")->assertUnauthorized();
});

it('lists active categories by default and can include inactive categories', function (): void {
    $user = User::factory()->create();
    Category::factory()->create(['name' => 'Active category', 'is_active' => true]);
    Category::factory()->create(['name' => 'Inactive category', 'is_active' => false]);

    $active = $this->actingAs($user)->getJson('/api/categories');
    expect($active->json('data'))->toHaveCount(1);

    $all = $this->actingAs($user)->getJson('/api/categories?include_inactive=1');
    expect($all->json('data'))->toHaveCount(2);
});

it('creates a category with a trimmed unique name', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/categories', ['name' => '  Travel  '])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Travel')
        ->assertJsonPath('data.is_active', true);

    $this->assertDatabaseHas('categories', ['name' => 'Travel']);

    $this->actingAs($user)
        ->postJson('/api/categories', ['name' => 'Travel'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

it('validates category input', function (array $payload): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/categories', $payload)
        ->assertUnprocessable();
})->with([
    'missing name' => [[]],
    'blank name' => [['name' => '   ']],
    'long name' => [['name' => str_repeat('a', 101)]],
    'invalid active status' => [['name' => 'Travel', 'is_active' => 'yes']],
]);

it('renames and deactivates a category', function (): void {
    $user = User::factory()->create();
    $category = Category::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->putJson("/api/categories/{$category->id}", [
            'name' => 'Subscriptions',
            'is_active' => false,
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Subscriptions')
        ->assertJsonPath('data.is_active', false);
});

it('deletes an unused category', function (): void {
    $user = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($user)
        ->deleteJson("/api/categories/{$category->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

it('prevents deleting a category used by wishlist items', function (): void {
    $user = User::factory()->create();
    $category = Category::factory()->create();
    WishlistItem::factory()->for($user)->for($category)->create();

    $this->actingAs($user)
        ->deleteJson("/api/categories/{$category->id}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('category');

    $this->assertDatabaseHas('categories', ['id' => $category->id]);
});
