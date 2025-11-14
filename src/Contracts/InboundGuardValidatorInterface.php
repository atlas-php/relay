<?php

declare(strict_types=1);

namespace Atlas\Relay\Contracts;

use Atlas\Relay\Models\Relay;
use Atlas\Relay\Support\InboundGuardProfile;
use Illuminate\Http\Request;

/**
 * Contract for advanced inbound guard validators defined in PRD: Inbound Guards — Authentication Gate.
 */
interface InboundGuardValidatorInterface
{
    public function validate(Request $request, InboundGuardProfile $profile, ?Relay $relay = null): void;
}
