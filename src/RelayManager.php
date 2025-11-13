<?php

declare(strict_types=1);

namespace Atlas\Relay;

use Atlas\Relay\Contracts\RelayManagerInterface;
use Atlas\Relay\Models\Relay;
use Atlas\Relay\Routing\Router;
use Atlas\Relay\Services\RelayCaptureService;
use Atlas\Relay\Services\RelayDeliveryService;
use Atlas\Relay\Services\RelayLifecycleService;
use Atlas\Relay\Support\RelayHttpClient;
use Illuminate\Http\Request;

/**
 * Default RelayManager that hands back configured builders per the PRD.
 */
class RelayManager implements RelayManagerInterface
{
    public function __construct(
        private readonly RelayCaptureService $captureService,
        private readonly RelayLifecycleService $lifecycleService,
        private readonly RelayDeliveryService $deliveryService,
        private readonly Router $router
    ) {}

    public function request(Request $request): RelayBuilder
    {
        return new RelayBuilder($this->captureService, $this->router, $this->deliveryService, $request);
    }

    public function payload(mixed $payload): RelayBuilder
    {
        return (new RelayBuilder($this->captureService, $this->router, $this->deliveryService))->payload($payload);
    }

    public function http(): RelayHttpClient
    {
        return (new RelayBuilder($this->captureService, $this->router, $this->deliveryService))->http();
    }

    public function cancel(Relay $relay): Relay
    {
        return $this->lifecycleService->cancel($relay);
    }

    public function replay(Relay $relay): Relay
    {
        return $this->lifecycleService->replay($relay);
    }
}
