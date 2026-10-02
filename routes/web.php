<?php

use App\Http\Controllers\RedirectToOriginalUrlController;
use App\Services\ShortCodeGenerator;
use Illuminate\Support\Facades\Route;

Route::view('/', 'app')->name('home');

// Keep this last: it matches any 6–8 character alphanumeric path. Anything
// outside that pattern 404s without touching the database.
Route::get('/{shortUrl:short_code}', RedirectToOriginalUrlController::class)
    ->where('shortUrl', ShortCodeGenerator::PATTERN)
    ->name('short-urls.redirect');
