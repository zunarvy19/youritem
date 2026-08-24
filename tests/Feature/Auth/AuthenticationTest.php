<?php

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Hash;

it('registers a new user and starts an authenticated session', function (): void {
    $response = $this->withoutMiddleware(ValidateCsrfToken::class)
        ->withHeaders(['referer' => 'http://localhost'])
        ->postJson('/api/register', [
            'name' => 'Arvy',
            'email' => 'arvy@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Arvy')
        ->assertJsonPath('data.email', 'arvy@example.com')
        ->assertJsonMissing(['password']);

    expect(User::whereEmail('arvy@example.com')->exists())->toBeTrue()
        ->and($this->app['auth']->guard()->check())->toBeTrue();
});

it('rejects registration with invalid data', function (callable $payload): void {
    $response = $this->postJson('/api/register', $payload());

    $response->assertUnprocessable()->assertJsonValidationErrors(
        array_keys($response->json('errors')),
    );
})->with([
    'missing name' => fn (): array => ['email' => 'a@b.com', 'password' => 'password123', 'password_confirmation' => 'password123'],
    'invalid email' => fn (): array => ['name' => 'A', 'email' => 'not-an-email', 'password' => 'password123', 'password_confirmation' => 'password123'],
    'short password' => fn (): array => ['name' => 'A', 'email' => 'a@b.com', 'password' => 'short', 'password_confirmation' => 'short'],
]);

it('prevents duplicate email registration', function (): void {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson('/api/register', [
        'name' => 'Someone',
        'email' => 'taken@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertUnprocessable()->assertJsonValidationErrors(['email']);
});

it('logs in with valid credentials', function (): void {
    $user = User::factory()->create([
        'email' => 'arvy@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->withoutMiddleware(ValidateCsrfToken::class)
        ->withHeaders(['referer' => 'http://localhost'])
        ->postJson('/api/login', [
            'email' => 'arvy@example.com',
            'password' => 'password123',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertSessionHas(auth()->guard()->getName());
});

it('rejects login with invalid credentials without leaking which field is wrong', function (): void {
    User::factory()->create([
        'email' => 'arvy@example.com',
        'password' => Hash::make('password123'),
    ]);

    $this->postJson('/api/login', [
        'email' => 'arvy@example.com',
        'password' => 'wrong-password',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['email'])
        ->assertJsonMissing(['password_hash']);
});

it('logs out the authenticated user and invalidates the session', function (): void {
    User::factory()->create([
        'email' => 'arvy@example.com',
        'password' => Hash::make('password123'),
    ]);

    $this->withoutMiddleware(ValidateCsrfToken::class)
        ->withHeaders(['referer' => 'http://localhost'])
        ->postJson('/api/login', [
            'email' => 'arvy@example.com',
            'password' => 'password123',
        ])->assertOk();

    $sessionKey = auth()->guard()->getName();

    expect($this->app['session.store']->all())->toHaveKey($sessionKey);

    $this->postJson('/api/logout')->assertOk();

    expect($this->app['session.store']->all())->not->toHaveKey($sessionKey);
});

it('requires authentication for the current user endpoint', function (): void {
    $this->getJson('/api/user')->assertUnauthorized();
});

it('returns the authenticated user data without password', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonStructure(['data' => ['id', 'name', 'email']])
        ->assertJsonMissing(['password']);
});
