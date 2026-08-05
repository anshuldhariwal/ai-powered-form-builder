<?php

use Illuminate\Support\Facades\Route;

test('the application returns a successful response', function () {
    $this->get('/')->assertOk();
});

test('forwarded HTTPS is used for browser asset URLs', function () {
    Route::get('/_test/forwarded-scheme', fn (): string => request()->secure() ? 'secure' : 'insecure');

    $this->withHeader('X-Forwarded-Proto', 'https')
        ->get('/_test/forwarded-scheme')
        ->assertOk()
        ->assertSeeText('secure');
});
