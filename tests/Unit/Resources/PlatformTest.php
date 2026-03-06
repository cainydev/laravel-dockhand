<?php

use Cainy\Dockhand\Resources\Platform;

it('creates a platform', function () {
    $platform = new Platform('linux', 'amd64');
    expect($platform->os)->toBe('linux')
        ->and($platform->architecture)->toBe('amd64')
        ->and($platform->variant)->toBeNull();
});

it('creates a platform with variant', function () {
    $platform = new Platform('linux', 'arm64', 'v8');
    expect($platform->variant)->toBe('v8');
});

it('parses from array', function () {
    $platform = Platform::parse(['os' => 'linux', 'architecture' => 'amd64']);
    expect($platform)->not->toBeNull()
        ->and($platform->os)->toBe('linux')
        ->and($platform->architecture)->toBe('amd64');
});

it('parses from array with variant', function () {
    $platform = Platform::parse(['os' => 'linux', 'architecture' => 'arm64', 'variant' => 'v8']);
    expect($platform->variant)->toBe('v8');
});

it('parses from array with features', function () {
    $platform = Platform::parse(['os' => 'linux', 'architecture' => 'amd64', 'features' => ['sse4']]);
    expect($platform->features->toArray())->toBe(['sse4']);
});

it('converts to string', function () {
    expect(Platform::create('linux', 'amd64')->toString())->toBe('linux/amd64')
        ->and(Platform::create('linux', 'arm64', 'v8')->toString())->toBe('linux/arm64/v8');
});

it('validates valid combinations', function () {
    expect(Platform::create('linux', 'amd64')->isValid())->toBeTrue()
        ->and(Platform::create('windows', 'arm64')->isValid())->toBeTrue()
        ->and(Platform::create('darwin', 'arm64')->isValid())->toBeTrue();
});

it('rejects invalid combinations', function () {
    expect(Platform::create('linux', 'sparc')->isValid())->toBeFalse()
        ->and(Platform::create('unknown', 'amd64')->isValid())->toBeFalse();
});

it('converts to array', function () {
    $platform = Platform::create('linux', 'amd64', 'v1');
    expect($platform->toArray())->toBe([
        'os' => 'linux',
        'architecture' => 'amd64',
        'variant' => 'v1',
        'features' => [],
    ]);
});

it('implements JsonSerializable', function () {
    $platform = Platform::create('linux', 'amd64');
    expect($platform->jsonSerialize())->toBe($platform->toArray());
});

it('returns null when os or architecture is empty', function () {
    expect(Platform::parse(['os' => '', 'architecture' => 'amd64']))->toBeNull()
        ->and(Platform::parse(['os' => 'linux', 'architecture' => '']))->toBeNull();
});
