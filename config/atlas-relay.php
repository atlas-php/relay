<?php

declare(strict_types=1);

return [
    'tables' => [
        'relays' => 'atlas_relays',
        'relay_routes' => 'atlas_relay_routes',
        'relay_archives' => 'atlas_relay_archives',
    ],

    'database' => [
        'connection' => env('ATLAS_RELAY_DATABASE_CONNECTION'),
    ],

    'archiving' => [
        'archive_after_days' => env('ATLAS_RELAY_ARCHIVE_DAYS', 30),
        'purge_after_days' => env('ATLAS_RELAY_PURGE_DAYS', 180),
    ],

    'payload' => [
        'max_bytes' => 64 * 1024,
    ],

    'capture' => [
        'sensitive_headers' => [
            'authorization',
            'proxy-authorization',
            'x-api-key',
            'api-key',
            'cookie',
        ],
    ],

    'routing' => [
        'cache_ttl_seconds' => env('ATLAS_RELAY_ROUTE_CACHE_SECONDS', 1200),
        'cache_store' => env('ATLAS_RELAY_ROUTE_CACHE_STORE'),
    ],

    'http' => [
        'max_redirects' => env('ATLAS_RELAY_MAX_REDIRECTS', 3),
        'enforce_https' => env('ATLAS_RELAY_ENFORCE_HTTPS', true),
    ],

    'automation' => [
        'stuck_threshold_minutes' => env('ATLAS_RELAY_STUCK_THRESHOLD_MINUTES', 10),
        'timeout_buffer_seconds' => env('ATLAS_RELAY_TIMEOUT_BUFFER_SECONDS', 0),
    ],
];
