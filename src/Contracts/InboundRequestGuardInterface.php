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
     * Return true (default) to persist relays when header validation fails; false skips capture.
     */
    public function captureHeaderFailure(): bool;

    /**
     * Return true (default) to persist relays when payload validation fails; false skips capture.
     */
    public function capturePayloadFailure(): bool;

    /**
     * Validate inbound requests using the provided context helpers and throw guard exceptions on failure.
     */
    public function validate(InboundRequestGuardContext $context): void;
}
