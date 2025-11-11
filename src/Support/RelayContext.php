<?php

declare(strict_types=1);

namespace AtlasRelay\Support;

use Illuminate\Http\Request;

class RelayContext
{
    public function __construct(
        public readonly ?Request $request,
        public readonly mixed $payload
    ) {}
}
