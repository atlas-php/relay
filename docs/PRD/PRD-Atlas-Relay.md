
# PRD — Atlas Relay

## Overview
Atlas Relay provides a unified, reliable system to capture inbound webhooks, drive downstream work, and record every lifecycle transition. Every relay is fully tracked end‑to‑end with complete visibility and auditability.

---

## Goals
- Single fluent API for the full webhook lifecycle.
- Guaranteed storage and traceability of all payloads before any business logic runs.
- Support event, dispatch, and direct HTTP delivery modes without additional routing layers.
- Unified synchronous/asynchronous behavior.
- Complete lifecycle observability.

---

## Relay Flow
Request → Capture → Event/Dispatch/HTTP → Complete → Archive

---

## Core API Patterns

### Capture + Event
```php
Relay::request($req)->event(fn($payload) => ...);
```

### Capture + Dispatch Job
```php
Relay::request($req)->dispatch(new ExampleJob($payload));
```

### Direct HTTP
```php
Relay::http()->post('https://example.com', ['payload' => true]);
```

---

## Functional Summary
- **Relay::request()** captures inbound HTTP, normalizes headers, stores payload, and exposes that payload directly on the builder for downstream delivery or job execution.
- **payload()** sets stored payload (optional when using `Relay::http()` because payload is captured from the request data).
- **event()** runs internal logic synchronously.
- **http()** sends direct outbound HTTP (Laravel `Http` wrapper).
- **dispatch()** uses Laravel’s native dispatch with lifecycle tracking.

---

## Relay Tracking Model
Every relay is represented by a unified record containing the entire transaction.  
Full schema lives in **Payload Capture PRD** (`atlas_relays` includes all request, response, and lifecycle fields). Retry/delay/timeout enforcement is handled directly from the relay record combined with global automation config.

---

## Database Requirements
- Configurable database connection: `atlas-relay.database.connection` (`ATLAS_RELAY_DATABASE_CONNECTION`).
- Defaults to app’s primary connection.
- All models/migrations must respect this setting.

---

## Status Lifecycle
**Queued → Processing → Completed | Failed | Cancelled**

Rules:
- All relay types use the same lifecycle.
- Exceptions or failed outbound responses set status to `Failed` and populate `failure_reason`.
- `event()` → completes when handler succeeds.
- Direct HTTP completes/fails based on response and lifecycle service results.

---

## Retry, Delay & Timeout Logic
Automation relies entirely on the relay record plus package configuration.

Source of configuration:
- `next_retry_at` determines when `atlas-relay:retry-overdue` will requeue a relay.
- Applications decide if/when to populate `next_retry_at` (e.g., after delivery failures).
- `atlas-relay.automation.processing_timeout_seconds` + `timeout_buffer_seconds` control when `atlas-relay:enforce-timeouts` marks a relay as failed while it sits in `processing`.

Rules:
- Retries: when `next_retry_at` is in the past, `retry-overdue` resets status to `queued`, clears `next_retry_at`, and lifecycle services increment attempts on the next `startAttempt`.
- Delays: simply set `next_retry_at` in the future; the retry job will pick it up when due.
- Timeouts: once `processing_at` is older than the configured processing timeout (plus buffer), the timeout job marks the relay failed with `RelayFailure::ROUTE_TIMEOUT`.

---

## Observability
All lifecycle data is stored inline on `atlas_relays`:  
status, failure_reason, attempts, durations, `response_http_status`, `response_payload`, and scheduling timestamps. Automation relies on those columns plus the automation config section for enforcement thresholds.

---

## Archiving & Retention
Historical records migrate to `atlas_relay_archives` (schema mirrors `atlas_relays`).

| Var                        | Default | Meaning            |
|----------------------------|---------|--------------------|
| `ATLAS_RELAY_ARCHIVE_DAYS` | 30      | Age before archive |
| `ATLAS_RELAY_PURGE_DAYS`   | 180     | Age before purge   |

Archiving: 10 PM EST  
Purging: 11 PM EST

---

## Automation Jobs
| Job                  | Frequency    | Purpose                      |
|----------------------|--------------|------------------------------|
| Retry overdue        | Every min    | Retry relays past `next_retry_at` |
| Requeue stuck relays | Every 10 min | Requeue long‑running relays  |
| Timeout enforcement  | Hourly       | Mark timed‑out relays failed |
| Archiving            | Daily        | Move old relays              |
| Purging              | Daily        | Delete old archives          |

---

## Notes
- HTTP & Dispatch use Laravel‑native APIs; Atlas Relay only intercepts for lifecycle recording.
- All payloads stored regardless of delivery result.
- Malformed JSON stored as‑is with `INVALID_PAYLOAD`.
- All operations must be idempotent.
