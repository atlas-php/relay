<?php

declare(strict_types=1);

namespace AtlasRelay\Models;

/**
 * Immutable audit log entries tied to relay lifecycle events.
 */
class RelayLog extends AtlasModel
{
    protected function tableNameConfigKey(): string
    {
        return 'atlas-relay.tables.relay_logs';
    }

    protected function defaultTableName(): string
    {
        return 'atlas_relay_logs';
    }
}
