<?php

declare(strict_types=1);

namespace AtlasRelay\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * Represents the authoritative live relay record specified in the Payload Capture, Routing, and Outbound Delivery PRDs.
 */
class Relay extends AtlasModel
{
    /**
     * @var array<string, string>
     */
    protected $casts = [
        'headers' => 'array',
        'payload' => 'array',
        'response_payload' => 'array',
        'meta' => 'array',
        'is_retry' => 'boolean',
        'is_delay' => 'boolean',
        'response_payload_truncated' => 'boolean',
        'retry_seconds' => 'integer',
        'retry_max_attempts' => 'integer',
        'attempt_count' => 'integer',
        'max_attempts' => 'integer',
        'delay_seconds' => 'integer',
        'timeout_seconds' => 'integer',
        'http_timeout_seconds' => 'integer',
        'last_attempt_duration_ms' => 'integer',
        'response_status' => 'integer',
        'failure_reason' => 'integer',
        'route_id' => 'integer',
        'retry_at' => 'immutable_datetime',
        'first_attempted_at' => 'immutable_datetime',
        'last_attempted_at' => 'immutable_datetime',
        'processing_started_at' => 'immutable_datetime',
        'processing_finished_at' => 'immutable_datetime',
        'completed_at' => 'immutable_datetime',
        'failed_at' => 'immutable_datetime',
        'cancelled_at' => 'immutable_datetime',
        'archived_at' => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public function scopeDueForRetry(Builder $query): Builder
    {
        return $query
            ->where('is_retry', true)
            ->whereNull('archived_at')
            ->whereNotNull('retry_at')
            ->where('retry_at', '<=', now());
    }

    public function scopeUnarchived(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    protected function tableNameConfigKey(): string
    {
        return 'atlas-relay.tables.relays';
    }

    protected function defaultTableName(): string
    {
        return 'atlas_relays';
    }
}
