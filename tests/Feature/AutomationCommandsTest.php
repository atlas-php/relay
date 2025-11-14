<?php

declare(strict_types=1);

namespace Atlas\Relay\Tests\Feature;

use Atlas\Relay\Console\Commands\ArchiveRelaysCommand;
use Atlas\Relay\Enums\RelayStatus;
use Atlas\Relay\Enums\RelayType;
use Atlas\Relay\Models\Relay;
use Atlas\Relay\Models\RelayArchive;
use Atlas\Relay\Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Validates the archiving console commands shipped with Atlas Relay.
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

    public function test_archive_and_purge_commands(): void
    {
        $relay = Relay::query()->create([
            'source_ip' => '127.0.0.1',
            'headers' => [],
            'payload' => [],
            'status' => RelayStatus::COMPLETED,
            'type' => RelayType::INBOUND,
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
                'type' => RelayType::INBOUND,
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
