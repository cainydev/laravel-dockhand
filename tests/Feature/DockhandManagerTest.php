<?php

use Cainy\Dockhand\Auth\BasicAuthenticator;
use Cainy\Dockhand\Auth\BearerTokenAuthenticator;
use Cainy\Dockhand\Auth\NullAuthenticator;
use Cainy\Dockhand\DockhandManager;
use Cainy\Dockhand\Drivers\DistributionDriver;
use Cainy\Dockhand\Drivers\ZotDriver;

it('returns default driver name from config', function () {
    $manager = app(DockhandManager::class);
    expect($manager->getDefaultDriver())->toBe('default');
});

it('resolves default connection as DistributionDriver', function () {
    $manager = app(DockhandManager::class);
    $connection = $manager->connection();
    expect($connection)->toBeInstanceOf(DistributionDriver::class);
});

it('caches connections', function () {
    $manager = app(DockhandManager::class);
    $first = $manager->connection();
    $second = $manager->connection();
    expect($first)->toBe($second);
});

it('resolves named connections', function () {
    config()->set('dockhand.connections.secondary', [
        'driver' => 'distribution',
        'base_uri' => 'http://other:5000/v2/',
        'auth' => ['driver' => 'null'],
        'logging' => ['driver' => null],
    ]);

    $manager = app(DockhandManager::class);
    $conn = $manager->connection('secondary');
    expect($conn)->toBeInstanceOf(DistributionDriver::class)
        ->and($conn->getBaseUrl())->toBe('http://other:5000/v2');
});

it('resolves zot driver', function () {
    config()->set('dockhand.connections.zot-test', [
        'driver' => 'zot',
        'base_uri' => 'http://zot:5000/v2/',
        'auth' => ['driver' => 'null'],
        'logging' => ['driver' => null],
    ]);

    $manager = app(DockhandManager::class);
    $conn = $manager->connection('zot-test');
    expect($conn)->toBeInstanceOf(ZotDriver::class);
});

it('typed zot getter works', function () {
    config()->set('dockhand.connections.zot-typed', [
        'driver' => 'zot',
        'base_uri' => 'http://zot:5000/v2/',
        'auth' => ['driver' => 'null'],
        'logging' => ['driver' => null],
    ]);

    $manager = app(DockhandManager::class);
    expect($manager->zot('zot-typed'))->toBeInstanceOf(ZotDriver::class);
});

it('typed zot getter throws for non-zot driver', function () {
    $manager = app(DockhandManager::class);
    $manager->zot(); // default is distribution
})->throws(InvalidArgumentException::class);

it('typed distribution getter works', function () {
    $manager = app(DockhandManager::class);
    expect($manager->distribution())->toBeInstanceOf(DistributionDriver::class);
});

it('typed distribution getter throws for non-distribution driver', function () {
    config()->set('dockhand.connections.default', [
        'driver' => 'zot',
        'base_uri' => 'http://zot:5000/v2/',
        'auth' => ['driver' => 'null'],
        'logging' => ['driver' => null],
    ]);

    $manager = new DockhandManager;
    $manager->distribution();
})->throws(InvalidArgumentException::class);

it('throws for unknown connection', function () {
    $manager = app(DockhandManager::class);
    $manager->connection('nonexistent');
})->throws(InvalidArgumentException::class, 'is not configured');

it('throws for unsupported driver', function () {
    config()->set('dockhand.connections.bad', [
        'driver' => 'unsupported',
        'base_uri' => 'http://localhost/v2/',
        'auth' => ['driver' => 'null'],
        'logging' => ['driver' => null],
    ]);

    $manager = app(DockhandManager::class);
    $manager->connection('bad');
})->throws(InvalidArgumentException::class, 'Unsupported Dockhand driver');

it('resolves basic auth', function () {
    config()->set('dockhand.connections.basic-test', [
        'driver' => 'distribution',
        'base_uri' => 'http://localhost/v2/',
        'auth' => ['driver' => 'basic', 'username' => 'user', 'password' => 'pass'],
        'logging' => ['driver' => null],
    ]);

    $manager = app(DockhandManager::class);
    $conn = $manager->connection('basic-test');
    expect($conn->getAuthenticator())->toBeInstanceOf(BasicAuthenticator::class);
});

