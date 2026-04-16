<?php

namespace TemperBit\LaraHog\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use TemperBit\LaraHog\LaraHogManager;

class GroupIdentifyJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * @param  array<string, mixed>  $properties
     */
    public function __construct(
        public readonly string $connectionName,
        public readonly string $groupType,
        public readonly string $groupKey,
        public readonly array $properties = [],
    ) {}

    public function handle(LaraHogManager $manager): void
    {
        $larahog = $manager->connection($this->connectionName);

        if (! $larahog->isEnabled()) {
            return;
        }

        $larahog->getClient()->capture([
            'distinct_id' => "\${$this->groupType}_{$this->groupKey}",
            'event' => '$groupidentify',
            'properties' => [
                '$group_type' => $this->groupType,
                '$group_key' => $this->groupKey,
                '$group_set' => $this->properties,
            ],
        ]);
    }
}
