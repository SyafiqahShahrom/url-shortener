<?php

use App\Http\Controllers\Api\ShortUrlController;
use Illuminate\Support\Facades\Route;

Route::post('/urls', [ShortUrlController::class, 'store'])
    ->middleware('throttle:shorten')
    ->name('api.short-urls.store');
