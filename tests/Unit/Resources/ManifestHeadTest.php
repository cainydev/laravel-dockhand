<?php

use Cainy\Dockhand\Resources\ManifestHead;

it('constructs correctly', function () {
    $head = new ManifestHead('sha256:abc', 1024, 'application/vnd.docker.distribution.manifest.v2+json');
    expect($head->digest)->toBe('sha256:abc')
        ->and($head->contentLength)->toBe(1024)
        ->and($head->mediaType)->toBe('application/vnd.docker.distribution.manifest.v2+json');
});

it('allows null mediaType', function () {
    $head = new ManifestHead('sha256:abc', 512, null);
    expect($head->mediaType)->toBeNull();
});

it('converts to array', function () {
    $head = new ManifestHead('sha256:abc', 1024, 'application/json');
    expect($head->toArray())->toBe([
        'digest' => 'sha256:abc',
        'contentLength' => 1024,
        'mediaType' => 'application/json',
    ]);
});

it('implements JsonSerializable', function () {
    $head = new ManifestHead('sha256:abc', 1024, null);
    expect($head->jsonSerialize())->toBe($head->toArray());
});
