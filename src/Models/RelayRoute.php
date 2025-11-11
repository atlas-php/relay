<?php

declare(strict_types=1);

namespace AtlasRelay\Models;

/**
 * Configured route definitions used for AutoRouting decisions.
 */
class RelayRoute extends AtlasModel
{
    protected function tableNameConfigKey(): string
    {
        return 'atlas-relay.tables.relay_routes';
    }

    protected function defaultTableName(): string
    {
        return 'atlas_relay_routes';
    }
}
