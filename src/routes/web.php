<?php

use App\Http\Controllers\FormController;
use App\Http\Controllers\PublicFormController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'app');
Route::view('/login', 'app')->name('login');
Route::view('/register', 'app');
Route::view('/forms/{path?}', 'app')->where('path', '.*');
Route::view('/f/{tenantSlug}/{formSlug}', 'app');

Route::get('/auth/user', function () {
    return response()->json(request()->user());
})->middleware('auth');

Route::middleware('auth')->prefix('api')->group(function () {
    Route::get('/forms', [FormController::class, 'index']);
    Route::post('/forms', [FormController::class, 'store']);
    Route::get('/forms/{publicId}', [FormController::class, 'show']);
    Route::put('/forms/{publicId}', [FormController::class, 'update']);
    Route::post('/forms/{publicId}/publish', [FormController::class, 'publish']);
    Route::get('/forms/{publicId}/submissions', [FormController::class, 'submissions']);
    Route::get('/forms/{publicId}/submissions/export', [FormController::class, 'export']);
    Route::get('/forms/{publicId}/submissions/{submissionId}', [FormController::class, 'submission']);
    Route::get('/forms/{publicId}/submissions/{submissionId}/files/{fileId}', [FormController::class, 'download']);
});

Route::get('/api/public/forms/{tenantSlug}/{formSlug}', [PublicFormController::class, 'show']);
Route::post('/api/public/forms/{tenantSlug}/{formSlug}', [PublicFormController::class, 'store'])->middleware('throttle:20,1');
