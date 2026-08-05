<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

const CSRF_TOKEN = 'authentication-test-token';

test('a user can register through the JSON endpoint', function () {
    $response = $this->withSession(['_token' => CSRF_TOKEN])
        ->withHeader('X-CSRF-TOKEN', CSRF_TOKEN)
        ->postJson('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

    $response->assertSuccessful();
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
});

test('a user can log in and log out through JSON endpoints', function () {
    $user = User::factory()->create(['password' => 'password123']);

    $this->withSession(['_token' => CSRF_TOKEN])
        ->withHeader('X-CSRF-TOKEN', CSRF_TOKEN)
        ->postJson('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertSuccessful();

    $this->assertAuthenticatedAs($user);

    $this->withSession(['_token' => CSRF_TOKEN])
        ->withHeader('X-CSRF-TOKEN', CSRF_TOKEN)
        ->postJson('/logout')
        ->assertSuccessful();

    $this->assertGuest();
});
