<?php

declare(strict_types=1);

namespace Atlas\Relay\Console\Commands;

use Atlas\Relay\Enums\RelayFailure;
use Atlas\Relay\Enums\RelayStatus;
use Atlas\Relay\Models\Relay;
use Atlas\Relay\Services\RelayLifecycleService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Class EnforceRelayTimeoutsCommand
 *
 * Marks relays as failed when processing exceeds configured time limits,
 * fulfilling the timeout enforcement automation in PRD — Atlas Relay
 * (Automation Jobs).
 */
class EnforceRelayTimeoutsCommand extends Command
{
    protected $signature = 'atlas-relay:enforce-timeouts {--chunk=100 : Number of relays per chunk}';

    protected $description = 'Marks relays that exceeded their timeout configuration as failed.';

    public function handle(RelayLifecycleService $lifecycle): int
    {
        $chunkSize = (int) $this->option('chunk');
        $bufferSeconds = (int) config('atlas-relay.automation.timeout_buffer_seconds', 0);
        $timeoutSeconds = (int) config('atlas-relay.automation.processing_timeout_seconds', 0);

        if ($timeoutSeconds <= 0) {
            $this->info(
                'Processing timeout enforcement disabled; set atlas-relay.automation.processing_timeout_seconds to enable.'
            );

            return self::SUCCESS;
        }

        $count = 0;

        Relay::query()
            ->where('status', RelayStatus::PROCESSING->value)
            ->whereNotNull('processing_at')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($relays) use (&$count, $lifecycle, $bufferSeconds, $timeoutSeconds): void {
                foreach ($relays as $relay) {
                    if (! $relay->processing_at instanceof \DateTimeInterface) {
                        continue;
                    }

                    $deadline = Carbon::parse($relay->processing_at)
                        ->addSeconds($timeoutSeconds + $bufferSeconds);

                    if ($deadline->isPast()) {
                        $lifecycle->markFailed($relay, RelayFailure::ROUTE_TIMEOUT);
                        $count++;
                    }
                }
            });

        $this->info("Timed out {$count} relays.");

        return self::SUCCESS;
    }
}
