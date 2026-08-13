<?php

use TemperBit\LaraHog\LaraHog;
use TemperBit\LaraHog\LaraHogManager;

it('resolves larahog from the container', function () {
    $larahog = app(LaraHog::class);

    expect($larahog)->toBeInstanceOf(LaraHog::class);
});

it('returns the same default connection instance', function () {
    $a = app(LaraHog::class);
    $b = app(LaraHog::class);

    expect($a)->toBe($b);
});

it('resolves the manager once per application scope', function () {
    $a = app(LaraHogManager::class);
    $b = app(LaraHogManager::class);

    expect($a)->toBe($b);

    app()->forgetScopedInstances();

    expect(app(LaraHogManager::class))->not->toBe($a);
});

it('reports enabled when project token is set', function () {
    expect(app(LaraHog::class)->isEnabled())->toBeTrue();
});

it('reports disabled when config is false', function () {
    config()->set('larahog.connections.default.enabled', false);

    expect(app(LaraHog::class)->isEnabled())->toBeFalse();
});

it('reports disabled when project token is empty', function () {
    config()->set('larahog.connections.default.project_token', '');

    expect(app(LaraHog::class)->isEnabled())->toBeFalse();
});
