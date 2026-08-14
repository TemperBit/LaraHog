<?php

namespace TemperBit\LaraHog;

use DateTimeInterface;
use Illuminate\Support\Str;
use PostHog\Client;
use PostHog\ExceptionPayloadBuilder;
use TemperBit\LaraHog\Jobs\AliasJob;
use TemperBit\LaraHog\Jobs\CaptureJob;
use TemperBit\LaraHog\Jobs\GroupIdentifyJob;
use TemperBit\LaraHog\Jobs\IdentifyJob;
use Throwable;

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
    public function capture(
        ?string $distinctId,
        string $event,
        array $properties = [],
        array $groups = [],
        DateTimeInterface|int|float|string|null $timestamp = null,
    ): void {
        if (! $this->isEnabled()) {
            return;
        }

        if ($distinctId === null) {
            $distinctId = (string) Str::uuid();
            $properties['$process_person_profile'] = false;
        }

        if ($this->shouldQueue()) {
            CaptureJob::dispatch(
                $this->connectionName,
                $distinctId,
                $event,
                $properties,
                $groups,
                true,
                $this->resolveTimestamp($timestamp),
            )
                ->onConnection($this->queueConnection())
                ->onQueue($this->queueName());
        } else {
            CaptureJob::dispatchSync(
                $this->connectionName,
                $distinctId,
                $event,
                $properties,
                $groups,
                false,
                $this->resolveTimestamp($timestamp),
            );
        }
    }

    /**
     * @param  array<string, mixed>  $properties
     * @param  array<string, string>  $groups
     */
    public function captureException(
        Throwable|string $exception,
        ?string $distinctId = null,
        array $properties = [],
        array $groups = [],
        DateTimeInterface|int|float|string|null $timestamp = null,
    ): void {
        $maxFrames = (int) ($this->config['sdk_options']['error_tracking']['max_frames'] ?? 20);
        $exceptionList = ExceptionPayloadBuilder::buildExceptionList($exception, max(0, $maxFrames));

        if ($exceptionList === []) {
            return;
        }

        $this->capture($distinctId, '$exception', array_merge($properties, [
            '$exception_list' => $exceptionList,
            '$exception_handled' => ExceptionPayloadBuilder::getPrimaryHandled($exceptionList),
        ]), $groups, $timestamp);
    }

    /**
     * @param  array<string, mixed>  $properties
     * @param  array<string, string>  $groups
     */
    public function identify(
        string $distinctId,
        array $properties = [],
        array $groups = [],
        DateTimeInterface|int|float|string|null $timestamp = null,
    ): void {
        if (! $this->isEnabled()) {
            return;
        }

        if ($this->shouldQueue()) {
            IdentifyJob::dispatch(
                $this->connectionName,
                $distinctId,
                $properties,
                $groups,
                true,
                $this->resolveTimestamp($timestamp),
            )
                ->onConnection($this->queueConnection())
                ->onQueue($this->queueName());
        } else {
            IdentifyJob::dispatchSync(
                $this->connectionName,
                $distinctId,
                $properties,
                $groups,
                false,
                $this->resolveTimestamp($timestamp),
            );
        }
    }

    public function alias(
        string $distinctId,
        string $alias,
        DateTimeInterface|int|float|string|null $timestamp = null,
    ): void {
        if (! $this->isEnabled()) {
            return;
        }

        if ($this->shouldQueue()) {
            AliasJob::dispatch(
                $this->connectionName,
                $distinctId,
                $alias,
                true,
                $this->resolveTimestamp($timestamp),
            )
                ->onConnection($this->queueConnection())
                ->onQueue($this->queueName());
        } else {
            AliasJob::dispatchSync(
                $this->connectionName,
                $distinctId,
                $alias,
                false,
                $this->resolveTimestamp($timestamp),
            );
        }
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public function groupIdentify(
        string $groupType,
        string $groupKey,
        array $properties = [],
        DateTimeInterface|int|float|string|null $timestamp = null,
    ): void {
        if (! $this->isEnabled()) {
            return;
        }

        if ($this->shouldQueue()) {
            GroupIdentifyJob::dispatch(
                $this->connectionName,
                $groupType,
                $groupKey,
                $properties,
                true,
                $this->resolveTimestamp($timestamp),
            )
                ->onConnection($this->queueConnection())
                ->onQueue($this->queueName());
        } else {
            GroupIdentifyJob::dispatchSync(
                $this->connectionName,
                $groupType,
                $groupKey,
                $properties,
                false,
                $this->resolveTimestamp($timestamp),
            );
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
        $connection = $this->config['queue']['connection'] ?? null;

        return filled($connection) ? (string) $connection : null;
    }

    private function queueName(): string
    {
        /** @var string */
        return $this->config['queue']['name'] ?? 'default';
    }

    private function resolveTimestamp(
        DateTimeInterface|int|float|string|null $timestamp,
    ): int|float|string {
        if ($timestamp instanceof DateTimeInterface) {
            return $timestamp->format('Y-m-d\TH:i:s.uP');
        }

        return $timestamp ?? microtime(true);
    }
}
