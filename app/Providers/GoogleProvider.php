<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Service\Google\GoogleClient;
use Illuminate\Contracts\Foundation\Application;

class GoogleProvider extends ServiceProvider
{
    protected $client;

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(GoogleClient::class, function (Application $app) {

            return new GoogleClient();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void {}
}
