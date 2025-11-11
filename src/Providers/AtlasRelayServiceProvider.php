<?php

declare(strict_types=1);

namespace AtlasRelay\Providers;

use AtlasRelay\Contracts\RelayManagerInterface;
use AtlasRelay\RelayManager;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the relay manager singleton and exposes package infrastructure.
 */
class AtlasRelayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RelayManagerInterface::class, static fn (): RelayManager => new RelayManager());
        $this->app->alias(RelayManagerInterface::class, RelayManager::class);
        $this->app->alias(RelayManagerInterface::class, 'atlas-relay.manager');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../database/migrations' => database_path('migrations'),
            ], 'atlas-relay-migrations');
        }
    }
}
