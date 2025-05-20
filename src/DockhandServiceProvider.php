<?php

namespace Cainy\Dockhand;

use Cainy\Dockhand\Commands\NotifyTokenCommand;
use Cainy\Dockhand\Services\TokenService;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use function config;

class DockhandServiceProvider extends PackageServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->singleton(TokenService::class, function () {
            return new TokenService(
                config('dockhand.jwt_private_key'),
                config('dockhand.jwt_public_key'));
        });

        $this->app->singleton(Dockhand::class, function () {
            return new Dockhand(
                config('dockhand.base_uri'),
                config('dockhand.registry_name'),
                config('dockhand.authority_name'));
        });
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-dockhand')
            ->hasConfigFile()
            ->hasCommand(NotifyTokenCommand::class);

        if (config('dockhand.notifications.enabled')) {
            $package->hasRoute('notifications');
        }
    }
}
