<?php

namespace TemperBit\LaraHog\Jobs;

use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use TemperBit\LaraHog\LaraHogManager;

class IdentifyBatchJob implements ShouldQueueAfterCommit
{
    use Dispatchable, Queueable;

    /**
     * @param  list<array{
     *     distinct_id: string,
     *     properties: array<string, mixed>,
     *     groups: array<string, string>,
     *     timestamp: int|float|string
     * }>  $messages
     */
    public function __construct(
        public readonly string $connectionName,
        public readonly array $messages,
        public readonly bool $flushAfterHandling = false,
    ) {}

    public function handle(LaraHogManager $manager): void
    {
        $larahog = $manager->connection($this->connectionName);

        if (! $larahog->isEnabled()) {
            return;
        }

        $client = $larahog->getClient();

        foreach ($this->messages as $identity) {
            $message = [
                'distinct_id' => $identity['distinct_id'],
                'properties' => $identity['properties'],
                'timestamp' => $identity['timestamp'],
            ];

            if ($identity['groups'] !== []) {
                $message['$groups'] = $identity['groups'];
            }

            $client->identify($message);
        }

        if ($this->flushAfterHandling) {
            $client->flush();
        }
    }
}
