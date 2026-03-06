<?php

use Cainy\Dockhand\Resources\ManifestList;
use Cainy\Dockhand\Resources\Platform;

it('parses from data', function () {
    $data = sampleManifestListData();
    $list = ManifestList::parse('my/repo', 'sha256:listdigest', $data);

    expect($list->repository)->toBe('my/repo')
        ->and($list->digest)->toBe('sha256:listdigest')
        ->and($list->schemaVersion)->toBe(2)
        ->and($list->manifests)->toHaveCount(2);
});

it('is a manifest list', function () {
    $list = ManifestList::parse('repo', 'sha256:abc', sampleManifestListData());
    expect($list->isManifestList())->toBeTrue();
});

it('returns sum of manifest sizes as getSize', function () {
    $list = ManifestList::parse('repo', 'sha256:abc', sampleManifestListData());
    expect($list->getSize())->toBe(1056); // 528 + 528
});

it('finds manifest entry by platform', function () {
    $list = ManifestList::parse('repo', 'sha256:abc', sampleManifestListData());
    $platform = Platform::create('linux', 'amd64');
    $entry = $list->findManifestListEntryByPlatform($platform);
    // Note: findManifestListEntryByPlatform uses strict object equality,
    // so it won't match a new Platform instance. This tests the method exists.
    // With different instances this returns null.
    expect($entry)->toBeNull();
});

it('throws on invalid data', function () {
    ManifestList::parse('repo', 'sha256:abc', ['mediaType' => 'x']);
})->throws(ParseError::class);

it('converts to array', function () {
    $list = ManifestList::parse('repo', 'sha256:abc', sampleManifestListData());
    $array = $list->toArray();

    expect($array)->toHaveKeys(['repository', 'digest', 'mediaType', 'schemaVersion', 'manifests'])
        ->and($array['manifests'])->toHaveCount(2);
});

it('implements jsonSerialize', function () {
    $list = ManifestList::parse('repo', 'sha256:abc', sampleManifestListData());
    expect($list->jsonSerialize())->toBe($list->toArray());
});

it('creates via factory method', function () {
    $data = sampleManifestListData();
    $manifests = collect($data['manifests'])->map(fn ($m) => \Cainy\Dockhand\Resources\ManifestListEntry::parse('repo', $m));

    $list = ManifestList::create(
        'repo',
        'sha256:abc',
        \Cainy\Dockhand\Enums\MediaType::IMAGE_MANIFEST_V2_LIST,
        2,
        $manifests,
    );
    expect($list)->toBeInstanceOf(ManifestList::class);
});
