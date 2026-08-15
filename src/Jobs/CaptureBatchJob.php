<?php

namespace TemperBit\LaraHog\Jobs;

use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use TemperBit\LaraHog\LaraHogManager;

class CaptureBatchJob implements ShouldQueueAfterCommit
{
    use Dispatchable, Queueable;

    /**
     * @param  list<array{
     *     distinct_id: string,
     *     event: string,
     *     properties: array<string, mixed>,
     *     groups: array<string, string>,
     *     timestamp: int|float|string
     * }>  $messages
     */
    public function __construct(
        public readonly string $connectionName,
        public readonly array $messages,
        public readonly bool $flushAfterHandling = false,
        public readonly bool $historicalMigration = false,
    ) {}

    public function handle(LaraHogManager $manager): void
    {
        $larahog = $manager->connection($this->connectionName);

        if (! $larahog->isEnabled()) {
            return;
        }

        $client = $this->historicalMigration
            ? $larahog->getHistoricalMigrationClient()
            : $larahog->getClient();

        foreach ($this->messages as $capture) {
            $message = [
                'distinct_id' => $capture['distinct_id'],
                'event' => $capture['event'],
                'properties' => $capture['properties'],
                'timestamp' => $capture['timestamp'],
            ];

            if ($capture['groups'] !== []) {
                $message['$groups'] = $capture['groups'];
            }

            $client->capture($message);
        }

        if ($this->flushAfterHandling || $this->historicalMigration) {
            $client->flush();
        }
    }
}
