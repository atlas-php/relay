<?php

declare(strict_types=1);

namespace Atlas\Relay\Support;

use Atlas\Relay\Enums\HttpMethod;
use Atlas\Relay\Enums\RelayFailure;
use Atlas\Relay\Exceptions\RelayHttpException;
use Atlas\Relay\Models\Relay;
use Atlas\Relay\Services\RelayLifecycleService;
use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JsonSerializable;
use RuntimeException;
use Traversable;

/**
 * Proxy around Laravel's HTTP client that enforces PRD rules and records relay lifecycle data.
 *
 * @method Response get(string $url, mixed ...$arguments)
 * @method Response post(string $url, mixed ...$arguments)
 * @method Response put(string $url, mixed ...$arguments)
 * @method Response patch(string $url, mixed ...$arguments)
 * @method Response delete(string $url, mixed ...$arguments)
 * @method Response head(string $url, mixed ...$arguments)
 *
 * @mixin PendingRequest
 */
class RelayHttpClient
{
    /**
     * @var array<string>
     */
    private array $verbs = [
        'get', 'post', 'put', 'patch', 'delete', 'head',
    ];

    public function __construct(
        private PendingRequest $pendingRequest,
        private readonly RelayLifecycleService $lifecycle,
        private readonly Closure $relayResolver,
        private readonly ?Closure $headerRecorder = null
    ) {}

    private ?Relay $resolvedRelay = null;

    /**
     * @param  array<int, mixed>  $arguments
     */
    public function __call(string $method, array $arguments): mixed
    {
        $normalized = strtolower($method);

        if (in_array($normalized, $this->verbs, true)) {
            return $this->send($normalized, ...$arguments);
        }

        $result = $this->pendingRequest->{$method}(...$arguments);

        if ($result instanceof PendingRequest) {
            $this->pendingRequest = $result;

            return $this;
        }

        return $result;
    }

    /**
     * @param  mixed  ...$arguments
     */
    private function send(string $method, ...$arguments): Response
    {
        $url = $arguments[0] ?? null;
        $resolvedMethod = HttpMethod::tryFromMixed($method);

        $this->recordPendingHeaders();
        $relay = $this->relay();

        try {
            if (! is_string($url)) {
                throw new RelayHttpException(
                    'HTTP relay calls require a target URL.',
                    RelayFailure::HTTP_ERROR
                );
            }

            if ($resolvedMethod === null) {
                $this->reportInvalidMethod($relay, $method);

                throw new RelayHttpException(
                    sprintf('Unsupported HTTP method [%s] for relay delivery.', $method),
                    RelayFailure::HTTP_ERROR
                );
            }

            $this->registerPayloadFromArguments($relay, $arguments);
            $this->registerDestination($relay, $url, $resolvedMethod);
        } catch (RelayHttpException $exception) {
            $failure = $exception->failure() ?? RelayFailure::HTTP_ERROR;

            $this->lifecycle->markFailed($relay, $failure);
            $this->lifecycle->recordResponse($relay, null, $exception->getMessage());

            throw $exception;
        }

        $relay = $this->lifecycle->startAttempt($relay);
        $startedAt = microtime(true);

        try {
            /** @var Response $response */
            $response = $this->pendingRequest->{$method}(...$arguments);
        } catch (ConnectionException $exception) {
            $duration = $this->durationSince($startedAt);
            $failure = $this->failureForConnectionException($exception);
            $this->lifecycle->markFailed($relay, $failure, [], $duration);

            throw $exception;
        } catch (RequestException $exception) {
            $duration = $this->durationSince($startedAt);
            $this->lifecycle->markFailed($relay, RelayFailure::HTTP_ERROR, [], $duration);

            throw $exception;
        }

        $duration = $this->durationSince($startedAt);

        $payload = $this->normalizePayload($response);

        $this->lifecycle->recordResponse($relay, $response->status(), $payload);

        if ($response->successful()) {
            $this->lifecycle->markCompleted($relay, [], $duration);
        } else {
            $this->lifecycle->markFailed($relay, RelayFailure::HTTP_ERROR, [], $duration);
        }

        return $response;
    }

    private function durationSince(float $startedAt): int
    {
        return (int) max(0, round((microtime(true) - $startedAt) * 1000));
    }

