<?php

declare(strict_types=1);

namespace AtlasRelay\Jobs;

use AtlasRelay\Services\RelayDeliveryService;
use Closure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queueable job that executes relay event callbacks asynchronously while preserving lifecycle semantics.
 *
 * Defined by PRD: Outbound Delivery — Event Mode and Failure Reason Enum.
 */
class DispatchRelayEventJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly Closure $callback
    ) {}

    public function handle(RelayDeliveryService $deliveryService): void
    {
        $deliveryService->runQueuedEventCallback($this->callback);
    }
}
