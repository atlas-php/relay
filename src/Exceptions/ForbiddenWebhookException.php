<?php

declare(strict_types=1);

namespace Atlas\Relay\Exceptions;

/**
 * Exception thrown when inbound provider guards reject a webhook before processing per PRD: Inbound Guards — Authentication Gate.
 */
class ForbiddenWebhookException extends \RuntimeException
{
    /**
     * @param  array<int, string>  $violations
     */
    public function __construct(
        private readonly string $guard,
        private readonly array $violations,
        ?string $message = null
    ) {
        parent::__construct($message ?? self::buildMessage($guard, $violations));
    }

    /**
     * @param  array<int, string>  $violations
     */
    public static function fromViolations(string $guard, array $violations): self
    {
        return new self($guard, $violations, self::buildMessage($guard, $violations));
    }

    /**
     * @return array<int, string>
     */
    public function violations(): array
    {
        return $this->violations;
    }

    public function guard(): string
    {
        return $this->guard;
    }

    public function statusCode(): int
    {
        return 403;
    }

    /**
     * @param  array<int, string>  $violations
     */
    private static function buildMessage(string $guard, array $violations): string
    {
        $summary = $violations === []
            ? 'No additional context provided.'
            : implode(' ', $violations);

        return sprintf('Inbound guard [%s] rejected the request: %s', $guard, $summary);
    }
}
