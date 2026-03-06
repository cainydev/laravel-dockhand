<?php

use Cainy\Dockhand\Resources\PushResult;

it('constructs correctly', function () {
    $result = new PushResult('/v2/repo/manifests/sha256:abc', 'sha256:abc');
    expect($result->location)->toBe('/v2/repo/manifests/sha256:abc')
        ->and($result->digest)->toBe('sha256:abc');
});

it('converts to array', function () {
    $result = new PushResult('/v2/repo/manifests/sha256:abc', 'sha256:abc');
    expect($result->toArray())->toBe([
        'location' => '/v2/repo/manifests/sha256:abc',
        'digest' => 'sha256:abc',
    ]);
});

it('implements JsonSerializable', function () {
    $result = new PushResult('/v2/test', 'sha256:def');
    expect($result->jsonSerialize())->toBe($result->toArray());
});
