<?php

declare(strict_types=1);

namespace Atlas\Relay\Support;

use Atlas\Relay\Models\Relay;

/**
 * Stores per-job relay context so jobs can introspect or signal failures.
 */
class RelayJobContext
{
    private static ?Relay $current = null;

    public static function set(Relay $relay): void
    {
        self::$current = $relay;
    }

    public static function current(): ?Relay
    {
        return self::$current;
    }

    public static function clear(): void
    {
        self::$current = null;
    }
}
