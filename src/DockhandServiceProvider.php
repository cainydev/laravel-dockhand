<?php

namespace Cainy\Dockhand;

use Cainy\Dockhand\Auth\JwtAuthenticator;
use Cainy\Dockhand\Services\TokenService;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use function config;

class DockhandServiceProvider extends PackageServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->singleton(DockhandManager::class, function () {
            return new DockhandManager;
        });

        $this->app->alias(DockhandManager::class, 'dockhand');

        // Keep TokenService singleton for backward compat (NotifyTokenCommand, notifications route).
        // If the default connection uses JWT auth, reuse its TokenService; otherwise create one from config.
        $this->app->singleton(TokenService::class, function () {
            /** @var string $defaultConnection */
            $defaultConnection = config('dockhand.default', 'default');
            /** @var array<string, mixed> $authConfig */
            $authConfig = config("dockhand.connections.{$defaultConnection}.auth", []);

            if (($authConfig['driver'] ?? null) === 'jwt') {
                $manager = $this->app->make(DockhandManager::class);
                $driver = $manager->connection($defaultConnection);
                $authenticator = $driver->getAuthenticator();

                if ($authenticator instanceof JwtAuthenticator) {
                    return $authenticator->getTokenService();
                }
            }

            // Fallback: create from config directly
            /** @var non-empty-string $privateKey */
            $privateKey = $authConfig['jwt_private_key'] ?? config('dockhand.jwt_private_key') ?? '';
            /** @var non-empty-string $publicKey */
            $publicKey = $authConfig['jwt_public_key'] ?? config('dockhand.jwt_public_key') ?? '';

            return new TokenService($privateKey, $publicKey);
        });
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-dockhand')
            ->hasConfigFile()
            ->hasCommand(Commands\NotifyTokenCommand::class);

        if (config('dockhand.notifications.enabled')) {
            $package->hasRoute('notifications');
        }
    }
}
