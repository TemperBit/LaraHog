<?php

namespace TemperBit\LaraHog\Facades;

use Illuminate\Support\Facades\Facade;
use TemperBit\LaraHog\LaraHogManager;

/**
 * @method static \TemperBit\LaraHog\LaraHog connection(?string $name = null)
 * @method static void capture(?string $distinctId, string $event, array<string, mixed> $properties = [], array<string, string> $groups = [], \DateTimeInterface|int|float|string|null $timestamp = null)
 * @method static void captureBatch(array<int, array<string, mixed>> $events, bool $historicalMigration = false)
 * @method static void captureException(\Throwable|string $exception, ?string $distinctId = null, array<string, mixed> $properties = [], array<string, string> $groups = [], \DateTimeInterface|int|float|string|null $timestamp = null)
 * @method static void identify(string $distinctId, array<string, mixed> $properties = [], array<string, string> $groups = [], \DateTimeInterface|int|float|string|null $timestamp = null)
 * @method static void alias(string $distinctId, string $alias, \DateTimeInterface|int|float|string|null $timestamp = null)
 * @method static void groupIdentify(string $groupType, string $groupKey, array<string, mixed> $properties = [], \DateTimeInterface|int|float|string|null $timestamp = null)
 * @method static \PostHog\Client getClient()
 * @method static \PostHog\Client getHistoricalMigrationClient()
 * @method static void flush()
 * @method static bool isEnabled()
 * @method static void flushAll()
 * @method static string getDefaultConnection()
 *
 * @see LaraHogManager
 */
class LaraHog extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LaraHogManager::class;
    }
}
