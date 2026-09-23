<?php

namespace HelloFromSteve\Hubtel;

use Illuminate\Support\ServiceProvider;

class HubtelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/hubtel.php', 'hubtel');

        $this->app->singleton(HubtelService::class, fn () => new HubtelService());
        $this->app->singleton(HubtelInvoiceService::class, fn () => new HubtelInvoiceService());
        $this->app->singleton(HubtelTransferService::class, fn () => new HubtelTransferService());
        $this->app->singleton(HubtelVerificationService::class, fn () => new HubtelVerificationService());
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/hubtel.php' => config_path('hubtel.php'),
            ], 'hubtel-config');
        }
    }
}
