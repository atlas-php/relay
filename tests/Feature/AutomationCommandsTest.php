<?php

declare(strict_types=1);

namespace Atlas\Relay\Tests\Feature;

use Atlas\Relay\Console\Commands\ArchiveRelaysCommand;
use Atlas\Relay\Enums\RelayStatus;
use Atlas\Relay\Models\Relay;
use Atlas\Relay\Models\RelayArchive;
use Atlas\Relay\Services\RelayLifecycleService;
use Atlas\Relay\Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Validates automation console commands for retrying, requeuing, timing out, archiving, and purging relays.
 *
 * Defined by PRD: Atlas Relay — Automation Jobs; Archiving & Logging — Archiving Process and Purge Process.
 */
class AutomationCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2025-01-15 00:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_retry_overdue_command_requeues_relays(): void
    {
        $relay = Relay::query()->create([
            'source_ip' => '127.0.0.1',
            'headers' => [],
            'payload' => [],
            'status' => RelayStatus::FAILED,
            'mode' => 'event',
            'next_retry_at' => Carbon::now()->subMinute(),
        ]);

        $this->runPendingCommand('atlas-relay:retry-overdue')->assertExitCode(0);

        $relay->refresh();
        $this->assertSame(RelayStatus::QUEUED, $relay->status);
        $this->assertNull($relay->next_retry_at);
        $this->assertNull($relay->failure_reason);
        $this->assertNull($relay->completed_at);
    }

    public function test_retry_overdue_resets_response_state_and_increments_attempts_on_next_run(): void
    {
        $relay = Relay::query()->create([
            'source_ip' => '127.0.0.1',
            'headers' => [],
            'payload' => [],
            'status' => RelayStatus::FAILED,
            'mode' => 'http',
            'attempts' => 2,
            'response_http_status' => 500,
            'response_payload' => 'error payload',
            'failure_reason' => 201,
            'next_retry_at' => Carbon::now()->subMinutes(5),
            'completed_at' => Carbon::now()->subMinutes(5),
        ]);

        $this->runPendingCommand('atlas-relay:retry-overdue')->assertExitCode(0);

        $relay->refresh();
        $this->assertSame(RelayStatus::QUEUED, $relay->status);
        $this->assertNull($relay->response_http_status);
        $this->assertNull($relay->response_payload);
        $this->assertNull($relay->failure_reason);
        $this->assertNull($relay->completed_at);
        $this->assertNull($relay->next_retry_at);
        $this->assertSame(2, $relay->attempts);

        $lifecycle = app(RelayLifecycleService::class);
        $lifecycle->startAttempt($relay);

        $relay->refresh();
        $this->assertSame(RelayStatus::PROCESSING, $relay->status);
        $this->assertSame(3, $relay->attempts);
    }

    public function test_requeue_stuck_command_moves_processing_relays_back_to_queue(): void
    {
        $relay = Relay::query()->create([
            'source_ip' => '127.0.0.1',
            'headers' => [],
            'payload' => [],
            'status' => RelayStatus::PROCESSING,
            'mode' => 'event',
            'processing_at' => Carbon::now()->subMinutes(30),
        ]);

        $this->runPendingCommand('atlas-relay:requeue-stuck')->assertExitCode(0);

        $relay->refresh();
        $this->assertSame(RelayStatus::QUEUED, $relay->status);
        $this->assertNull($relay->processing_at);
        $this->assertNull($relay->completed_at);
        $this->assertTrue($relay->next_retry_at?->equalTo(Carbon::now()));
    }

    public function test_enforce_timeouts_marks_relays_failed(): void
    {
        $relay = Relay::query()->create([
            'source_ip' => '127.0.0.1',
            'headers' => [],
            'payload' => [],
            'status' => RelayStatus::PROCESSING,
            'mode' => 'http',
            'processing_at' => Carbon::now()->subMinutes(5),
        ]);

        config()->set('atlas-relay.automation.processing_timeout_seconds', 60);

        $this->runPendingCommand('atlas-relay:enforce-timeouts')->assertExitCode(0);

        $relay->refresh();
        $this->assertSame(RelayStatus::FAILED, $relay->status);
    }

    public function test_archive_and_purge_commands(): void
    {
        $relay = Relay::query()->create([
            'source_ip' => '127.0.0.1',
            'headers' => [],
            'payload' => [],
            'status' => RelayStatus::COMPLETED,
            'mode' => 'http',
            'updated_at' => Carbon::now()->subDays(60),
            'created_at' => Carbon::now()->subDays(61),
        ]);

        $this->runPendingCommand('atlas-relay:archive', ['--chunk' => 10])->assertExitCode(0);

        $this->assertDatabaseMissing($relay->getTable(), ['id' => $relay->id]);
        $this->assertDatabaseHas(
            RelayArchive::query()->getModel()->getTable(),
            ['id' => $relay->id]
        );

        RelayArchive::query()->update(['archived_at' => Carbon::now()->subDays(200)]);

        $this->runPendingCommand('atlas-relay:purge-archives')->assertExitCode(0);

        $this->assertDatabaseMissing(RelayArchive::query()->getModel()->getTable(), ['id' => $relay->id]);
    }

    public function test_archive_command_uses_default_chunk_size_when_option_missing(): void
    {
        foreach (range(1, 3) as $index) {
            Relay::query()->create([
                'source_ip' => '127.0.0.1',
                'headers' => [],
                'payload' => [],
                'status' => RelayStatus::COMPLETED,
                'mode' => 'http',
                'updated_at' => Carbon::now()->subDays(60)->subMinutes($index),
                'created_at' => Carbon::now()->subDays(61)->subMinutes($index),
            ]);
        }

        DB::connection()->enableQueryLog();

        $this->runPendingCommand('atlas-relay:archive')->assertExitCode(0);

        $queries = collect(DB::getQueryLog())->pluck('query');

        $this->assertTrue(
            $queries->contains(
                fn (string $query): bool => str_contains(
                    strtolower($query),
                    sprintf('limit %d', ArchiveRelaysCommand::DEFAULT_CHUNK_SIZE)
                )
            ),
            sprintf(
                'Expected archive command to query relays using default chunk size of %d.',
                ArchiveRelaysCommand::DEFAULT_CHUNK_SIZE
            )
        );
    }
}
