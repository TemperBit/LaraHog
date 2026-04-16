<?php

use PostHog\Client;
use TemperBit\LaraHog\LaraHog;

it('calls identify on the posthog client', function () {
    $client = Mockery::mock(Client::class);
    $client->shouldReceive('identify')->once()->with(Mockery::on(function (array $message) {
        return $message['distinct_id'] === 'user-1'
            && $message['properties'] === ['name' => 'John'];
    }))->andReturn(true);

    $larahog = app(LaraHog::class);
    $reflection = new ReflectionProperty($larahog, 'client');
    $reflection->setValue($larahog, $client);

    $larahog->identify('user-1', ['name' => 'John']);
});

it('calls alias on the posthog client', function () {
    $client = Mockery::mock(Client::class);
    $client->shouldReceive('alias')->once()->with(Mockery::on(function (array $message) {
        return $message['distinct_id'] === 'user-1'
            && $message['alias'] === 'anon-abc';
    }))->andReturn(true);

    $larahog = app(LaraHog::class);
    $reflection = new ReflectionProperty($larahog, 'client');
    $reflection->setValue($larahog, $client);

    $larahog->alias('user-1', 'anon-abc');
});

it('calls groupIdentify via capture with correct message shape', function () {
    $client = Mockery::mock(Client::class);
    $client->shouldReceive('capture')->once()->with(Mockery::on(function (array $message) {
        return $message['event'] === '$groupidentify'
            && $message['properties']['$group_type'] === 'company'
            && $message['properties']['$group_key'] === 'acme'
            && $message['properties']['$group_set'] === ['name' => 'Acme Inc'];
    }))->andReturn(true);

    $larahog = app(LaraHog::class);
    $reflection = new ReflectionProperty($larahog, 'client');
    $reflection->setValue($larahog, $client);

    $larahog->groupIdentify('company', 'acme', ['name' => 'Acme Inc']);
});
