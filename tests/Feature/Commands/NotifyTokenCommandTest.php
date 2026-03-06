<?php

it('outputs a JWT token', function () {
    $keys = generateEcdsaKeyPair();

    config()->set('dockhand.connections.default.auth', [
        'driver' => 'jwt',
        'authority_name' => 'auth',
        'registry_name' => 'registry',
        'jwt_private_key' => $keys['private'],
        'jwt_public_key' => $keys['public'],
    ]);

    // Re-resolve to pick up new config
    app()->forgetInstance(\Cainy\Dockhand\Services\TokenService::class);
    app()->forgetInstance(\Cainy\Dockhand\DockhandManager::class);

    $this->artisan('dockhand:notify-token')
        ->expectsOutput('Generated new authentication token:')
        ->assertSuccessful();

    cleanupKeyPair($keys);
});
