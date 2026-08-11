<?php

namespace App\Providers;

use App\Service\Google\GoogleClient;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class GoogleProvider extends ServiceProvider
{
    protected ?GoogleClient $client = null;

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(GoogleClient::class, function (Application $app) {

            return new GoogleClient;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void {}
}
