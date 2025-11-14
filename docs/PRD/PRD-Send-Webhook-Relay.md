
# PRD — Send Webhook Relay

## Overview
Send Webhook Relay describes how Atlas Relay delivers captured payloads to downstream systems. It covers synchronous event handlers, queued jobs, and outbound HTTP calls while ensuring lifecycle state, responses, and failures are written back to the originating relay. No retry framework exists—each relay represents a single delivery attempt that either completes, fails, or is cancelled.

---

## Goals
- Provide consistent outbound execution for HTTP calls, synchronous events, and queued jobs.
- Record every response payload, failure reason, and timestamp for auditing.
- Surface delivery context to jobs via middleware and helpers without changing Laravel ergonomics.

---

## Outbound Flow
Capture → Choose Delivery Path (Event / Dispatch / HTTP) → Execute → Record Response → Complete/Fail → Archive

---

## Delivery Paths

| Path      | Description                                                                 |
|-----------|-----------------------------------------------------------------------------|
| **HTTP**  | Sends the relay payload to an external URL using Laravel’s HTTP client.     |
| **Event** | Executes a synchronous callback and records success or thrown exceptions.   |
| **Dispatch** | Dispatches a queued job with middleware that tracks relay lifecycle state. |

Every path operates on a single relay record. Fan-out (multiple deliveries per relay) remains an application-level orchestration concern.

---

## Laravel Wrapper Design
Send Webhook Relay wraps Laravel’s native primitives:

- **HTTP** — Uses `Http::` under the hood. The wrapper writes headers/method/url onto the relay and captures status/payload (truncated to the configured byte limit) while leaving HTTPS/redirect settings entirely up to the consuming application. Exceptions are mapped to `RelayFailure` codes.
- **Dispatch** — Returns Laravel’s `PendingDispatch` and supports all queue customizations. Middleware (`RelayJobMiddleware`) marks `processing`, updates completion timestamps, and captures exceptions or helper-triggered failures.
- **Event** — Executes the provided callback synchronously. Success marks the relay `COMPLETED`; exceptions mark it `FAILED` with `RelayFailure::EXCEPTION`.

---

## HTTP Execution Rules
- Method defaults to the invoked verb (`get`, `post`, etc.); payload argument becomes the request body.
- Headers provided via `withHeaders()` are merged into the relay record.
- Consumers control HTTPS enforcement, redirect policies, retries, and all other HTTP client options directly through Laravel’s `Http` facade before invoking verbs.
- Responses record:
  - `response_http_status`
  - `response_payload` (truncated to `atlas-relay.payload_max_bytes`)

## Dispatch Execution Rules
- Middleware calls `RelayLifecycleService::startAttempt()` to mark `PROCESSING`.
- Jobs can manually fail the relay by throwing `RelayJobFailedException` or using `RelayJobHelper::fail()`.
- On completion the middleware clears any failure reason and timestamps `completed_at`.

## Event Execution Rules
- The event callback runs immediately inside the request lifecycle.
- Any thrown exception marks the relay failed with `RelayFailure::EXCEPTION` and records the exception summary as the `response_payload`.

---

## Lifecycle Transitions

| State                  | Trigger                                |
|------------------------|----------------------------------------|
| Queued → Processing    | HTTP dispatch, event execution, or job middleware starts |
| Processing → Completed | Callback/job/HTTP succeeded            |
| Processing → Failed    | Exception, HTTP failure, timeout guard |
| Processing → Cancelled | Manual `cancel()` invocation           |

---

## Failure Reason Enum (`Enums\RelayFailure`)

| Code | Label                 | Description                                |
|------|-----------------------|--------------------------------------------|
| 100  | EXCEPTION             | Uncaught exception                         |
| 101  | PAYLOAD_TOO_LARGE     | Payload >64KB                              |
| 102  | NO_ROUTE_MATCH        | Legacy code (reserved).                    |
| 103  | CANCELLED             | Manually cancelled                         |
| 104  | ROUTE_TIMEOUT         | Processing timeout (consumer automation)   |
| 201  | HTTP_ERROR            | Non‑2xx response                           |
| 205  | CONNECTION_ERROR      | Network/SSL/DNS failure                    |
| 206  | CONNECTION_TIMEOUT    | HTTP timeout                               |

---

## Automation

Atlas Relay no longer ships a timeout enforcement command. Consumers should implement their own schedulers (horizon metrics, queue monitors, etc.) if they need to fail relays stuck in `PROCESSING`—`RelayLifecycleService::markFailed(..., RelayFailure::ROUTE_TIMEOUT)` remains available for that workflow. Archiving and purging continue to run as described in the Archiving & Logging PRD.

---

## Observability
- `RelayType::OUTBOUND` is applied automatically when an HTTP relay is created without an inbound request.
- Response bodies and statuses are stored on the relay for auditability.
- Jobs can access the active relay via `RelayJobContext::current()` or `RelayJobHelper`.
- All lifecycle timestamps (`processing_at`, `completed_at`) are updated by `RelayLifecycleService`.

---

## Notes
- There is no automatic retry system; re-delivery must be initiated by the consuming application (e.g., by creating a new relay).
- HTTPS/redirect policies are entirely consumer-defined via Laravel’s HTTP client options; Atlas only records the outcome.
- Custom jobs do not need to inherit special base classes—middleware handles lifecycle tracking transparently.
