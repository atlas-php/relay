<?php

declare(strict_types=1);

namespace Atlas\Relay\Services;

use Atlas\Relay\Contracts\InboundRequestGuardInterface;
use Atlas\Relay\Exceptions\InvalidWebhookHeadersException;
use Atlas\Relay\Exceptions\InvalidWebhookPayloadException;
use Atlas\Relay\Support\InboundRequestGuardContext;
use Illuminate\Contracts\Container\Container;

/**
 * Resolves and orchestrates inbound guard classes defined in PRD: Receive Webhook Relay — Guard Validation.
 */
class InboundGuardService
{
    public function __construct(
        private readonly Container $container
    ) {}

    public function resolve(?string $guardClass): ?InboundRequestGuardInterface
    {
        $normalized = $this->normalizeGuardClass($guardClass);

        if ($normalized === null) {
            return null;
        }

        $guard = $this->container->make($normalized);

        if (! $guard instanceof InboundRequestGuardInterface) {
            throw new \InvalidArgumentException(sprintf(
                'Inbound guard [%s] must implement [%s].',
                $normalized,
                InboundRequestGuardInterface::class
            ));
        }

        return $guard;
    }

    public function validate(
        InboundRequestGuardInterface $guard,
        InboundRequestGuardContext $context
    ): void {
        try {
            $guard->validate($context);
        } catch (InvalidWebhookHeadersException|InvalidWebhookPayloadException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw InvalidWebhookPayloadException::fromViolations(
                $context->guardName(),
                [$exception->getMessage()]
            );
        }
    }

    private function normalizeGuardClass(?string $guard): ?string
    {
        if ($guard === null) {
            return null;
        }

        $trimmed = trim($guard);

        return $trimmed === '' ? null : $trimmed;
    }
}
