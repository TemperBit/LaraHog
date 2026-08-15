<?php

namespace TemperBit\LaraHog\Consumers;

use PostHog\Consumer\LibCurl;

class HistoricalMigrationLibCurl extends LibCurl
{
    /**
     * @param  array<int, array<string, mixed>>  $batch
     * @return array<string, mixed>
     */
    protected function payload($batch)
    {
        return [
            ...parent::payload($batch),
            'historical_migration' => true,
        ];
    }
}
