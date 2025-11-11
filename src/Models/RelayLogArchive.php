<?php

declare(strict_types=1);

namespace AtlasRelay\Models;

/**
 * Archived log entries that mirror the live relay log schema.
 */
class RelayLogArchive extends AtlasModel
{
    protected function tableNameConfigKey(): string
    {
        return 'atlas-relay.tables.relay_log_archives';
    }

    protected function defaultTableName(): string
    {
        return 'atlas_relay_log_archives';
    }
}
