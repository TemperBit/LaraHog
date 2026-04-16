<?php

namespace TemperBit\LaraHog\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use TemperBit\LaraHog\LaraHogManager;

class AliasJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public readonly string $connectionName,
        public readonly string $distinctId,
        public readonly string $alias,
    ) {}

    public function handle(LaraHogManager $manager): void
    {
        $larahog = $manager->connection($this->connectionName);

        if (! $larahog->isEnabled()) {
            return;
        }

        $larahog->getClient()->alias([
            'distinct_id' => $this->distinctId,
            'alias' => $this->alias,
        ]);
    }
}
