<?php

declare(strict_types=1);

namespace AtlasRelay;

use AtlasRelay\Contracts\RelayManagerInterface;
use Illuminate\Http\Request;

class RelayManager implements RelayManagerInterface
{
    public function request(Request $request): RelayBuilder
    {
        return new RelayBuilder($request);
    }

    public function payload(mixed $payload): RelayBuilder
    {
        return (new RelayBuilder())->payload($payload);
    }
}
