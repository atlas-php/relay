<?php

declare(strict_types=1);

namespace AtlasRelay\Contracts;

use AtlasRelay\RelayBuilder;
use Illuminate\Http\Request;

interface RelayManagerInterface
{
    public function request(Request $request): RelayBuilder;

    public function payload(mixed $payload): RelayBuilder;
}
