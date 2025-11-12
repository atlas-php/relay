<?php

declare(strict_types=1);

namespace Atlas\Relay\Events;

use Atlas\Relay\Models\Relay;

/**
 * Fired when a relay attempt finishes successfully.
 */
class RelayCompleted
{
    public function __construct(public Relay $relay, public ?int $durationMs = null) {}
}
