<?php

use Cainy\Dockhand\Auth\NullAuthenticator;
use Cainy\Dockhand\Drivers\DistributionDriver;
use Cainy\Dockhand\Enums\RegistryApiVersion;
use Illuminate\Support\Facades\Http;
use Psr\Log\NullLogger;

beforeEach(function () {
    $this->driver = new DistributionDriver('http://localhost:5000/v2', new NullAuthenticator, new NullLogger);
});

it('returns true when registry is online', function () {
    Http::fake([
        'localhost:5000/v2/' => Http::response('{}', 200),
    ]);

    expect($this->driver->isOnline())->toBeTrue();
});

it('returns false when registry is offline', function () {
    Http::fake([
        'localhost:5000/v2/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection refused'),
    ]);

    expect($this->driver->isOnline())->toBeFalse();
});

it('returns V2 api version', function () {
    Http::fake([
        'localhost:5000/v2/' => Http::response('{}', 200, [
            'Docker-Distribution-Api-Version' => 'registry/2.0',
        ]),
    ]);

    expect($this->driver->getApiVersion())->toBe(RegistryApiVersion::V2);
});

it('returns V1 api version', function () {
    Http::fake([
        'localhost:5000/v2/' => Http::response('{}', 200, [
            'Docker-Distribution-Api-Version' => 'registry/1.0',
        ]),
    ]);

    expect($this->driver->getApiVersion())->toBe(RegistryApiVersion::V1);
});

it('defaults to V2 when header is absent', function () {
    Http::fake([
        'localhost:5000/v2/' => Http::response('{}', 200),
    ]);

    expect($this->driver->getApiVersion())->toBe(RegistryApiVersion::V2);
});
