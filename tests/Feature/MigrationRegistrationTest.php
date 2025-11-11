<?php

declare(strict_types=1);

namespace AtlasRelay\Tests\Feature;

use AtlasRelay\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

class MigrationRegistrationTest extends TestCase
{
    public function test_package_migrations_are_loadable(): void
    {
        $this->artisan('migrate', ['--database' => 'testbench'])->run();

        $this->assertTrue(Schema::hasColumns('atlas_relays', [
            'request_source',
            'headers',
            'payload',
            'status',
            'mode',
            'failure_reason',
            'response_status',
            'response_payload',
            'is_retry',
            'retry_seconds',
            'retry_max_attempts',
            'is_delay',
            'delay_seconds',
            'timeout_seconds',
            'http_timeout_seconds',
            'retry_at',
        ]));

        $this->assertTrue(Schema::hasColumns('atlas_relay_routes', [
            'method',
            'path',
            'type',
            'destination',
            'is_retry',
            'retry_seconds',
            'retry_max_attempts',
            'is_delay',
            'delay_seconds',
            'timeout_seconds',
            'http_timeout_seconds',
        ]));

        $this->assertTrue(Schema::hasTable('atlas_relay_logs'));
        $this->assertTrue(Schema::hasTable('atlas_relay_archives'));
        $this->assertTrue(Schema::hasTable('atlas_relay_log_archives'));
    }
}
