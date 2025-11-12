<?php

declare(strict_types=1);

namespace Atlas\Relay\Events;

use Atlas\Relay\Models\Relay;

/**
 * Fired when a relay is requeued by automation commands.
 */
class RelayRequeued
{
    public function __construct(public Relay $relay) {}
}
