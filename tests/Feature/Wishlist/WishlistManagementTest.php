<?php

use App\Enums\Priority;
use App\Enums\Purpose;
use App\Enums\WishlistStatus;
use App\Models\Category;
use App\Models\User;
use App\Models\WishlistItem;
use Database\Seeders\CategorySeeder;

beforeEach(function (): void {
    $this->seed(CategorySeeder::class);
});

function wishlistPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Sony WH-1000XM6',
        'category_id' => Category::whereName('Electronics')->firstOrFail()->id,
        'priority' => Priority::High->value,
        'purpose' => Purpose::Need->value,
        'estimated_price' => 5800000,
    ], $overrides);
}

it('requires authentication for wishlist endpoints', function (): void {
    $this->getJson('/api/wishlist-items')->assertUnauthorized();
});

it('creates a wishlist item', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/wishlist-items', wishlistPayload());

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Sony WH-1000XM6')
        ->assertJsonPath('data.status', WishlistStatus::Active->value)
        ->assertJsonPath('data.priority', Priority::High->value);

    expect($user->wishlistItems()->count())->toBe(1);
});

it('validates wishlist item payload', function (array $overrides): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/wishlist-items', wishlistPayload($overrides))
        ->assertUnprocessable();
})->with([
    'missing name' => fn (): array => ['name' => null],
    'empty name' => fn (): array => ['name' => '   '],
    'invalid category' => fn (): array => ['category_id' => 99999],
    'invalid priority' => fn (): array => ['priority' => 'URGENT'],
    'invalid purpose' => fn (): array => ['purpose' => 'MAYBE'],
    'zero price' => fn (): array => ['estimated_price' => 0],
    'negative price' => fn (): array => ['estimated_price' => -5000],
    'string price' => fn (): array => ['estimated_price' => 'cheap'],
]);

it('rejects inactive categories for new items', function (): void {
    Category::whereName('Electronics')->update(['is_active' => false]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/wishlist-items', wishlistPayload())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['category_id']);
});

it('allows duplicate item names for the same user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/wishlist-items', wishlistPayload())->assertCreated();
    $this->actingAs($user)->postJson('/api/wishlist-items', wishlistPayload())->assertCreated();

    expect($user->wishlistItems()->count())->toBe(2);
});

it('lists only the authenticated user items scoped and paginated', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    WishlistItem::factory()->count(3)->for($userA)->create();
    WishlistItem::factory()->count(2)->for($userB)->create();

    $response = $this->actingAs($userA)->getJson('/api/wishlist-items');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(3);
    expect($response->json('meta.total'))->toBe(3);
});

it('searches items by name case-insensitively', function (): void {
    $user = User::factory()->create();
    WishlistItem::factory()->for($user)->create(['name' => 'Sony WH-1000XM6']);
    WishlistItem::factory()->for($user)->create(['name' => 'Coffee Grinder']);

    $response = $this->actingAs($user)->getJson('/api/wishlist-items?search=sony');

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.name'))->toBe('Sony WH-1000XM6');
});

it('filters items by priority and purpose and category', function (): void {
    $category = Category::whereName('Electronics')->firstOrFail();
    $otherCategory = Category::whereName('Food')->firstOrFail();
    $user = User::factory()->create();

    WishlistItem::factory()->for($user)->for($category)->high()->need()->create();
    WishlistItem::factory()->for($user)->for($otherCategory)->low()->want()->create();

    $matched = $this->actingAs($user)->getJson("/api/wishlist-items?priority=HIGH&purpose=NEED&category_id={$category->id}");
    expect($matched->json('data'))->toHaveCount(1);

    $byPurpose = $this->actingAs($user)->getJson('/api/wishlist-items?purpose=WANT');
    expect($byPurpose->json('data'))->toHaveCount(1);
});

it('sorts by price ascending', function (): void {
    $user = User::factory()->create();

    WishlistItem::factory()->for($user)->create(['estimated_price' => 900000]);
    WishlistItem::factory()->for($user)->create(['estimated_price' => 100000]);

    $response = $this->actingAs($user)->getJson('/api/wishlist-items?sort=price');

    expect((int) $response->json('data.0.estimated_price'))->toBe(100000);
});

it('excludes purchased items from the default list but includes archived', function (): void {
    $user = User::factory()->create();

    WishlistItem::factory()->for($user)->create();
    WishlistItem::factory()->for($user)->archived()->create();
    WishlistItem::factory()->for($user)->purchased()->create();

    $response = $this->actingAs($user)->getJson('/api/wishlist-items');

    expect($response->json('meta.total'))->toBe(2);

    $withPurchased = $this->actingAs($user)->getJson('/api/wishlist-items?status=PURCHASED');
    expect($withPurchased->json('meta.total'))->toBe(1);
});

it('updates an owned wishlist item', function (): void {
    $user = User::factory()->create();
    $item = WishlistItem::factory()->for($user)->create();

    $response = $this->actingAs($user)->putJson("/api/wishlist-items/{$item->id}", [
        'priority' => Priority::Low->value,
        'estimated_price' => 123456,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.priority', Priority::Low->value)
        ->assertJsonPath('data.estimated_price', 123456);
});

it('prevents accessing another user wishlist item', function (): void {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $item = WishlistItem::factory()->for($owner)->create();

    $this->actingAs($intruder)->getJson("/api/wishlist-items/{$item->id}")->assertNotFound();
    $this->actingAs($intruder)->putJson("/api/wishlist-items/{$item->id}", ['name' => 'Hacked'])->assertNotFound();
    $this->actingAs($intruder)->patchJson("/api/wishlist-items/{$item->id}/archive")->assertNotFound();
    $this->actingAs($intruder)->patchJson("/api/wishlist-items/{$item->id}/restore")->assertNotFound();
});

it('archives an active item and restores an archived one', function (): void {
    $user = User::factory()->create();
    $item = WishlistItem::factory()->for($user)->create();

    $archived = $this->actingAs($user)->patchJson("/api/wishlist-items/{$item->id}/archive");
    $archived->assertOk()->assertJsonPath('data.status', WishlistStatus::Archived->value);

    $restored = $this->actingAs($user)->patchJson("/api/wishlist-items/{$item->id}/restore");
    $restored->assertOk()->assertJsonPath('data.status', WishlistStatus::Active->value);
});

it('rejects invalid status transitions', function (): void {
    $user = User::factory()->create();

    $archived = WishlistItem::factory()->for($user)->archived()->create();
    $this->actingAs($user)->patchJson("/api/wishlist-items/{$archived->id}/archive")
        ->assertUnprocessable();

    $active = WishlistItem::factory()->for($user)->create();
    $this->actingAs($user)->patchJson("/api/wishlist-items/{$active->id}/restore")
        ->assertUnprocessable();
});

it('never allows purchased items to be modified or restored', function (): void {
    $user = User::factory()->create();
    $item = WishlistItem::factory()->for($user)->purchased()->create();

    $this->actingAs($user)->putJson("/api/wishlist-items/{$item->id}", ['name' => 'Changed'])
        ->assertUnprocessable();
    $this->actingAs($user)->patchJson("/api/wishlist-items/{$item->id}/restore")
        ->assertUnprocessable();
});
