<?php

declare(strict_types=1);

namespace Atlas\Relay\Events;

use Atlas\Relay\Models\Relay;

/**
 * Fired whenever an outbound attempt begins for a relay.
 */
class RelayAttemptStarted
{
    public function __construct(public Relay $relay) {}
}
