# Atlas Relay Full API Reference

This document provides a complete reference of all **public APIs** exposed by Atlas Relay. It is focused solely on method availability and signatures. Behavioral rules, lifecycle semantics, and schema definitions are documented in the corresponding PRDs.

## Table of Contents
- [Facade Entrypoint](#facade-entrypoint)
- [Manager-Level Public API](#manager-level-public-api)
- [RelayBuilder API](#relaybuilder-api)
- [Delivery API](#delivery-api)
- [Lifecycle & Delivery Services](#lifecycle--delivery-services)
- [Models](#models)
- [Console Commands](#console-commands)
- [Enums & Exceptions](#enums--exceptions)

## Facade Entrypoint
### Atlas\Relay\Facades\Relay
Central access point for creating and managing all relay types.

## Manager-Level Public API
| Method             | Signature                                   | Purpose                                           |
|--------------------|---------------------------------------------|---------------------------------------------------|
| `request()`        | `request(Request $request): RelayBuilder`   | Begin an inbound relay from an HTTP request.      |
| `payload()`        | `payload(mixed $payload): RelayBuilder`     | Begin a relay from raw payload (system/internal). |
| `type()`           | `type(RelayType $type): RelayBuilder`       | Override inferred relay type.                     |
| `provider()`       | `provider(?string $provider): RelayBuilder` | Tag the relay with an integration key.            |
| `setReferenceId()` | `setReferenceId(?string $id): RelayBuilder` | Attach a reference ID.                            |
| `guard()`          | `guard(?string $guardClass): RelayBuilder`  | Apply an inbound guard class.                     |
| `http()`           | `http(): RelayHttpClient`                   | Begin an outbound HTTP relay.                     |
| `cancel()`         | `cancel(Relay $relay): Relay`               | Mark relay as cancelled.                          |

## RelayBuilder API
Returned from `Relay::request()`, `Relay::payload()`, etc.

### Configuration Methods
| Method              | Signature                                                       | Purpose                                     |
|---------------------|-----------------------------------------------------------------|---------------------------------------------|
| `payload()`         | `payload(mixed $payload)`                                       | Override or define relay payload.           |
| `meta()`            | `meta(mixed $meta)`                                             | Store custom metadata.                      |
| `type()`            | `type(RelayType $type)`                                         | Set relay type explicitly.                  |
| `provider()`        | `provider(?string)`                                             | Override provider.                          |
| `setReferenceId()`  | `setReferenceId(?string)`                                       | Override reference ID.                      |
| `guard()`           | `guard(?string)`                                                | Attach inbound guard.                       |
| `status()`          | `status(RelayStatus $status)`                                   | Set initial status (rare).                  |
| `validationError()` | `validationError(string $field, string $message)`               | Store validation error before capture.      |
| `failWith()`        | `failWith(RelayFailure $failure, RelayStatus $status = FAILED)` | Force initial failure state.                |

### Capture & Inspection
| Method      | Signature                 | Purpose                         |
|-------------|---------------------------|---------------------------------|
| `capture()` | `capture(): Relay`        | Persist relay immediately.      |
| `relay()`   | `relay(): ?Relay`         | Get last persisted relay.       |
| `context()` | `context(): RelayContext` | Get immutable capture snapshot. |

## Delivery API
Delivery covers events, jobs, and HTTP execution.

### Event Execution
| Method    | Signature                          | Purpose                                            |
|-----------|------------------------------------|----------------------------------------------------|
| `event()` | `event(callable $callback): mixed` | Perform synchronous execution with lifecycle.      |

### Job Dispatching
| Method            | Signature                                  | Purpose                                                 |
|-------------------|--------------------------------------------|---------------------------------------------------------|
| `dispatch()`      | `dispatch(mixed $job): PendingDispatch`    | Dispatch job with lifecycle handling.                   |
| `dispatchChain()` | `dispatchChain(array $jobs): PendingChain` | Dispatch job chain with lifecycle propagation.          |

### HTTP Execution (Outbound)
Available through `RelayHttpClient`.

Supported verbs:
- `get()`
- `post()`
- `put()`
- `patch()`
- `delete()`

Request configuration (proxied from Laravel):
- `withHeaders()`
- `timeout()`
- `retry()`
- `accept()`
- `asJson()`
- `attach()`
- All other PendingRequest methods

## Lifecycle & Delivery Services
Internal services that remain publicly accessible.

| Service                 | Methods                                                                                                          | Purpose                |
|-------------------------|------------------------------------------------------------------------------------------------------------------|------------------------|
| `RelayDeliveryService`  | `executeEvent()`, `http()`, `dispatch()`, `dispatchChain()`, `runQueuedEventCallback()`                          | Orchestrates delivery. |
| `RelayLifecycleService` | `startAttempt()`, `markCompleted()`, `markFailed()`, `recordResponse()`, `recordExceptionResponse()`, `cancel()` | Lifecycle transitions. |
| `RelayCaptureService`   | `capture()`                                                                                                      | Low-level persistence. |

## Models
| Model                             | Purpose                |
|-----------------------------------|------------------------|
| `Atlas\Relay\Models\Relay`        | Live relay records     |
| `Atlas\Relay\Models\RelayArchive` | Archived relay records |

## Console Commands
| Command                          | Purpose                         |
|----------------------------------|---------------------------------|
| `atlas-relay:archive`            | Archive completed/failed relays |
| `atlas-relay:purge-archives`     | Purge expired archives          |
| `atlas-relay:relay:inspect {id}` | Inspect relay (live/archived)   |
| `atlas-relay:relay:restore {id}` | Restore archive → live          |

## Enums & Exceptions
### Enums
- `RelayType`
- `RelayStatus`
- `RelayFailure`

### Exceptions
- `InvalidWebhookHeadersException`
- `InvalidWebhookPayloadException`
- `RelayHttpException`
- `RelayJobFailedException`

## Also See
- [Atlas Relay](./PRD/Atlas-Relay.md)
- [Receive Webhook Relay](./PRD/Receive-Webhook-Relay.md)
- [Send Webhook Relay](./PRD/Send-Webhook-Relay.md)
- [Archiving & Logging](./PRD/Archiving-and-Logging.md)
- [Example Usage](./PRD/Example-Usage.md)
