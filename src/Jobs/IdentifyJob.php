<?php

namespace TemperBit\LaraHog\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use TemperBit\LaraHog\LaraHogManager;

class IdentifyJob implements ShouldQueue
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

        $larahog->getClient()->identify($message);
    }
}
