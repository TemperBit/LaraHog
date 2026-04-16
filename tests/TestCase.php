<?php

namespace TemperBit\LaraHog\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use TemperBit\LaraHog\LaraHogServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaraHogServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('larahog.default', 'default');
        $app['config']->set('larahog.connections.default', [
            'enabled' => true,
            'project_token' => 'test-api-key',
            'host' => 'https://test.posthog.com',
            'personal_api_key' => '',
            'dispatch_mode' => 'sync',
            'queue' => [
                'connection' => null,
                'name' => 'default',
            ],
            'feature_flags' => [
                'evaluate_locally' => false,
                'send_events' => true,
                'default' => false,
                'api_key' => '',
            ],
            'sdk_options' => [],
        ]);
    }
}
