<?php

it('runs the status command', function () {
    $this->artisan('larahog:status')
        ->assertSuccessful();
});

it('shows configuration values', function () {
    $this->artisan('larahog:status')
        ->expectsTable(['Setting', 'Value'], [
            ['Connection', 'default'],
            ['Enabled', 'Yes'],
            ['Project Token', 'test****-key'],
            ['Host', 'https://test.posthog.com'],
            ['Dispatch Mode', 'sync'],
            ['SDK Active', 'Yes'],
        ])
        ->assertSuccessful();
});
