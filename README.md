# Atlas Relay

> A unified Laravel relay system built for **reliability**, **observability**, and **control** — capture any payload, process it flexibly, and relay it anywhere with full lifecycle visibility.

---

## 🌍 Overview

**Atlas Relay** is a Laravel package that provides a **unified system for capturing, processing, and relaying payloads** between internal and external destinations. It replaces fragmented inbound/outbound logic with a single continuous **relay lifecycle** that ensures every payload is tracked, processed, and delivered with complete observability.

### Why Atlas Relay?

In modern systems, data moves constantly between services. Payloads often get lost, retried inconsistently, or lack visibility into their journey. Atlas Relay solves these issues by creating a persistent, traceable record for every payload and automating its entire lifecycle.

Atlas Relay ensures:

* Every payload is **stored first, processed second**.
* All relays are **auditable, traceable, and retryable**.
* Event-driven and HTTP-based deliveries share the same **fluent, chainable API**.

---

## ⚡ Core Concepts

**Relay Flow:**

`Request → Payload Capture → Event / Dispatch / AutoRoute → Delivery → Complete → Archive`

### Key Principles

* **Reliability:** Every payload is persisted before processing.
* **Visibility:** All relay states, payloads, and results are logged.
* **Flexibility:** Use events, dispatches, or routing for any workflow.
* **Auditability:** End-to-end tracking across every relay operation.

---

## ✨ Feature Highlights

* Unified API for all relay types (event, dispatch, autoroute, direct)
* Automatic lifecycle tracking with status management
* Retry, delay, and timeout handling for routed deliveries
* Caching for fast route lookup with invalidation
* Full audit trail and logging of payloads and responses
* Automatic archiving and purging for data lifecycle management

---

## 🧩 Fluent API Examples

Atlas Relay exposes a consistent, chainable API for defining how payloads are captured and delivered.

### Example A — Capture + Event Execution

```php
Relay::request($request)
    ->payload($payload)
    ->event(fn() => $this->handleEvent($payload));
```

Stores a payload, executes a synchronous event handler, and marks the relay as complete when finished.

### Example B — Capture + Dispatch Event

```php
Relay::request($request)
    ->payload($payload)
    ->dispatchEvent(fn() => $this->handleEvent($payload));
```

Stores payload and dispatches an asynchronous event. The relay completes when the dispatched job succeeds.

### Example C — Auto-Route Dispatch

```php
Relay::request($request)
    ->payload($payload)
    ->dispatchAutoRoute();
```

Captures a payload and routes it automatically using configured domains and routes.

### Example D — Auto-Route Immediate Delivery

```php
Relay::request($request)
    ->payload($payload)
    ->autoRouteImmediately();
```

Performs synchronous routing and returns outbound response inline.

### Example E — Direct Outbound Relay

```php
Relay::payload($payload)
    ->http()
    ->post('https://api.example.com/webhooks');
```

Performs direct outbound delivery without route lookup.

---

## 🧠 Relay Lifecycle

Every relay is represented by a record in the database (`atlas_relays`) and passes through these states:

| Status         | Description                                 |
| -------------- | ------------------------------------------- |
| **Queued**     | Payload recorded and awaiting relay action. |
| **Processing** | Relay executing or event dispatched.        |
| **Failed**     | Error occurred, `failure_reason` recorded.  |
| **Completed**  | Relay finished successfully.                |
| **Cancelled**  | Relay manually stopped before completion.   |

### Automatic Transitions

* Relays update automatically based on execution outcome.
* Failures capture exceptions or HTTP errors with detailed reasons.
* Completed relays store response status and payload.

---

## 🔁 Retry, Delay & Timeout Handling

Retry logic applies only to **AutoRoute** deliveries.

* **Retry**: Failed deliveries reattempt after `retry_at` interval.
* **Delay**: Postpones execution by configured seconds.
* **Timeout**: Marks relays as failed after exceeding duration.

Event-based and direct deliveries (`event()`, `dispatchEvent()`, `http()->post()`) complete immediately and are not retried.

---

## 🧭 Routing Behavior

* `dispatchAutoRoute()` and `autoRouteImmediately()` use domain + route registry.
* Supports strict and dynamic path matching (`/event/{CUSTOMER_ID}`).
* Cached for performance with **20-minute invalidation** after domain or route updates.

---

## 🔍 Observability & Logging

Atlas Relay logs all relay activity, including:

* Request source and headers
* Payload and response data
* Status transitions and timing metrics
* Retry history for failed deliveries

Each relay record is auditable from creation to completion, ensuring full transparency into what was processed, when, and how.

---

## 🗄️ Archiving & Retention

Relays automatically move to archive tables (`atlas_relay_archives`) based on retention settings.

| Env Variable               | Default | Description                      |
| -------------------------- | ------- | -------------------------------- |
| `ATLAS_RELAY_ARCHIVE_DAYS` | 30      | Days before relays are archived. |
| `ATLAS_RELAY_PURGE_DAYS`   | 180     | Days before archives are purged. |

* Archiving runs nightly at **10 PM EST**.
* Purging runs nightly at **11 PM EST**.

---

## 🧮 Automation Jobs

| Process              | Frequency         | Description                               |
| -------------------- | ----------------- | ----------------------------------------- |
| Retry overdue        | Every minute      | Re-attempts failed AutoRoute deliveries.  |
| Requeue stuck relays | Every 10 minutes  | Recovers relays stuck in `Processing`.    |
| Timeout enforcement  | Hourly            | Marks timed-out relays as failed.         |
| Archiving            | Daily (10 PM EST) | Moves aged relays to archive.             |
| Purging              | Daily (11 PM EST) | Deletes archived relays beyond retention. |

---

## ⚙️ Configuration

| Variable                   | Description                               |
| -------------------------- | ----------------------------------------- |
| `QUEUE_CONNECTION`         | Queue backend for asynchronous execution. |
| `ATLAS_RELAY_ARCHIVE_DAYS` | Days before relays are archived.          |
| `ATLAS_RELAY_PURGE_DAYS`   | Days before archived relays are deleted.  |

---

## 🚦 Error Mapping

| Condition             | Result                  |
| --------------------- | ----------------------- |
| HTTP not 2xx          | `HTTP_ERROR`            |
| Too many redirects    | `TOO_MANY_REDIRECTS`    |
| Redirect host changed | `REDIRECT_HOST_CHANGED` |
| Timeout reached       | `CONNECTION_TIMEOUT`    |
| Payload exceeds 64KB  | `PAYLOAD_TOO_LARGE`     |

---

## 🧪 Example Usage

### Handling an HTTP Request

```php
public function handle(Request $request)
{
    Relay::request($request)
        ->payload($request->all())
        ->dispatchAutoRoute();
}
```

### Dispatching an Event Relay

```php
Relay::payload(['id' => 42])
    ->dispatchEvent(fn() => ExampleJob::dispatch());
```

### Direct Outbound Delivery

```php
Relay::payload(['ping' => 'ok'])
    ->http()
    ->post('https://hooks.example.com/endpoint');
```

---

## 🤝 Contributing

Atlas Relay is designed for extensibility. Contributions are welcome to expand features, improve logging, or enhance routing logic.

### Guidelines

* Follow Laravel and PSR standards.
* Add tests for new behaviors.
* Update docs for all feature changes.

### Local Setup

```bash
composer install
php artisan migrate
```

Run tests:

```bash
php artisan test
```

---

## 📘 License

Atlas Relay is open-source software licensed under the [MIT license](./LICENSE).
