<?php

use Illuminate\Support\Facades\Queue;
use TemperBit\LaraHog\Jobs\CaptureJob;
use TemperBit\LaraHog\LaraHog;
use TemperBit\LaraHog\LaraHogManager;

it('resolves different connections independently', function () {
    config()->set('larahog.connections.marketing', [
        'enabled' => true,
        'project_token' => 'phc_marketing_key',
        'host' => 'https://us.i.posthog.com',
        'personal_api_key' => '',
        'dispatch_mode' => 'sync',
        'queue' => ['connection' => null, 'name' => 'default'],
        'feature_flags' => ['evaluate_locally' => false, 'send_events' => true, 'default' => false],
        'sdk_options' => [],
    ]);

    $manager = app(LaraHogManager::class);

    $default = $manager->connection('default');
    $marketing = $manager->connection('marketing');

    expect($default)->not->toBe($marketing);
    expect($default->getConnectionName())->toBe('default');
    expect($marketing->getConnectionName())->toBe('marketing');
});

it('caches connection instances', function () {
    $manager = app(LaraHogManager::class);

    $a = $manager->connection('default');
    $b = $manager->connection('default');

    expect($a)->toBe($b);
});

it('returns default connection when no name is given', function () {
    $manager = app(LaraHogManager::class);

    $default = $manager->connection();
    $explicit = $manager->connection('default');

    expect($default)->toBe($explicit);
});

it('throws for unconfigured connections', function () {
    app(LaraHogManager::class)->connection('nonexistent');
})->throws(InvalidArgumentException::class, 'PostHog connection [nonexistent] is not configured.');

it('flushes all resolved connections', function () {
    config()->set('larahog.connections.secondary', [
        'enabled' => true,
        'project_token' => 'phc_secondary_key',
        'host' => 'https://us.i.posthog.com',
        'personal_api_key' => '',
        'dispatch_mode' => 'sync',
        'queue' => ['connection' => null, 'name' => 'default'],
        'feature_flags' => ['evaluate_locally' => false, 'send_events' => true, 'default' => false],
        'sdk_options' => [],
    ]);

    $manager = app(LaraHogManager::class);
    $manager->connection('default');
    $manager->connection('secondary');

    expect($manager->getConnections())->toHaveCount(2);

    // flushAll should not throw
    $manager->flushAll();
});

it('proxies method calls to the default connection', function () {
    $manager = app(LaraHogManager::class);

    expect($manager->isEnabled())->toBeTrue();
    expect($manager->getConnectionName())->toBe('default');
});

it('dispatches jobs with the correct connection name', function () {
    config()->set('larahog.connections.marketing', [
        'enabled' => true,
        'project_token' => 'phc_marketing_key',
        'host' => 'https://us.i.posthog.com',
        'personal_api_key' => '',
        'dispatch_mode' => 'queue',
        'queue' => ['connection' => null, 'name' => 'default'],
        'feature_flags' => ['evaluate_locally' => false, 'send_events' => true, 'default' => false],
        'sdk_options' => [],
    ]);

    Queue::fake();

    $manager = app(LaraHogManager::class);
    $manager->connection('marketing')->capture('user-1', 'campaign_click');

    Queue::assertPushed(CaptureJob::class, function (CaptureJob $job) {
        return $job->connectionName === 'marketing'
            && $job->distinctId === 'user-1'
            && $job->event === 'campaign_click';
    });
});

it('supports facade connection method', function () {
    $connection = TemperBit\LaraHog\Facades\LaraHog::connection('default');

    expect($connection)->toBeInstanceOf(LaraHog::class);
    expect($connection->getConnectionName())->toBe('default');
});
