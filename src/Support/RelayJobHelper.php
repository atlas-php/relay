<?php

declare(strict_types=1);

namespace Atlas\Relay\Support;

use Atlas\Relay\Enums\RelayFailure;
use Atlas\Relay\Exceptions\RelayJobFailedException;
use Atlas\Relay\Models\Relay;

/**
 * Helper available inside jobs via the container for interacting with the active relay context.
 */
class RelayJobHelper
{
    public function relay(): ?Relay
    {
        return RelayJobContext::current();
    }

    /**
     * Signal that the current job should fail with a specific reason.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws RelayJobFailedException
     */
    public function fail(RelayFailure $failure, string $message = '', array $attributes = []): void
    {
        throw new RelayJobFailedException($failure, $attributes, $message ?: 'Relay job failed.');
    }
}
