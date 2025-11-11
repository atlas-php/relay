<?php

declare(strict_types=1);

namespace AtlasRelay;

use AtlasRelay\Support\RelayContext;
use Illuminate\Http\Request;

/**
 * Fluent builder that mirrors the relay lifecycle defined in the PRD.
 */
class RelayBuilder
{
    private ?Request $request;

    private mixed $payload;

    public function __construct(?Request $request = null, mixed $payload = null)
    {
        $this->request = $request;
        $this->payload = $payload;
    }

    public function request(Request $request): self
    {
        $this->request = $request;

        return $this;
    }

    public function payload(mixed $payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * Exposes the current relay snapshot so tests can assert fluent state until
     * persistence and lifecycle orchestration layers are implemented.
     */
    public function context(): RelayContext
    {
        return new RelayContext($this->request, $this->payload);
    }

    public function event(callable $callback): self
    {
        return $this;
    }

    public function dispatchEvent(callable $callback): self
    {
        return $this;
    }

    public function dispatchAutoRoute(): self
    {
        return $this;
    }

    public function autoRouteImmediately(): self
    {
        return $this;
    }

    public function http(): self
    {
        return $this;
    }

    public function dispatch(mixed $job): self
    {
        return $this;
    }

    public function dispatchSync(mixed $job): self
    {
        return $this;
    }

    public function dispatchChain(array $jobs): self
    {
        return $this;
    }
}
