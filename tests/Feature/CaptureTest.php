<?php

use PostHog\Client;
use TemperBit\LaraHog\LaraHog;

it('calls capture on the posthog client', function () {
    $client = Mockery::mock(Client::class);
    $client->shouldReceive('capture')->once()->with(Mockery::on(function (array $message) {
        return $message['distinct_id'] === 'user-1'
            && $message['event'] === 'test-event'
            && $message['properties'] === ['key' => 'value'];
    }))->andReturn(true);

    $larahog = app(LaraHog::class);
    $reflection = new ReflectionProperty($larahog, 'client');
    $reflection->setValue($larahog, $client);

    $larahog->capture('user-1', 'test-event', ['key' => 'value']);
});

it('includes groups in capture message', function () {
    $client = Mockery::mock(Client::class);
    $client->shouldReceive('capture')->once()->with(Mockery::on(function (array $message) {
        return $message['$groups'] === ['company' => 'acme'];
    }))->andReturn(true);

    $larahog = app(LaraHog::class);
    $reflection = new ReflectionProperty($larahog, 'client');
    $reflection->setValue($larahog, $client);

    $larahog->capture('user-1', 'test-event', [], ['company' => 'acme']);
});

it('does not capture when disabled', function () {
    config()->set('larahog.connections.default.enabled', false);

    $client = Mockery::mock(Client::class);
    $client->shouldNotReceive('capture');

    $larahog = app(LaraHog::class);
    $reflection = new ReflectionProperty($larahog, 'client');
    $reflection->setValue($larahog, $client);

    $larahog->capture('user-1', 'test-event');
});
