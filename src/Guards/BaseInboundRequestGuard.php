<?php

declare(strict_types=1);

namespace Atlas\Relay\Guards;

use Atlas\Relay\Contracts\InboundRequestGuardInterface;
use Atlas\Relay\Support\InboundRequestGuardContext;

/**
 * Convenience base class for authoring inbound request guards per PRD: Receive Webhook Relay — Guard Validation.
 */
abstract class BaseInboundRequestGuard implements InboundRequestGuardInterface
{
    public function captureHeaderFailure(): bool
    {
        return true;
    }

    public function capturePayloadFailure(): bool
    {
        return true;
    }

    public function validate(InboundRequestGuardContext $context): void
    {
        // Implement validation logic in child guards.
    }
}
