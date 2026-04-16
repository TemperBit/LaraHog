<?php

namespace TemperBit\LaraHog;

use Illuminate\Support\Str;
use PostHog\Client;
use TemperBit\LaraHog\Jobs\AliasJob;
use TemperBit\LaraHog\Jobs\CaptureJob;
use TemperBit\LaraHog\Jobs\GroupIdentifyJob;
use TemperBit\LaraHog\Jobs\IdentifyJob;

class LaraHog
{
    private ?Client $client = null;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly string $connectionName,
        private readonly array $config,
    ) {}

    public function getConnectionName(): string
    {
        return $this->connectionName;
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? true)
            && ($this->config['project_token'] ?? '') !== '';
    }

    /**
     * @param  array<string, mixed>  $properties
     * @param  array<string, string>  $groups
     */
    public function capture(?string $distinctId, string $event, array $properties = [], array $groups = []): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        if ($distinctId === null) {
            $distinctId = (string) Str::uuid();
            $properties['$process_person_profile'] = false;
        }

        if ($this->shouldQueue()) {
            CaptureJob::dispatch($this->connectionName, $distinctId, $event, $properties, $groups)
                ->onConnection($this->queueConnection())
                ->onQueue($this->queueName());
        } else {
            CaptureJob::dispatchSync($this->connectionName, $distinctId, $event, $properties, $groups);
        }
    }

    /**
     * @param  array<string, mixed>  $properties
     * @param  array<string, string>  $groups
     */
    public function identify(string $distinctId, array $properties = [], array $groups = []): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        if ($this->shouldQueue()) {
            IdentifyJob::dispatch($this->connectionName, $distinctId, $properties, $groups)
                ->onConnection($this->queueConnection())
                ->onQueue($this->queueName());
        } else {
            IdentifyJob::dispatchSync($this->connectionName, $distinctId, $properties, $groups);
        }
    }

    public function alias(string $distinctId, string $alias): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        if ($this->shouldQueue()) {
            AliasJob::dispatch($this->connectionName, $distinctId, $alias)
                ->onConnection($this->queueConnection())
                ->onQueue($this->queueName());
        } else {
            AliasJob::dispatchSync($this->connectionName, $distinctId, $alias);
        }
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public function groupIdentify(string $groupType, string $groupKey, array $properties = []): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        if ($this->shouldQueue()) {
            GroupIdentifyJob::dispatch($this->connectionName, $groupType, $groupKey, $properties)
                ->onConnection($this->queueConnection())
                ->onQueue($this->queueName());
        } else {
            GroupIdentifyJob::dispatchSync($this->connectionName, $groupType, $groupKey, $properties);
        }
    }

    public function getClient(): Client
    {
        if ($this->client === null) {
            $this->client = $this->createClient();
        }

        return $this->client;
    }

    public function flush(): void
    {
        if ($this->client !== null) {
            $this->client->flush();
        }
    }

    private function createClient(): Client
    {
        /** @var array<string, mixed> $sdkOptions */
        $sdkOptions = $this->config['sdk_options'] ?? [];

        $options = array_merge($sdkOptions, [
            'host' => $this->config['host'] ?? 'https://us.i.posthog.com',
        ]);

        return new Client(
            apiKey: (string) ($this->config['project_token'] ?? ''),
            options: $options,
        );
    }

    private function shouldQueue(): bool
    {
        return ($this->config['dispatch_mode'] ?? 'sync') === 'queue';
    }

    private function queueConnection(): ?string
    {
        /** @var string|null */
        return $this->config['queue']['connection'] ?? null;
    }

    private function queueName(): string
    {
        /** @var string */
        return $this->config['queue']['name'] ?? 'default';
    }
}
