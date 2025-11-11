<?php

declare(strict_types=1);

namespace AtlasRelay;

use AtlasRelay\Contracts\RelayManagerInterface;
use Illuminate\Http\Request;

/**
 * Default RelayManager that hands back configured builders per the PRD.
 */
class RelayManager implements RelayManagerInterface
{
    public function request(Request $request): RelayBuilder
    {
        return new RelayBuilder($request);
    }

    public function payload(mixed $payload): RelayBuilder
    {
        return (new RelayBuilder)->payload($payload);
    }
}
