<?php

declare(strict_types=1);

namespace Atlas\Relay\Events;

use Atlas\Relay\Enums\RelayFailure;
use Atlas\Relay\Models\Relay;

/**
 * Fired when a relay attempt fails.
 */
class RelayFailed
{
    public function __construct(
        public Relay $relay,
        public RelayFailure $failure,
        public ?int $durationMs = null
    ) {}
}
