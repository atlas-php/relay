<?php

declare(strict_types=1);

namespace AtlasRelay\Models;

/**
 * Represents the live relay lifecycle record defined in the PRDs.
 */
class Relay extends AtlasModel
{
    protected function tableNameConfigKey(): string
    {
        return 'atlas-relay.tables.relays';
    }

    protected function defaultTableName(): string
    {
        return 'atlas_relays';
    }
}
