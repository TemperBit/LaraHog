<?php

use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Support\Facades\Queue;
use TemperBit\LaraHog\Jobs\AliasJob;
use TemperBit\LaraHog\Jobs\CaptureBatchJob;
use TemperBit\LaraHog\Jobs\CaptureJob;
use TemperBit\LaraHog\Jobs\GroupIdentifyJob;
use TemperBit\LaraHog\Jobs\IdentifyJob;
use TemperBit\LaraHog\LaraHog;

beforeEach(function () {
    config()->set('larahog.connections.default.dispatch_mode', 'queue');
    Queue::fake();
});

it('dispatches analytics jobs after database transactions commit', function (object $job) {
    expect($job)->toBeInstanceOf(ShouldQueueAfterCommit::class);
})->with([
    'capture' => fn () => new CaptureJob('default', 'user-1', 'test-event'),
    'capture batch' => fn () => new CaptureBatchJob('default', []),
    'identify' => fn () => new IdentifyJob('default', 'user-1'),
    'alias' => fn () => new AliasJob('default', 'user-1', 'anonymous-user'),
    'group identify' => fn () => new GroupIdentifyJob('default', 'company', 'company-1'),
]);

it('dispatches one job for a capture batch', function () {
    app(LaraHog::class)->captureBatch([
        ['distinctId' => 'user-1', 'event' => 'first-event'],
        ['distinctId' => 'user-2', 'event' => 'second-event'],
    ], historicalMigration: true);

    Queue::assertPushed(CaptureBatchJob::class, function (CaptureBatchJob $job): bool {
        return count($job->messages) === 2
            && $job->messages[0]['distinct_id'] === 'user-1'
            && $job->messages[1]['distinct_id'] === 'user-2'
            && $job->historicalMigration
            && $job->flushAfterHandling;
    });
});

it('dispatches CaptureJob when dispatch mode is queue', function () {
    $timestamp = new DateTimeImmutable('2025-04-03T02:01:00.123456+00:00');

    app(LaraHog::class)->capture(
        'user-1',
        'test-event',
        ['key' => 'value'],
        ['company' => 'acme'],
        $timestamp,
    );

    Queue::assertPushed(CaptureJob::class, function (CaptureJob $job) {
        return $job->connectionName === 'default'
            && $job->distinctId === 'user-1'
            && $job->event === 'test-event'
            && $job->properties === ['key' => 'value']
            && $job->groups === ['company' => 'acme']
            && $job->timestamp === '2025-04-03T02:01:00.123456+00:00'
            && $job->flushAfterHandling;
    });
});

it('records capture time before a queued event is handled', function () {
    $beforeCapture = microtime(true);

    app(LaraHog::class)->capture('user-1', 'test-event');

    $afterCapture = microtime(true);

    Queue::assertPushed(CaptureJob::class, fn (CaptureJob $job): bool => is_float($job->timestamp)
        && $job->timestamp >= $beforeCapture
        && $job->timestamp <= $afterCapture);
});

it('normalizes exceptions before dispatching them to the queue', function () {
    app(LaraHog::class)->captureException(new RuntimeException('Something failed'), 'user-1', [
        'route' => 'dashboard',
    ], timestamp: '2025-04-03T02:01:00+00:00');

    Queue::assertPushed(CaptureJob::class, function (CaptureJob $job) {
        return $job->event === '$exception'
            && $job->distinctId === 'user-1'
            && $job->properties['route'] === 'dashboard'
            && $job->properties['$exception_list'][0]['type'] === RuntimeException::class
            && $job->properties['$exception_list'][0]['value'] === 'Something failed'
            && $job->timestamp === '2025-04-03T02:01:00+00:00';
    });
});

it('dispatches IdentifyJob when dispatch mode is queue', function () {
    app(LaraHog::class)->identify('user-1', ['name' => 'John'], timestamp: 1743645660);

    Queue::assertPushed(IdentifyJob::class, function (IdentifyJob $job) {
        return $job->connectionName === 'default'
            && $job->distinctId === 'user-1'
            && $job->properties === ['name' => 'John']
            && $job->timestamp === 1743645660
            && $job->flushAfterHandling;
    });
});

it('dispatches AliasJob when dispatch mode is queue', function () {
    app(LaraHog::class)->alias('user-1', 'anon-abc', 1743645660.123);

    Queue::assertPushed(AliasJob::class, function (AliasJob $job) {
        return $job->connectionName === 'default'
            && $job->distinctId === 'user-1'
            && $job->alias === 'anon-abc'
            && $job->timestamp === 1743645660.123
            && $job->flushAfterHandling;
    });
});

it('dispatches GroupIdentifyJob when dispatch mode is queue', function () {
    app(LaraHog::class)->groupIdentify(
        'company',
        'acme',
        ['name' => 'Acme Inc'],
        '2025-04-03T02:01:00+00:00',
    );

    Queue::assertPushed(GroupIdentifyJob::class, function (GroupIdentifyJob $job) {
        return $job->connectionName === 'default'
            && $job->groupType === 'company'
            && $job->groupKey === 'acme'
            && $job->properties === ['name' => 'Acme Inc']
            && $job->timestamp === '2025-04-03T02:01:00+00:00'
            && $job->flushAfterHandling;
    });
});

it('does not dispatch jobs when disabled', function () {
    config()->set('larahog.connections.default.enabled', false);

    app(LaraHog::class)->capture('user-1', 'test-event');
    app(LaraHog::class)->identify('user-1', ['name' => 'John']);
    app(LaraHog::class)->alias('user-1', 'anon-abc');
    app(LaraHog::class)->groupIdentify('company', 'acme');

    Queue::assertNothingPushed();
});

it('uses configured queue connection and name', function () {
    config()->set('larahog.connections.default.queue.connection', 'redis');
    config()->set('larahog.connections.default.queue.name', 'posthog');

    app(LaraHog::class)->capture('user-1', 'test-event');

    Queue::assertPushed(CaptureJob::class, function (CaptureJob $job) {
        return $job->connection === 'redis' && $job->queue === 'posthog';
    });
});

it('uses the default queue connection when the configured value is empty', function () {
    config()->set('larahog.connections.default.queue.connection', '');

    app(LaraHog::class)->capture('user-1', 'test-event');

    Queue::assertPushed(CaptureJob::class, fn (CaptureJob $job) => $job->connection === null);
});
