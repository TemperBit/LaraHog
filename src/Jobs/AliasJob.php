<?php

namespace TemperBit\LaraHog\Jobs;

use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use TemperBit\LaraHog\LaraHogManager;

class AliasJob implements ShouldQueueAfterCommit
{
    use Dispatchable, Queueable;

    public function __construct(
        public readonly string $connectionName,
        public readonly string $distinctId,
        public readonly string $alias,
        public readonly bool $flushAfterHandling = false,
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

        if ($this->flushAfterHandling) {
            $larahog->flush();
        }
    }
}
