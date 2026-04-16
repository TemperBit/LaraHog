<?php

namespace TemperBit\LaraHog\Facades;

use Illuminate\Support\Facades\Facade;
use TemperBit\LaraHog\LaraHogManager;

/**
 * @method static \TemperBit\LaraHog\LaraHog connection(?string $name = null)
 * @method static void capture(?string $distinctId, string $event, array<string, mixed> $properties = [], array<string, string> $groups = [])
 * @method static void identify(string $distinctId, array<string, mixed> $properties = [], array<string, string> $groups = [])
 * @method static void alias(string $distinctId, string $alias)
 * @method static void groupIdentify(string $groupType, string $groupKey, array<string, mixed> $properties = [])
 * @method static \PostHog\Client getClient()
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
