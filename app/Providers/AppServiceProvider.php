<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Public, unauthenticated write endpoint: cap it per client IP to limit
        // spam and database growth from scripted abuse.
        RateLimiter::for('shorten', function (Request $request) {
            return Limit::perMinute(config('shortener.rate_limit_per_minute'))->by($request->ip());
        });
    }
}
