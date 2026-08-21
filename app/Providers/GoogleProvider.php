<?php

namespace App\Providers;

use App\Service\Google\GoogleClient;
use Illuminate\Support\ServiceProvider;

class GoogleProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(GoogleClient::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void {}
}
