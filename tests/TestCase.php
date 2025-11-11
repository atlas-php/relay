<?php

declare(strict_types=1);

namespace AtlasRelay\Tests;

use AtlasRelay\Facades\Relay;
use AtlasRelay\Providers\AtlasRelayServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            AtlasRelayServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Relay' => Relay::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
