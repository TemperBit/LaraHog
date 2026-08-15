<?php

namespace TemperBit\LaraHog;

use PostHog\Client;
use PostHog\HttpClient;
use TemperBit\LaraHog\Consumers\HistoricalMigrationLibCurl;

class HistoricalMigrationClient extends Client
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        string $apiKey,
        array $options = [],
        ?HttpClient $httpClient = null,
    ) {
        $options['consumer'] = 'lib_curl';

        parent::__construct($apiKey, $options, $httpClient);

        $this->consumer = new HistoricalMigrationLibCurl($apiKey, $options, $httpClient);
    }
}
