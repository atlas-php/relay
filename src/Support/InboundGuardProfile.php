<?php

declare(strict_types=1);

namespace Atlas\Relay\Support;

/**
 * Immutable inbound guard definition resolved from configuration per PRD: Inbound Guards — Authentication Gate.
 *
 * @phpstan-type GuardHeaderRequirement array{name:string, lookup:string, expected:?string}
 */
class InboundGuardProfile
{
    /**
     * @param  array<int, GuardHeaderRequirement>  $requiredHeaders
     */
    public function __construct(
        public readonly string $name,
        public readonly bool $captureForbidden,
        public readonly array $requiredHeaders,
        public readonly ?string $validatorClass
    ) {}
}
