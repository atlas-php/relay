<?php

declare(strict_types=1);

namespace Atlas\Relay\Support;

use Atlas\Relay\Exceptions\InvalidWebhookHeadersException;
use Atlas\Relay\Exceptions\InvalidWebhookPayloadException;
use Atlas\Relay\Models\Relay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Value object exposing normalized inbound request data to guard classes per PRD: Receive Webhook Relay — Guard Validation.
 *
 * @phpstan-type HeaderMap array<string, string>
 */
class InboundRequestGuardContext
{
    /**
     * @param  HeaderMap  $headers
     */
    public function __construct(
        private readonly Request $request,
        private readonly array $headers,
        private readonly mixed $payload,
        private readonly string $guardName,
        private readonly ?Relay $relay = null
    ) {
        $this->headerLookup = $this->normalizeHeaderLookup($headers);
    }

    public function request(): Request
    {
        return $this->request;
    }

    /**
     * Returns the normalized header list captured during Relay::request().
     *
     * @return HeaderMap
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Performs a case-insensitive header lookup.
     */
    public function header(string $name): ?string
    {
        $normalized = strtolower($name);

        return $this->headerLookup[$normalized] ?? null;
    }

    /**
     * Returns the normalized payload array/object decoded from the request.
     */
    public function payload(): mixed
    {
        return $this->payload;
    }

    public function guardName(): string
    {
        return $this->guardName;
    }

    /**
     * Relay model captured prior to guard execution when captureFailures() is true.
     */
    public function relay(): ?Relay
    {
        return $this->relay;
    }

    public function requireHeader(string $name, ?string $expected = null): string
    {
        $value = $this->header($name);

        if ($value === null) {
            $this->failHeaders([sprintf('Missing required header [%s].', $name)]);
        }

        if ($expected !== null && $value !== $expected) {
            $this->failHeaders([sprintf('Header [%s] did not match the expected value.', $name)]);
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $rules
     * @param  array<string, string>  $messages
     * @param  array<string, string>  $attributes
     * @return array<string, mixed>
     */
    public function validateHeaders(array $rules, array $messages = [], array $attributes = []): array
    {
        return $this->runValidator(
            $this->headers,
            $rules,
            $messages,
            $attributes,
            true
        );
    }

    /**
     * @param  array<string, mixed>  $rules
     * @param  array<string, string>  $messages
     * @param  array<string, string>  $attributes
     * @return array<string, mixed>
     */
    public function validatePayload(array $rules, array $messages = [], array $attributes = []): array
    {
        $payload = $this->arrayPayload();

        return $this->runValidator(
            $payload,
            $rules,
            $messages,
            $attributes,
            false
        );
    }

    /**
     * @param  array<int, string>  $violations
     */
    public function failHeaders(array $violations): never
    {
        throw InvalidWebhookHeadersException::fromViolations($this->guardName, $violations);
    }

    /**
     * @param  array<int, string>  $violations
     */
    public function failPayload(array $violations): never
    {
        throw InvalidWebhookPayloadException::fromViolations($this->guardName, $violations);
    }

    /**
     * @param  HeaderMap  $headers
     * @return array<string, string>
     */
    private function normalizeHeaderLookup(array $headers): array
    {
        $lookup = [];

        foreach ($headers as $name => $value) {
            $lookup[strtolower($name)] = $value;
        }

        return $lookup;
    }

    /** @var array<string, string> */
    private array $headerLookup = [];

    /**
     * @return array<string, mixed>
     */
    private function arrayPayload(): array
    {
        if (is_array($this->payload)) {
            return $this->payload;
        }

        $this->failPayload(['Payload must be an array to run validation.']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $rules
     * @param  array<string, string>  $messages
     * @param  array<string, string>  $attributes
     * @return array<string, mixed>
     */
    private function runValidator(
        array $data,
        array $rules,
        array $messages,
        array $attributes,
        bool $isHeaderValidation
    ): array {
        try {
            return Validator::make($data, $rules, $messages, $attributes)->validate();
        } catch (ValidationException $exception) {
            $violations = $this->flattenErrors($exception->errors());

            if ($isHeaderValidation) {
                $this->failHeaders($violations);
            }

            $this->failPayload($violations);
        }
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     * @return array<int, string>
     */
    private function flattenErrors(array $errors): array
    {
        $messages = [];

        foreach ($errors as $field => $fieldErrors) {
            foreach ($fieldErrors as $message) {
                $messages[] = sprintf('%s: %s', $field, $message);
            }
        }

        return $messages;
    }
}
