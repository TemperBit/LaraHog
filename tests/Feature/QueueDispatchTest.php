<?php

use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Support\Facades\Queue;
use TemperBit\LaraHog\Jobs\AliasJob;
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
    'identify' => fn () => new IdentifyJob('default', 'user-1'),
    'alias' => fn () => new AliasJob('default', 'user-1', 'anonymous-user'),
    'group identify' => fn () => new GroupIdentifyJob('default', 'company', 'company-1'),
]);

it('dispatches CaptureJob when dispatch mode is queue', function () {
    app(LaraHog::class)->capture('user-1', 'test-event', ['key' => 'value'], ['company' => 'acme']);

    Queue::assertPushed(CaptureJob::class, function (CaptureJob $job) {
        return $job->connectionName === 'default'
            && $job->distinctId === 'user-1'
            && $job->event === 'test-event'
            && $job->properties === ['key' => 'value']
            && $job->groups === ['company' => 'acme']
            && $job->flushAfterHandling;
    });
});

it('normalizes exceptions before dispatching them to the queue', function () {
    app(LaraHog::class)->captureException(new RuntimeException('Something failed'), 'user-1', [
        'route' => 'dashboard',
    ]);

    Queue::assertPushed(CaptureJob::class, function (CaptureJob $job) {
        return $job->event === '$exception'
            && $job->distinctId === 'user-1'
            && $job->properties['route'] === 'dashboard'
            && $job->properties['$exception_list'][0]['type'] === RuntimeException::class
            && $job->properties['$exception_list'][0]['value'] === 'Something failed';
    });
});

it('dispatches IdentifyJob when dispatch mode is queue', function () {
    app(LaraHog::class)->identify('user-1', ['name' => 'John']);

    Queue::assertPushed(IdentifyJob::class, function (IdentifyJob $job) {
        return $job->connectionName === 'default'
            && $job->distinctId === 'user-1'
            && $job->properties === ['name' => 'John']
            && $job->flushAfterHandling;
    });
});

it('dispatches AliasJob when dispatch mode is queue', function () {
    app(LaraHog::class)->alias('user-1', 'anon-abc');

    Queue::assertPushed(AliasJob::class, function (AliasJob $job) {
        return $job->connectionName === 'default'
            && $job->distinctId === 'user-1'
            && $job->alias === 'anon-abc'
            && $job->flushAfterHandling;
    });
});

it('dispatches GroupIdentifyJob when dispatch mode is queue', function () {
    app(LaraHog::class)->groupIdentify('company', 'acme', ['name' => 'Acme Inc']);

    Queue::assertPushed(GroupIdentifyJob::class, function (GroupIdentifyJob $job) {
        return $job->connectionName === 'default'
            && $job->groupType === 'company'
            && $job->groupKey === 'acme'
            && $job->properties === ['name' => 'Acme Inc']
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
