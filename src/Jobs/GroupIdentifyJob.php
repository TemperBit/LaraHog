<?php

namespace TemperBit\LaraHog\Jobs;

use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use TemperBit\LaraHog\LaraHogManager;

class GroupIdentifyJob implements ShouldQueueAfterCommit
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
            'distinct_id' => "\${$this->groupType}_{$this->groupKey}",
            'event' => '$groupidentify',
            'properties' => [
                '$group_type' => $this->groupType,
                '$group_key' => $this->groupKey,
                '$group_set' => $this->properties,
            ],
        ];

        if ($this->timestamp !== null) {
            $message['timestamp'] = $this->timestamp;
        }

        $larahog->getClient()->capture($message);

        if ($this->flushAfterHandling) {
            $larahog->flush();
        }
    }
}
