<?php

declare(strict_types=1);

namespace AtlasRelay\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * Configured route definitions used for AutoRouting decisions defined in the Routing PRD.
 */
class RelayRoute extends AtlasModel
{
    /**
     * @var array<string, string>
     */
    protected $casts = [
        'headers' => 'array',
        'retry_policy' => 'array',
        'is_retry' => 'boolean',
        'is_delay' => 'boolean',
        'enabled' => 'boolean',
        'retry_seconds' => 'integer',
        'retry_max_attempts' => 'integer',
        'delay_seconds' => 'integer',
        'timeout_seconds' => 'integer',
        'http_timeout_seconds' => 'integer',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    protected function tableNameConfigKey(): string
    {
        return 'atlas-relay.tables.relay_routes';
    }

    protected function defaultTableName(): string
    {
        return 'atlas_relay_routes';
    }
}