    private function relay(): Relay
    {
        if ($this->resolvedRelay instanceof Relay) {
            return $this->resolvedRelay;
        }

        $resolver = $this->relayResolver;
        $relay = $resolver();

        if (! $relay instanceof Relay) {
            throw new RuntimeException('Relay resolver must return a Relay instance.');
        }

        $this->resolvedRelay = $relay;

        return $this->resolvedRelay;
    }

    private function recordPendingHeaders(): void
    {
        if ($this->headerRecorder === null) {
            return;
        }

        $headers = $this->pendingRequest->getOptions()['headers'] ?? [];

        if (! is_array($headers) || $headers === []) {
            return;
        }

        $recorder = $this->headerRecorder;
        $recorder($headers);
    }

    private function failureForConnectionException(ConnectionException $exception): RelayFailure
    {
        return Str::contains(strtolower($exception->getMessage()), 'timed out')
            ? RelayFailure::CONNECTION_TIMEOUT
            : RelayFailure::CONNECTION_ERROR;
    }

    private function reportInvalidMethod(Relay $relay, string $method): void
    {
        Log::warning('atlas-relay:http-method-invalid', [
            'relay_id' => $relay->id,
            'method' => $method,
            'allowed' => HttpMethod::values(),
        ]);
    }

    private function truncatePayload(?string $payload): ?string
    {
        if ($payload === null) {
            return null;
        }

        $maxBytes = (int) config('atlas-relay.payload_max_bytes', 64 * 1024);

        return strlen($payload) > $maxBytes
            ? substr($payload, 0, $maxBytes)
            : $payload;
    }

    private function normalizePayload(Response $response): mixed
    {
        $json = $response->json();

        if (is_array($json)) {
            return $json;
        }

        return $this->truncatePayload($response->body());
    }

    /**
     * @param  array<int, mixed>  $arguments
     */
    private function registerPayloadFromArguments(Relay $relay, array $arguments): void
    {
        if ($relay->payload !== null) {
            return;
        }

        $payload = $arguments[1] ?? null;

        if ($payload === null) {
            return;
        }

        $normalized = $this->normalizeOutgoingPayload($payload);

        if ($normalized === null) {
            return;
        }

        $maxBytes = (int) config('atlas-relay.payload_max_bytes', 64 * 1024);
        $payloadBytes = $this->payloadSize($normalized);

        if ($payloadBytes > $maxBytes) {
            throw new RelayHttpException(
                sprintf('Payload exceeds configured limit of %d bytes.', $maxBytes),
                RelayFailure::PAYLOAD_TOO_LARGE
            );
        }

        $relay->forceFill(['payload' => $normalized])->save();
    }

    private function normalizeOutgoingPayload(mixed $payload): mixed
    {
        if ($payload instanceof Arrayable) {
            return $payload->toArray();
        }

        if ($payload instanceof JsonSerializable) {
            $payload = $payload->jsonSerialize();
        } elseif ($payload instanceof Traversable) {
            $payload = iterator_to_array($payload);
        } elseif (is_object($payload) && method_exists($payload, 'toArray')) {
            $converted = $payload->toArray();

            if (is_array($converted)) {
                $payload = $converted;
            }
        }

        if (is_array($payload)) {
            return $payload;
        }

        if ($payload instanceof \stdClass) {
            return (array) $payload;
        }

        if (is_scalar($payload) || $payload === null) {
            return $payload;
        }

        if (is_object($payload) && method_exists($payload, '__toString')) {
            return (string) $payload;
        }

        return null;
    }

    private function payloadSize(mixed $payload): int
    {
        if ($payload === null) {
            return 0;
        }

        if (is_string($payload)) {
            return strlen($payload);
        }

        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);

        if ($encoded === false) {
            return 0;
        }

        return strlen($encoded);
    }

    private function registerDestination(Relay $relay, string $url, HttpMethod $method): void
    {
        $maxLength = 255;

        if (strlen($url) > $maxLength) {
            throw new RelayHttpException(
                sprintf('URL may not exceed %d characters; received %d.', $maxLength, strlen($url)),
                RelayFailure::HTTP_ERROR
            );
        }

        $attributes = [];

        if ($relay->url !== $url) {
            $attributes['url'] = $url;
        }

        if ($relay->method?->value !== $method->value) {
            $attributes['method'] = $method;
        }

        if ($attributes === []) {
            return;
        }

        $relay->forceFill($attributes)->save();
    }
}
