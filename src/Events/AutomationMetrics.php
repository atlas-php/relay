<?php

declare(strict_types=1);

namespace AtlasRelay\Events;

/**
 * Dispatched after automation commands finish to expose counters for observability.
 */
class AutomationMetrics
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly string $operation,
        public readonly int $count,
        public readonly int $durationMs,
        public readonly array $context = []
    ) {}
}
