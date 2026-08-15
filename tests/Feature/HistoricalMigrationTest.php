<?php

use PostHog\Client;
use PostHog\HttpClient;
use PostHog\HttpResponse;
use TemperBit\LaraHog\HistoricalMigrationClient;
use TemperBit\LaraHog\LaraHog;

it('adds the historical migration flag to the batch payload', function () {
    $httpClient = Mockery::mock(HttpClient::class);
    $httpClient
        ->shouldReceive('sendRequest')
        ->once()
        ->with(
            '/batch/',
            Mockery::on(function (string $json): bool {
                $payload = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

                return $payload['historical_migration'] === true
                    && count($payload['batch']) === 2
                    && ! array_key_exists('historical_migration', $payload['batch'][0])
                    && ! array_key_exists('historical_migration', $payload['batch'][1]);
            }),
            Mockery::type('array'),
            Mockery::type('array'),
        )
        ->andReturn(new HttpResponse('{}', 200));

    $client = new HistoricalMigrationClient('project-token', [
        'batch_size' => 100,
    ], $httpClient);

    $larahog = app(LaraHog::class);
    $reflection = new ReflectionProperty($larahog, 'historicalMigrationClient');
    $reflection->setValue($larahog, $client);

    $larahog->captureBatch([
        ['distinctId' => 'user-1', 'event' => 'historical-event'],
        ['distinctId' => 'user-2', 'event' => 'historical-event'],
    ], historicalMigration: true);
});

it('sends identify batches without historical migration', function () {
    $httpClient = Mockery::mock(HttpClient::class);
    $httpClient
        ->shouldReceive('sendRequest')
        ->once()
        ->with(
            '/batch/',
            Mockery::on(function (string $json): bool {
                $payload = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

                return ! array_key_exists('historical_migration', $payload)
                    && count($payload['batch']) === 2
                    && $payload['batch'][0]['event'] === '$identify'
                    && $payload['batch'][0]['distinct_id'] === 'user-1'
                    && $payload['batch'][0]['$set'] === ['name' => 'Jane']
                    && $payload['batch'][1]['event'] === '$identify'
                    && $payload['batch'][1]['distinct_id'] === 'user-2'
                    && $payload['batch'][1]['$set'] === ['name' => 'John'];
            }),
            Mockery::type('array'),
            Mockery::type('array'),
        )
        ->andReturn(new HttpResponse('{}', 200));

    $client = new Client('project-token', [
        'consumer' => 'lib_curl',
        'batch_size' => 100,
    ], $httpClient);

    $larahog = app(LaraHog::class);
    $reflection = new ReflectionProperty($larahog, 'client');
    $reflection->setValue($larahog, $client);

    $larahog->identifyBatch([
        ['distinctId' => 'user-1', 'properties' => ['name' => 'Jane']],
        ['distinctId' => 'user-2', 'properties' => ['name' => 'John']],
    ]);
    $larahog->flush();
});

it('sends group identify batches without historical migration', function () {
    $httpClient = Mockery::mock(HttpClient::class);
    $httpClient
        ->shouldReceive('sendRequest')
        ->once()
        ->with(
            '/batch/',
            Mockery::on(function (string $json): bool {
                $payload = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

                return ! array_key_exists('historical_migration', $payload)
                    && count($payload['batch']) === 2
                    && $payload['batch'][0]['event'] === '$groupidentify'
                    && $payload['batch'][0]['properties']['$group_type'] === 'company'
                    && $payload['batch'][0]['properties']['$group_key'] === 'acme'
                    && $payload['batch'][0]['properties']['$group_set'] === ['name' => 'Acme']
                    && $payload['batch'][1]['event'] === '$groupidentify'
                    && $payload['batch'][1]['properties']['$group_key'] === 'globex';
            }),
            Mockery::type('array'),
            Mockery::type('array'),
        )
        ->andReturn(new HttpResponse('{}', 200));

    $client = new Client('project-token', [
        'consumer' => 'lib_curl',
        'batch_size' => 100,
    ], $httpClient);

    $larahog = app(LaraHog::class);
    $reflection = new ReflectionProperty($larahog, 'client');
    $reflection->setValue($larahog, $client);

    $larahog->groupIdentifyBatch([
        ['groupType' => 'company', 'groupKey' => 'acme', 'properties' => ['name' => 'Acme']],
        ['groupType' => 'company', 'groupKey' => 'globex', 'properties' => ['name' => 'Globex']],
    ]);
    $larahog->flush();
});
