
# PRD — Receive Webhook Relay

## Overview

Receive Webhook Relay is the first stage of Atlas Relay. It guarantees that every inbound webhook request (or internal payload) is captured, normalized, validated, and stored before any business logic executes. Guard classes authenticate requests up front, the payload extractor normalizes JSON bodies, and the resulting relay record becomes the system of record for downstream delivery.

## Relay Schema (`atlas_relays`)

| Field                  | Description                                                                 |
|------------------------|-----------------------------------------------------------------------------|
| `id`                   | Relay identifier.                                                           |
| `type`                 | `RelayType` enum. Inbound captures store `INBOUND`; other flows may use `OUTBOUND` or `RELAY`. |
| `status`               | `RelayStatus` enum (Queued, Processing, Completed, Failed, Cancelled).      |
| `provider`             | Optional integration/provider label (indexed).                              |
| `reference_id`         | Optional consumer-provided reference (indexed).                             |
| `source_ip`            | Inbound IPv4 address detected from the HTTP request.                        |
| `headers`              | Normalized header JSON with sensitive keys masked.                          |
| `payload`              | Stored JSON payload (truncated when the capture limit is exceeded).         |
| `method`               | HTTP verb detected from the request (if present).                           |
| `url`                  | Full inbound URL (or target URL for outbound calls).                        |
| `failure_reason`       | `RelayFailure` enum for capture or downstream failures.                     |
| `meta`                 | Consumer-defined JSON metadata captured alongside the relay.                |
| `response_http_status` | Last recorded outbound HTTP status.                                         |
| `response_payload`     | Truncated outbound response payload.                                        |
| `processing_at`        | Timestamp for when processing began.                                        |
| `completed_at`         | Timestamp for when the relay finished (success/failure/cancel).             |
| `created_at`           | Capture timestamp.                                                          |
| `updated_at`           | Last state change.                                                          |

## RelayType enum (`Enums\RelayType`)

| Value | Label    | Usage                                                            |
|-------|----------|------------------------------------------------------------------|
| 1     | INBOUND  | Automatically applied when `Relay::request()` captures a webhook.|
| 2     | OUTBOUND | Applied when issuing webhooks directly via `Relay::http()` without a request context. |
| 3     | RELAY    | Default classification for internal/system-driven relays.        |

## Failure Reason Enum (`Enums\RelayFailure`)

| Code | Label                 | Description                                               |
|------|-----------------------|-----------------------------------------------------------|
| 100  | EXCEPTION             | Uncaught exception.                                       |
| 101  | PAYLOAD_TOO_LARGE     | Payload exceeds 64KB.                                     |
| 102  | NO_ROUTE_MATCH        | Legacy code (reserved).                                   |
| 103  | CANCELLED             | Manually cancelled.                                       |
| 104  | ROUTE_TIMEOUT         | Processing timeout.                                       |
| 105  | INVALID_PAYLOAD       | JSON decode failure.                                      |
| 108  | INVALID_GUARD_HEADERS | Guard rejected the request before processing because headers failed validation. |
| 109  | INVALID_GUARD_PAYLOAD | Guard rejected payload contents before processing.        |
| 201  | HTTP_ERROR            | Non‑2xx response.                                         |
| 205  | CONNECTION_ERROR      | Network/SSL/DNS failure.                                  |
| 206  | CONNECTION_TIMEOUT    | HTTP timeout.                                             |

## Inbound Guard Classes

Inbound guards are authored as plain PHP classes and registered inline via `guard(StripeWebhookGuard::class)`. No configuration files are required. Guards can **implement** `Atlas\Relay\Contracts\InboundRequestGuardInterface` or extend `Atlas\Relay\Guards\BaseInboundRequestGuard`.

- Guards receive an `InboundRequestGuardContext` that already contains the `Request`, normalized headers, decoded payload, and (when configured) the persisted `Relay` model. Consumers never need to gather these manually.
- `$context->requireHeader('X-Key', env('WEBHOOK_KEY'))` checks for required headers and compares them against secrets without writing custom exception logic.
- `$context->validateHeaders([...])` and `$context->validatePayload([...])` run Laravel's Validator (including dot-notation rules such as `event.order.id`) against headers/payload arrays. Validation exceptions are converted to `InvalidWebhookHeadersException` or `InvalidWebhookPayloadException` automatically.
- Call `$context->failHeaders([...])` or `$context->failPayload([...])` when you want to short-circuit with your own error messages.
- `captureFailures()` controls whether a failing guard should persist the webhook attempt. The default is `true`, meaning the webhook is recorded as failed (using `RelayFailure::INVALID_GUARD_HEADERS` or `INVALID_GUARD_PAYLOAD`). Returning `false` skips capture entirely for blocked attempts.

### Example guard class
```php
use Atlas\Relay\Guards\BaseInboundRequestGuard;
use Atlas\Relay\Support\InboundRequestGuardContext;

class StripeWebhookGuard extends BaseInboundRequestGuard
{
    public function validate(InboundRequestGuardContext $context): void
    {
        // Require a header and check for a specific secret when needed
        $context->requireHeader('Stripe-Signature', env('STRIPE_WEBHOOK_SIGNATURE'));

        // Require a header to simply exist (no comparison)
        $context->requireHeader('X-Relay-Request');

        $context->validatePayload([
            'id' => ['required', 'string'],
            'type' => ['required', 'string'],
            'event.order.id' => ['required', 'string'], // dot notation uses Laravel's Validator under the hood
        ]);
    }

    public function captureFailures(): bool
    {
        return true; // capture failed attempts as relays; return false to reject without storing
    }
}
```

### Guard exception handling
```php
use Atlas\Relay\Exceptions\InvalidWebhookHeadersException;
use Atlas\Relay\Exceptions\InvalidWebhookPayloadException;
use Illuminate\Http\Request;

public function __invoke(Request $request)
{
    try {
        Relay::request($request)
            ->provider('stripe')
            ->guard(\App\Guards\StripeWebhookGuard::class)
            ->event(fn ($payload) => $this->handleEvent($payload));

        return response()->json(['message' => 'ok']);
    } catch (InvalidWebhookHeadersException $exception) {
        return response()->json(['message' => 'Forbidden'], 403);
    } catch (InvalidWebhookPayloadException $exception) {
        return response()->json(['message' => $exception->getMessage()], 422);
    }
}
```

## Capture Rules

- Payloads are truncated when `atlas-relay.payload_max_bytes` is exceeded and the relay is marked `PAYLOAD_TOO_LARGE`.
- Sensitive headers are masked according to `atlas-relay.sensitive_headers`.
- Destination URLs longer than 255 characters are rejected with `InvalidDestinationUrlException`.
- When guards opt-in via `captureFailures()`, relays are stored even when validation fails, ensuring auditability.
