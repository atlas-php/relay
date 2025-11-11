<?php

declare(strict_types=1);

namespace AtlasRelay\Support;

use Illuminate\Console\Scheduling\Schedule;

/**
 * Helper for registering Atlas Relay automation commands with the Laravel scheduler.
 */
class RelayScheduler
{
    public static function register(Schedule $schedule): void
    {
        $schedule->command('atlas-relay:retry-overdue')
            ->cron(config('atlas-relay.automation.retry_overdue_cron', '*/1 * * * *'));

        $schedule->command('atlas-relay:requeue-stuck')
            ->cron(config('atlas-relay.automation.stuck_requeue_cron', '*/10 * * * *'));

        $schedule->command('atlas-relay:enforce-timeouts')
            ->cron(config('atlas-relay.automation.timeout_enforcement_cron', '0 * * * *'));

        $schedule->command('atlas-relay:archive')
            ->cron(config('atlas-relay.automation.archive_cron', '0 22 * * *'));

        $schedule->command('atlas-relay:purge-archives')
            ->cron(config('atlas-relay.automation.purge_cron', '0 23 * * *'));
    }
}
