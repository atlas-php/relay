<?php

declare(strict_types=1);

namespace AtlasRelay\Models;

/**
 * Archived relay records retained per retention policies.
 */
class RelayArchive extends AtlasModel
{
    protected function tableNameConfigKey(): string
    {
        return 'atlas-relay.tables.relay_archives';
    }

    protected function defaultTableName(): string
    {
        return 'atlas_relay_archives';
    }
}
