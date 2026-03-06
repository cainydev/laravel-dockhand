<?php

use Cainy\Dockhand\Resources\ImageManifest;

it('parses from data', function () {
    $data = sampleManifestData();
    $manifest = ImageManifest::parse('my/repo', 'sha256:manifest123', $data);

    expect($manifest->repository)->toBe('my/repo')
        ->and($manifest->digest)->toBe('sha256:manifest123')
        ->and($manifest->schemaVersion)->toBe(2)
        ->and($manifest->config->digest)->toBe('sha256:configabc123')
        ->and($manifest->layers)->toHaveCount(2)
        ->and($manifest->layers[0]->digest)->toBe('sha256:layer1abc123');
});

it('is not a manifest list', function () {
    $manifest = ImageManifest::parse('repo', 'sha256:abc', sampleManifestData());
    expect($manifest->isManifestList())->toBeFalse();
});

it('returns config size as getSize', function () {
    $manifest = ImageManifest::parse('repo', 'sha256:abc', sampleManifestData());
    expect($manifest->getSize())->toBe(1470);
});

it('throws on invalid data', function () {
    ImageManifest::parse('repo', 'sha256:abc', ['schemaVersion' => 2]);
})->throws(ParseError::class);

it('converts to array', function () {
    $manifest = ImageManifest::parse('repo', 'sha256:abc', sampleManifestData());
    $array = $manifest->toArray();

    expect($array)->toHaveKeys(['repository', 'digest', 'mediaType', 'schemaVersion', 'config', 'layers'])
        ->and($array['config'])->toBeArray()
        ->and($array['layers'])->toHaveCount(2);
});

it('implements jsonSerialize', function () {
    $manifest = ImageManifest::parse('repo', 'sha256:abc', sampleManifestData());
    expect($manifest->jsonSerialize())->toBe($manifest->toArray());
});

it('creates via factory method', function () {
    $data = sampleManifestData();
    $config = \Cainy\Dockhand\Resources\ImageConfigDescriptor::parse('repo', $data['config']);
    $layers = collect($data['layers'])->map(fn ($l) => \Cainy\Dockhand\Resources\ImageLayerDescriptor::parse('repo', $l));

    $manifest = ImageManifest::create(
        'repo',
        'sha256:abc',
        \Cainy\Dockhand\Enums\MediaType::IMAGE_MANIFEST_V2,
        2,
        $config,
        $layers,
    );
    expect($manifest)->toBeInstanceOf(ImageManifest::class);
});
