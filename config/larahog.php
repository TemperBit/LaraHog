<?php

// config for TemperBit/LaraHog
return [

    /*
    |--------------------------------------------------------------------------
    | Default Connection
    |--------------------------------------------------------------------------
    |
    | The default PostHog connection to use. This corresponds to one of the
    | connections defined in the "connections" array below.
    |
    */
    'default' => env('POSTHOG_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | PostHog Connections
    |--------------------------------------------------------------------------
    |
    | Each connection represents a PostHog project you want to send data to.
    | You may define as many connections as your application requires.
    |
    | Usage:
    |   LaraHog::capture(...)                             // uses default
    |   LaraHog::connection('marketing')->capture(...)    // uses "marketing"
    |
    */
    'connections' => [

        'default' => [
            'enabled' => env('POSTHOG_ENABLED', true),
            'project_token' => env('POSTHOG_PROJECT_TOKEN', ''),
            'host' => env('POSTHOG_HOST', 'https://us.i.posthog.com'),
            'dispatch_mode' => env('POSTHOG_DISPATCH_MODE', 'sync'),
            'queue' => [
                'connection' => env('POSTHOG_QUEUE_CONNECTION'),
                'name' => env('POSTHOG_QUEUE_NAME', 'default'),
            ],
            'sdk_options' => [
                // 'debug' => true,
                // 'ssl' => true,
            ],
        ],

    ],

];
