<?php

namespace TemperBit\LaraHog\Jobs;

use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use TemperBit\LaraHog\LaraHogManager;

class IdentifyJob implements ShouldQueueAfterCommit
{
    use Dispatchable, Queueable;

    /**
     * @param  array<string, mixed>  $properties
     * @param  array<string, string>  $groups
     */
    public function __construct(
        public readonly string $connectionName,
        public readonly string $distinctId,
        public readonly array $properties = [],
        public readonly array $groups = [],
        public readonly bool $flushAfterHandling = false,
        public readonly int|float|string|null $timestamp = null,
    ) {}

    public function handle(LaraHogManager $manager): void
    {
        $larahog = $manager->connection($this->connectionName);

        if (! $larahog->isEnabled()) {
            return;
        }

        $message = [
            'distinct_id' => $this->distinctId,
            'properties' => $this->properties,
        ];

        if ($this->groups !== []) {
            $message['$groups'] = $this->groups;
        }

        if ($this->timestamp !== null) {
            $message['timestamp'] = $this->timestamp;
        }

        $larahog->getClient()->identify($message);

        if ($this->flushAfterHandling) {
            $larahog->flush();
        }
    }
}
