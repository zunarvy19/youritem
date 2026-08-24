<?php

use App\Models\Budget;
use App\Models\User;

it('returns zero available budget when the user has no budget row yet', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/budget')
        ->assertOk()
        ->assertJsonPath('data.amount', 0);
});

it('updates the user budget', function (): void {
    $user = User::factory()->create();
    Budget::factory()->for($user)->create(['amount' => 500_000]);

    $this->actingAs($user)
        ->putJson('/api/budget', ['amount' => 2_000_000])
        ->assertOk()
        ->assertJsonPath('data.amount', 2_000_000);

    expect($user->budget()->count())->toBe(1)
        ->and($user->budget->amount)->toBe(2_000_000);
});

it('creates the budget row on first update', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->putJson('/api/budget', ['amount' => 750_000])->assertOk();

    expect($user->budget()->count())->toBe(1);
});

it('validates budget payload', function (array $payload): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->putJson('/api/budget', $payload)
        ->assertUnprocessable();
})->with([
    'negative' => fn (): array => ['amount' => -1],
    'missing' => fn (): array => [],
    'float' => fn (): array => ['amount' => 10.5],
    'string' => fn (): array => ['amount' => 'rich'],
]);

it('allows zero budget explicitly', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->putJson('/api/budget', ['amount' => 0])
        ->assertOk()
        ->assertJsonPath('data.amount', 0);
});

it('requires authentication for budget endpoints', function (): void {
    $this->getJson('/api/budget')->assertUnauthorized();
});
