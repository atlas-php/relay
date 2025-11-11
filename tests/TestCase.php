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
}
