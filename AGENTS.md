# Agents

This guide defines the conventions and best practices for contributors working on this **Laravel package repository**. These rules ensure consistency, clarity, and compatibility for all consumers installing this package via Composer.

---

## Purpose

This repository provides **standalone Laravel packages** designed for installation in other Laravel applications. There is **no full Laravel app** in this repo — all logic must remain **framework-integrated but package-isolated**.

Every package must also follow any **Product Requirement Documents (PRDs)** available in the project. PRDs are the **source of truth** for business rules, naming, and behavior. Code must always align with the PRD’s intent and structure.

---

## Core Principles

1. Follow **PSR-12** and **Laravel Pint** formatting.
2. Use **strict types** and modern **PHP 8.2+** syntax.
3. All code must be **stateless**, **framework-aware**, and **application-agnostic**.
4. Keep everything **self-contained**: no hard dependencies on a consuming app.
5. Always reference **PRDs** for functional requirements and naming accuracy.
6. Write clear, testable, and deterministic code.

---

## Structure

Each package should follow this layout:

```
package-name/
├── composer.json
├── src/
│   ├── Providers/
│   │   └── PackageServiceProvider.php
│   ├── Services/
│   ├── Models/ (if applicable)
│   ├── Contracts/
│   ├── Exceptions/
│   ├── Support/
│   └── Singletons/ (optional)
├── config/ (optional)
├── database/ (optional, migrations/factories)
├── tests/
└── README.md
```

---

## Naming & Conventions

### Class Naming

* **Service Providers:** PascalCase + `ServiceProvider` suffix.
* **Services:** PascalCase + `Service` suffix.
* **Singletons:** PascalCase + `Singleton` suffix (for reusable, shared instances).
* **Contracts:** Interface files end with `Interface`.
* **Models:** Singular, PascalCase (if present).
* **Enums:** PascalCase with clear scope.
* **Exceptions:** PascalCase + `Exception` suffix.

### File & Namespace Structure

* All PHP classes must use the package namespace root (e.g. `Vendor\\PackageName\\...`).
* Group by domain when applicable (`Services/Users/UserService.php`).
* Avoid mixing unrelated logic within a single directory.

### Variables & Methods

* Use `camelCase` for variables and methods.
* Prefix booleans with `is`, `has`, or `can`.
* Keep methods short, descriptive, and predictable.
* Avoid ambiguous names (`handleData()` → `parseWebhookPayload()`).
* Ensure method and service names match **PRD-defined terminology** when applicable.

---

## Service Provider Rules

* Must handle **registration**, **publishing**, and **booting** cleanly.
* Register bindings, configs, routes, and migrations **only if required**.
* Use **package auto-discovery**.
* Keep provider logic minimal and avoid business logic.

---

## Code Practices

1. **Business Logic** — belongs in `Services/` or dedicated singleton classes, not controllers or providers.
2. **Configuration** — define publishable config files in `config/`, use sensible defaults.
3. **Testing** — use PHPUnit or Pest; cover both happy and failure paths.
4. **Type Safety** — declare all parameter and return types.
5. **Error Handling** — use custom exceptions for expected failures.
6. **Dependencies** — keep minimal; prefer Laravel contracts over concrete bindings.
7. **PRD Alignment** — always verify that logic, method names, and service behavior align with any provided PRDs before implementation.

---

## Documentation

Each package must include:

* `README.md` — Installation, Configuration, Usage, and Examples.
* `CHANGELOG.md` — Noting versioned updates.
* `LICENSE` — Open-source license file.

---

## Pre-Commit Checklist

Before committing any change:

1. Run Pint for formatting: `./vendor/bin/pint`
2. Run tests: `composer test`
3. Verify autoload & discovery: `composer dump-autoload`
4. Confirm PRD alignment for naming and functionality.
5. Ensure no temporary debugging or unused imports remain.

---

## Enforcement

Any contribution that violates these standards or PRD requirements will be rejected or revised before merge.
Every agent is expected to follow this guide and maintain alignment between implementation and the project’s PRDs.
