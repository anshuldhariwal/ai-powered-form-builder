<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'app');
Route::view('/login', 'app')->name('login');
Route::view('/register', 'app');

Route::get('/auth/user', function () {
    return response()->json(request()->user());
})->middleware('auth');
