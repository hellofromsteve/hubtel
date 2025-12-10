<?php

namespace HelloFromSteve\Hubtel;

use Illuminate\Support\ServiceProvider;

class HubtelServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/hubtel.php',
            'hubtel'
        );

        $this->app->singleton(HubtelService::class, function ($app) {
            return new HubtelService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/hubtel.php' => config_path('hubtel.php'),
        ], 'hubtel-config');
    }
}

