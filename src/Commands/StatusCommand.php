<?php

namespace TemperBit\LaraHog\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use TemperBit\LaraHog\LaraHogManager;

class StatusCommand extends Command
{
    public $signature = 'larahog:status {--connection= : Show status for a specific connection}';

    public $description = 'Show LaraHog configuration status and test connectivity';

    public function handle(LaraHogManager $manager): int
    {
        /** @var string|null $connectionOption */
        $connectionOption = $this->option('connection');
        $connectionName = $connectionOption ?? $manager->getDefaultConnection();

        try {
            $larahog = $manager->connection($connectionName);
        } catch (\InvalidArgumentException) {
            $this->components->error("LaraHog connection [{$connectionName}] is not configured.");

            return self::FAILURE;
        }

        $this->components->info("LaraHog Status [{$connectionName}]");

        /** @var array<string, mixed> $connectionConfig */
        $connectionConfig = config("larahog.connections.{$connectionName}", []);

        $enabled = $connectionConfig['enabled'] ?? true;
        $apiKey = (string) ($connectionConfig['project_token'] ?? '');
        $host = (string) ($connectionConfig['host'] ?? '');
        $dispatchMode = (string) ($connectionConfig['dispatch_mode'] ?? 'sync');

        $this->table(['Setting', 'Value'], [
            ['Connection', $connectionName],
            ['Enabled', $enabled ? 'Yes' : 'No'],
            ['Project Token', $apiKey !== '' ? $this->maskKey($apiKey) : '(not set)'],
            ['Host', $host ?: '(not set)'],
            ['Dispatch Mode', $dispatchMode],
            ['SDK Active', $larahog->isEnabled() ? 'Yes' : 'No'],
        ]);

        if ($host !== '' && $apiKey !== '') {
            $this->newLine();
            $this->components->task('Testing connectivity to PostHog', function () use ($host) {
                try {
                    $response = Http::timeout(5)->get(rtrim($host, '/').'/decide/?v=3');

                    return $response->status() < 500;
                } catch (\Throwable) {
                    return false;
                }
            });
        }

        return self::SUCCESS;
    }

    private function maskKey(string $key): string
    {
        if (strlen($key) <= 8) {
            return str_repeat('*', strlen($key));
        }

        return substr($key, 0, 4).str_repeat('*', strlen($key) - 8).substr($key, -4);
    }
}
