<?php

declare(strict_types=1);

namespace Atlas\Relay\Tests\Feature;

use Atlas\Relay\Contracts\RelayManagerInterface;
use Atlas\Relay\Enums\RelayFailure;
use Atlas\Relay\Enums\RelayStatus;
use Atlas\Relay\Facades\Relay;
use Atlas\Relay\Services\RelayLifecycleService;
use Atlas\Relay\Tests\TestCase;
use Carbon\Carbon;

/**
 * Ensures the lifecycle service can cancel relays and replay them back into the queue while clearing failure state.
 *
 * Defined by PRD: Atlas Relay — Lifecycle Flow Summary and Notes on retries and replays.
 */
class RelayLifecycleServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_cancel_and_replay_flow(): void
    {
        $relay = Relay::payload(['foo' => 'bar'])->capture();

        /** @var RelayManagerInterface $manager */
        $manager = app(RelayManagerInterface::class);

        $cancelled = $manager->cancel($relay);
        $this->assertSame(RelayStatus::CANCELLED, $cancelled->status);
        $this->assertNotNull($cancelled->cancelled_at);

        $replayed = $manager->replay($cancelled);
        $this->assertSame(RelayStatus::QUEUED, $replayed->status);
        $this->assertNull($replayed->failure_reason);
        $this->assertNull($replayed->cancelled_at);
        $this->assertSame(0, $replayed->attempt_count);
    }

    public function test_processing_and_completion_timestamps_are_managed(): void
    {
        Carbon::setTestNow('2025-04-01 08:00:00');

        $relay = Relay::payload(['foo' => 'bar'])->capture();

        /** @var RelayLifecycleService $lifecycle */
        $lifecycle = app(RelayLifecycleService::class);

        $lifecycle->startAttempt($relay);
        $relay->refresh();

        $this->assertSame(RelayStatus::PROCESSING, $relay->status);
        $this->assertTrue($relay->processing_at?->equalTo(Carbon::now()));
        $this->assertNull($relay->completed_at);

        $relay->forceFill(['next_retry_at' => Carbon::now()->addMinutes(5)])->save();

        Carbon::setTestNow('2025-04-01 08:05:00');
        $lifecycle->markFailed($relay, RelayFailure::ROUTE_TIMEOUT);
        $relay->refresh();

        $this->assertSame(RelayStatus::FAILED, $relay->status);
        $this->assertTrue($relay->completed_at?->equalTo(Carbon::now()));
        $this->assertTrue($relay->failed_at?->equalTo(Carbon::now()));
        $this->assertNull($relay->next_retry_at);

        $relay->forceFill(['status' => RelayStatus::QUEUED])->save();

        Carbon::setTestNow('2025-04-01 08:06:00');
        $lifecycle->startAttempt($relay);
        $relay->refresh();

        $this->assertNull($relay->failed_at);
        $this->assertNull($relay->completed_at);

        Carbon::setTestNow('2025-04-01 08:07:00');
        $lifecycle->markCompleted($relay);
        $relay->refresh();

        $this->assertSame(RelayStatus::COMPLETED, $relay->status);
        $this->assertTrue($relay->completed_at?->equalTo(Carbon::now()));
        $this->assertNull($relay->next_retry_at);
    }
}
