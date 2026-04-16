<?php

namespace TemperBit\LaraHog;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use InvalidArgumentException;

/**
 * @mixin LaraHog
 */
class LaraHogManager
{
    /** @var array<string, LaraHog> */
    private array $connections = [];

    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    public function connection(?string $name = null): LaraHog
    {
        $name ??= $this->getDefaultConnection();

        if (! isset($this->connections[$name])) {
            $this->connections[$name] = new LaraHog($name, $this->resolveConfig($name));
        }

        return $this->connections[$name];
    }

    public function getDefaultConnection(): string
    {
        /** @var string */
        return $this->config->get('larahog.default', 'default');
    }

    public function flushAll(): void
    {
        foreach ($this->connections as $connection) {
            $connection->flush();
        }
    }

    /**
     * @return array<string, LaraHog>
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveConfig(string $name): array
    {
        /** @var array<string, mixed>|null $config */
        $config = $this->config->get("larahog.connections.{$name}");

        if ($config === null) {
            throw new InvalidArgumentException("PostHog connection [{$name}] is not configured.");
        }

        return $config;
    }

    /**
     * @param  array<int, mixed>  $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->connection()->{$method}(...$parameters);
    }
}
