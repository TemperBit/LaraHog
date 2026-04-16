<?php

namespace TemperBit\LaraHog;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use TemperBit\LaraHog\Commands\StatusCommand;

class LaraHogServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('larahog')
            ->hasConfigFile()
            ->hasCommands(StatusCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(LaraHogManager::class, function ($app) {
            return new LaraHogManager($app->make(ConfigRepository::class));
        });

        $this->app->bind(LaraHog::class, function ($app) {
            return $app->make(LaraHogManager::class)->connection();
        });
    }

    public function packageBooted(): void
    {
        $this->app->terminating(function () {
            $this->app->make(LaraHogManager::class)->flushAll();
        });
    }
}
