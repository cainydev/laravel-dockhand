<?php

use Cainy\Dockhand\DockhandManager;

it('registers DockhandManager as singleton', function () {
    $first = app(DockhandManager::class);
    $second = app(DockhandManager::class);
    expect($first)->toBe($second);
});

it('registers dockhand alias', function () {
    expect(app('dockhand'))->toBeInstanceOf(DockhandManager::class);
});

it('resolves TokenService when jwt auth is configured', function () {
    $keys = generateEcdsaKeyPair();

    config()->set('dockhand.connections.default.auth', [
        'driver' => 'jwt',
        'authority_name' => 'auth',
        'registry_name' => 'registry',
        'jwt_private_key' => $keys['private'],
        'jwt_public_key' => $keys['public'],
    ]);

    // Force re-resolve
    app()->forgetInstance(\Cainy\Dockhand\Services\TokenService::class);
    app()->forgetInstance(DockhandManager::class);

    $service = app(\Cainy\Dockhand\Services\TokenService::class);
    expect($service)->toBeInstanceOf(\Cainy\Dockhand\Services\TokenService::class);

    cleanupKeyPair($keys);
});

it('registers the notify token command', function () {
    // Verify the command is registered by checking it's in the artisan command list
    $commands = \Illuminate\Support\Facades\Artisan::all();
    expect($commands)->toHaveKey('dockhand:notify-token');
});

it('resolves TokenService from config fallback when not jwt', function () {
    $keys = generateEcdsaKeyPair();

    config()->set('dockhand.connections.default.auth', [
        'driver' => 'null',
    ]);
    config()->set('dockhand.jwt_private_key', $keys['private']);
    config()->set('dockhand.jwt_public_key', $keys['public']);

    app()->forgetInstance(\Cainy\Dockhand\Services\TokenService::class);
    app()->forgetInstance(\Cainy\Dockhand\DockhandManager::class);

    $service = app(\Cainy\Dockhand\Services\TokenService::class);
    expect($service)->toBeInstanceOf(\Cainy\Dockhand\Services\TokenService::class);

    cleanupKeyPair($keys);
});

it('enables notifications route when configured', function () {
    config()->set('dockhand.notifications.enabled', true);

    // Re-register the package to pick up the route
    $routePath = __DIR__ . '/../../routes/notifications.php';
    if (file_exists($routePath)) {
        \Illuminate\Support\Facades\Route::middleware('api')->group($routePath);
    }

    $routes = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes())
        ->pluck('uri')
        ->toArray();

    expect($routes)->toContain('dockhand/notify');
});
