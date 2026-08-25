<?php

use App\Enums\Priority;
use App\Enums\Purpose;
use App\Models\Budget;
use App\Models\User;
use App\Models\WishlistItem;
use Database\Seeders\CategorySeeder;

beforeEach(function (): void {
    $this->seed(CategorySeeder::class);
});

function activeItem(User $user, int $price = 500_000): WishlistItem
{
    return WishlistItem::factory()->for($user)->create([
        'purpose' => Purpose::Need,
        'priority' => Priority::High,
        'estimated_price' => $price,
    ]);
}

it('completes a valid purchase and updates all state consistently', function (): void {
    $user = User::factory()->create();
    Budget::factory()->for($user)->create(['amount' => 1_000_000]);
    $item = activeItem($user, price: 500_000);

    $response = $this->actingAs($user)
        ->postJson("/api/wishlist-items/{$item->id}/purchase", [
            'actual_price' => 450_000,
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.actual_price', 450_000)
        ->assertJsonPath('data.wishlist_item.id', $item->id);

    expect($item->refresh()->status->value)->toBe('PURCHASED')
        ->and($user->budget()->first()->amount)->toBe(550_000)
        ->and($user->purchases()->count())->toBe(1);

    $ledger = $user->budgetTransactions()->firstOrFail();
    expect($ledger->type->value)->toBe('EXPENSE')->and($ledger->amount)->toBe(-450_000)->and($ledger->purchase_id)->not->toBeNull();
});

it('uses the actual purchase price for budget deduction, not the estimated price', function (): void {
    $user = User::factory()->create();
    Budget::factory()->for($user)->create(['amount' => 1_000_000]);
    $item = activeItem($user, price: 800_000);

    $this->actingAs($user)
        ->postJson("/api/wishlist-items/{$item->id}/purchase", ['actual_price' => 300_000])
        ->assertCreated();

    expect($user->budget()->first()->amount)->toBe(700_000)
        // Estimated price must remain untouched.
        ->and($item->refresh()->estimated_price)->toBe(800_000);
});

it('allows the actual purchase price to be higher than estimated', function (): void {
    $user = User::factory()->create();
    Budget::factory()->for($user)->create(['amount' => 600_000]);
    $item = activeItem($user, price: 500_000);

    $this->actingAs($user)
        ->postJson("/api/wishlist-items/{$item->id}/purchase", ['actual_price' => 550_000])
        ->assertCreated();

    expect($user->budget()->first()->amount)->toBe(50_000);
});

it('rejects purchases exceeding the available budget without side effects', function (): void {
    $user = User::factory()->create();
    Budget::factory()->for($user)->create(['amount' => 400_000]);
    $item = activeItem($user, price: 500_000);

    $this->actingAs($user)
        ->postJson("/api/wishlist-items/{$item->id}/purchase", ['actual_price' => 450_000])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['actual_price']);

    expect($user->purchases()->count())->toBe(0)
        ->and($item->refresh()->status->value)->toBe('ACTIVE')
        ->and($user->budget()->first()->amount)->toBe(400_000);
});

it('revalidates against current budget even when recommendation said affordable', function (): void {
    $user = User::factory()->create();
    Budget::factory()->for($user)->create(['amount' => 1_000_000]);

    $first = activeItem($user, price: 900_000);
    $second = activeItem($user, price: 500_000);

    // First purchase consumes almost everything; second item looked affordable
    // before but must now be rejected based on fresh budget state.
    $this->actingAs($user)
        ->postJson("/api/wishlist-items/{$first->id}/purchase", ['actual_price' => 900_000])
        ->assertCreated();

    $this->actingAs($user)
        ->postJson("/api/wishlist-items/{$second->id}/purchase", ['actual_price' => 500_000])
        ->assertUnprocessable();
});

it('prevents purchasing the same item twice', function (): void {
    $user = User::factory()->create();
    Budget::factory()->for($user)->create(['amount' => 2_000_000]);
    $item = activeItem($user);

    $this->actingAs($user)
        ->postJson("/api/wishlist-items/{$item->id}/purchase", ['actual_price' => 500_000])
        ->assertCreated();

    $this->actingAs($user)
        ->postJson("/api/wishlist-items/{$item->id}/purchase", ['actual_price' => 500_000])
        ->assertUnprocessable();

    expect($user->purchases()->count())->toBe(1);
});

it('rejects purchasing archived items', function (): void {
    $user = User::factory()->create();
    Budget::factory()->for($user)->create(['amount' => 1_000_000]);
    $item = WishlistItem::factory()->for($user)->archived()->create([
        'estimated_price' => 100_000,
    ]);

    $this->actingAs($user)
        ->postJson("/api/wishlist-items/{$item->id}/purchase", ['actual_price' => 100_000])
        ->assertUnprocessable();
});

it('prevents purchasing another user wishlist item', function (): void {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    Budget::factory()->for($intruder)->create(['amount' => 5_000_000]);
    $item = activeItem($owner);

    $this->actingAs($intruder)
        ->postJson("/api/wishlist-items/{$item->id}/purchase", ['actual_price' => 100_000])
        ->assertNotFound();

    expect($owner->purchases()->count())->toBe(0)
        ->and($intruder->budget()->first()->amount)->toBe(5_000_000);
});

it('validates purchase payload', function (array $payload): void {
    $user = User::factory()->create();
    Budget::factory()->for($user)->create(['amount' => 1_000_000]);
    $item = activeItem($user);

    $this->actingAs($user)
        ->postJson("/api/wishlist-items/{$item->id}/purchase", $payload)
        ->assertUnprocessable();
})->with([
    'zero price' => fn (): array => ['actual_price' => 0],
    'negative price' => fn (): array => ['actual_price' => -100],
    'missing price' => fn (): array => [],
    'invalid date' => fn (): array => ['actual_price' => 100, 'purchased_at' => 'not-a-date'],
]);

it('accepts an explicit purchase date', function (): void {
    $user = User::factory()->create();
    Budget::factory()->for($user)->create(['amount' => 1_000_000]);
    $item = activeItem($user);

    $this->actingAs($user)
        ->postJson("/api/wishlist-items/{$item->id}/purchase", [
            'actual_price' => 500_000,
            'purchased_at' => '2026-08-01T10:00:00Z',
        ])
        ->assertCreated();

    expect($user->purchases()->first()->purchased_at->format('Y-m-d'))->toBe('2026-08-01');
});

it('defaults purchased_at to now when not provided', function (): void {
    $user = User::factory()->create();
    Budget::factory()->for($user)->create(['amount' => 1_000_000]);
    $item = activeItem($user);

    $this->actingAs($user)
        ->postJson("/api/wishlist-items/{$item->id}/purchase", ['actual_price' => 500_000])
        ->assertCreated();

    expect($user->purchases()->first()->purchased_at->isToday())->toBeTrue();
});

it('lists purchase history for the owning user only', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    foreach ([$userA, $userB] as $user) {
        Budget::factory()->for($user)->create(['amount' => 3_000_000]);
        $item = activeItem($user);
        $user->purchases()->create([
            'wishlist_item_id' => $item->id,
            'actual_price' => 100_000,
            'purchased_at' => now(),
        ]);
    }

    $response = $this->actingAs($userA)->getJson('/api/purchases');

    $response->assertOk();
    expect($response->json('meta.total'))->toBe(1)
        ->and($response->json('data.0.wishlist_item.name'))->not->toBeNull();
});

it('requires authentication for purchase endpoints', function (): void {
    $this->getJson('/api/purchases')->assertUnauthorized();
});
