<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('mcp', function (Request $request): Limit {
            return Limit::perMinute(30)->by((string) $request->ip());
        });

        RateLimiter::for('assessment-create', function (Request $request): Limit {
            $email = mb_strtolower(trim((string) $request->input('email', '')));
            $key = hash('sha256', $request->ip().'|'.$email);

            return Limit::perHour(5)->by($key);
        });

        RateLimiter::for('assessment-status', function (Request $request): Limit {
            return Limit::perMinute(60)->by((string) $request->route('assessment').'|'.$request->ip());
        });
    }
}
