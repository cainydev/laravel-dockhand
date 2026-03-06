<?php

use Cainy\Dockhand\Enums\MediaType;
use Cainy\Dockhand\Resources\ManifestListEntry;
use Cainy\Dockhand\Resources\Platform;

it('parses from array', function () {
    $entry = ManifestListEntry::parse('repo', [
        'mediaType' => 'application/vnd.docker.distribution.manifest.v2+json',
        'digest' => 'sha256:entry1',
        'size' => 528,
        'platform' => ['os' => 'linux', 'architecture' => 'amd64'],
    ]);

    expect($entry->repository)->toBe('repo')
        ->and($entry->digest)->toBe('sha256:entry1')
        ->and($entry->mediaType)->toBe(MediaType::IMAGE_MANIFEST_V2)
        ->and($entry->size)->toBe(528)
        ->and($entry->platform->os)->toBe('linux')
        ->and($entry->platform->architecture)->toBe('amd64');
});

it('creates via factory method', function () {
    $platform = Platform::create('linux', 'arm64', 'v8');
    $entry = ManifestListEntry::create('repo', 'sha256:abc', MediaType::IMAGE_MANIFEST_V2, 256, $platform);
    expect($entry->platform->variant)->toBe('v8');
});

it('converts to array', function () {
    $platform = Platform::create('linux', 'amd64');
    $entry = ManifestListEntry::create('repo', 'sha256:abc', MediaType::IMAGE_MANIFEST_V2, 100, $platform);
    $array = $entry->toArray();

    expect($array)->toHaveKeys(['repository', 'digest', 'mediaType', 'size', 'platform'])
        ->and($array['platform']['os'])->toBe('linux');
});

it('implements jsonSerialize', function () {
    $platform = Platform::create('linux', 'amd64');
    $entry = ManifestListEntry::create('repo', 'sha256:abc', MediaType::IMAGE_MANIFEST_V2, 100, $platform);
    expect($entry->jsonSerialize())->toBe($entry->toArray());
});
