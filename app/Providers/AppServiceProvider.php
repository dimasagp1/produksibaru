<?php

namespace App\Providers;
use Illuminate\Support\Facades\URL;
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
        if (!app()->runningInConsole()) {
            $host = request()->getHost();
            $isNgrok = str_contains($host, 'ngrok');
            $isHttps = request()->isSecure() || request()->header('X-Forwarded-Proto') === 'https';

            if ($isNgrok || $isHttps) {
                URL::forceScheme('https');
            }
        }
    }
}
