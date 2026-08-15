<?php

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
