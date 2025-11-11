<?php

declare(strict_types=1);

namespace AtlasRelay\Facades;

use AtlasRelay\RelayBuilder;
use AtlasRelay\RelayManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;

/**
 * @method static RelayBuilder request(Request $request)
 * @method static RelayBuilder payload(mixed $payload)
 */
class Relay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'atlas-relay.manager';
    }
}
