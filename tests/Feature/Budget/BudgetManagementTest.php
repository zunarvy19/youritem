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
        ->putJson('/api/budget', ['amount' => 2_000_000, 'description' => 'Correct starting balance'])
        ->assertOk()
        ->assertJsonPath('data.amount', 2_000_000);

    expect($user->budget()->count())->toBe(1)
        ->and($user->budget->amount)->toBe(2_000_000);
});

it('creates the budget row on first update', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->putJson('/api/budget', ['amount' => 750_000, 'description' => 'Initial correction'])->assertOk();

    expect($user->budget()->count())->toBe(1);
});

it('validates budget payload', function (array $payload): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->putJson('/api/budget', $payload)
        ->assertUnprocessable();
})->with([
    'negative' => fn (): array => ['amount' => -1, 'description' => 'Correction'],
    'missing' => fn (): array => [],
    'float' => fn (): array => ['amount' => 10.5, 'description' => 'Correction'],
    'string' => fn (): array => ['amount' => 'rich', 'description' => 'Correction'],
]);

it('allows zero budget explicitly', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->putJson('/api/budget', ['amount' => 0, 'description' => 'Reset incorrect balance'])
        ->assertOk()
        ->assertJsonPath('data.amount', 0);
});

it('records income and expense while keeping a cumulative balance', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/budget/transactions', [
        'type' => 'INCOME', 'amount' => 1_000_000, 'description' => 'Salary', 'occurred_at' => '2026-08-25',
    ])->assertCreated();
    $this->actingAs($user)->postJson('/api/budget/transactions', [
        'type' => 'EXPENSE', 'amount' => 250_000, 'description' => 'Groceries',
    ])->assertCreated();

    $this->actingAs($user)->getJson('/api/budget')->assertOk()
        ->assertJsonPath('data.amount', 750_000)
        ->assertJsonPath('data.income', 1_000_000)
        ->assertJsonPath('data.expense', 250_000);
});

it('rejects an expense that would make the balance negative', function (): void {
    $user = User::factory()->create();
    Budget::factory()->for($user)->create(['amount' => 100_000]);

    $this->actingAs($user)->postJson('/api/budget/transactions', [
        'type' => 'EXPENSE', 'amount' => 150_000, 'description' => 'Too much',
    ])->assertUnprocessable()->assertJsonValidationErrors(['amount']);

    expect($user->budget()->firstOrFail()->amount)->toBe(100_000)
        ->and($user->budgetTransactions()->count())->toBe(0);
});

it('records balance corrections instead of silently overwriting the balance', function (): void {
    $user = User::factory()->create();
    Budget::factory()->for($user)->create(['amount' => 500_000]);

    $this->actingAs($user)->putJson('/api/budget', ['amount' => 650_000, 'description' => 'Cash reconciliation'])->assertOk();

    $transaction = $user->budgetTransactions()->firstOrFail();
    expect($transaction->type->value)->toBe('ADJUSTMENT')->and($transaction->amount)->toBe(150_000);
});

it('requires authentication for budget endpoints', function (): void {
    $this->getJson('/api/budget')->assertUnauthorized();
});
