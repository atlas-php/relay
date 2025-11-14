<?php

declare(strict_types=1);

namespace Atlas\Relay\Contracts;

use Atlas\Relay\Support\InboundRequestGuardContext;

/**
 * Defines the inbound webhook guard contract per PRD: Receive Webhook Relay — Guard Validation.
 */
interface InboundRequestGuardInterface
{
    /**
     * Return true (default) to persist the webhook as a failed relay when validation fails;
     * return false to reject the request without capturing anything.
     */
    public function captureFailures(): bool;

    /**
     * Validate inbound requests using the provided context helpers and throw guard exceptions on failure.
     */
    public function validate(InboundRequestGuardContext $context): void;
}