it('resolves bearer auth', function () {
    config()->set('dockhand.connections.bearer-test', [
        'driver' => 'distribution',
        'base_uri' => 'http://localhost/v2/',
        'auth' => ['driver' => 'bearer', 'token' => 'my-token'],
        'logging' => ['driver' => null],
    ]);

    $manager = app(DockhandManager::class);
    $conn = $manager->connection('bearer-test');
    expect($conn->getAuthenticator())->toBeInstanceOf(BearerTokenAuthenticator::class);
});

it('resolves null/none auth', function () {
    $manager = app(DockhandManager::class);
    $conn = $manager->connection();
    expect($conn->getAuthenticator())->toBeInstanceOf(NullAuthenticator::class);
});

it('resolves jwt auth', function () {
    $keys = generateEcdsaKeyPair();

    config()->set('dockhand.connections.jwt-test', [
        'driver' => 'distribution',
        'base_uri' => 'http://localhost/v2/',
        'auth' => [
            'driver' => 'jwt',
            'authority_name' => 'auth',
            'registry_name' => 'registry',
            'jwt_private_key' => $keys['private'],
            'jwt_public_key' => $keys['public'],
        ],
        'logging' => ['driver' => null],
    ]);

    $manager = app(DockhandManager::class);
    $conn = $manager->connection('jwt-test');
    expect($conn->getAuthenticator())->toBeInstanceOf(\Cainy\Dockhand\Auth\JwtAuthenticator::class);

    cleanupKeyPair($keys);
});

it('throws for unsupported auth driver', function () {
    config()->set('dockhand.connections.bad-auth', [
        'driver' => 'distribution',
        'base_uri' => 'http://localhost/v2/',
        'auth' => ['driver' => 'ldap'],
        'logging' => ['driver' => null],
    ]);

    $manager = app(DockhandManager::class);
    $manager->connection('bad-auth');
})->throws(InvalidArgumentException::class, 'Unsupported Dockhand auth driver');

it('disconnects a connection', function () {
    $manager = app(DockhandManager::class);
    $first = $manager->connection();
    $manager->disconnect();
    $second = $manager->connection();

    // After disconnect, a new instance should be created
    expect($first)->not->toBe($second);
});

it('forwards calls to default connection', function () {
    $manager = app(DockhandManager::class);
    // __call should forward to default connection's method
    expect($manager->getBaseUrl())->toBe('http://localhost:5000/v2');
});

it('resolves apikey auth', function () {
    config()->set('dockhand.connections.apikey-test', [
        'driver' => 'distribution',
        'base_uri' => 'http://localhost/v2/',
        'auth' => ['driver' => 'apikey', 'api_key' => 'my-api-key'],
        'logging' => ['driver' => null],
    ]);

    $manager = app(DockhandManager::class);
    $conn = $manager->connection('apikey-test');
    expect($conn->getAuthenticator())->toBeInstanceOf(\Cainy\Dockhand\Auth\ApiKeyAuthenticator::class);
});

it('resolves a named log driver', function () {
    config()->set('dockhand.connections.logged', [
        'driver' => 'distribution',
        'base_uri' => 'http://localhost/v2/',
        'auth' => ['driver' => 'null'],
        'logging' => ['driver' => 'single'],
    ]);

    $manager = app(DockhandManager::class);
    $conn = $manager->connection('logged');
    expect($conn->logger())->toBeInstanceOf(\Psr\Log\LoggerInterface::class);
});

it('resolves zot driver with extension cache ttl', function () {
    config()->set('dockhand.connections.zot-ttl', [
        'driver' => 'zot',
        'base_uri' => 'http://zot:5000/v2/',
        'auth' => ['driver' => 'null'],
        'logging' => ['driver' => null],
        'extension_cache_ttl' => 600,
    ]);

    $manager = app(DockhandManager::class);
    $conn = $manager->connection('zot-ttl');
    expect($conn)->toBeInstanceOf(ZotDriver::class);
});
