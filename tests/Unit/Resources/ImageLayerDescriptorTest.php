<?php

use Cainy\Dockhand\Enums\MediaType;
use Cainy\Dockhand\Resources\ImageLayerDescriptor;

it('constructs correctly', function () {
    $layer = new ImageLayerDescriptor('repo', 'sha256:abc', MediaType::IMAGE_LAYER_V1_TAR_GZIP, 32654);
    expect($layer->repository)->toBe('repo')
        ->and($layer->digest)->toBe('sha256:abc')
        ->and($layer->mediaType)->toBe(MediaType::IMAGE_LAYER_V1_TAR_GZIP)
        ->and($layer->size)->toBe(32654)
        ->and($layer->urls)->toBe([]);
});

it('parses from array', function () {
    $layer = ImageLayerDescriptor::parse('my/repo', [
        'mediaType' => 'application/vnd.oci.image.layer.v1.tar+gzip',
        'digest' => 'sha256:layer1',
        'size' => 5000,
    ]);
    expect($layer->repository)->toBe('my/repo')
        ->and($layer->digest)->toBe('sha256:layer1')
        ->and($layer->size)->toBe(5000)
        ->and($layer->urls)->toBe([]);
});

it('parses with urls', function () {
    $layer = ImageLayerDescriptor::parse('repo', [
        'mediaType' => 'application/vnd.oci.image.layer.v1.tar+gzip',
        'digest' => 'sha256:abc',
        'size' => 100,
        'urls' => ['https://example.com/layer1'],
    ]);
    expect($layer->urls)->toBe(['https://example.com/layer1']);
});

it('throws on invalid data', function () {
    ImageLayerDescriptor::parse('repo', ['mediaType' => 'x']);
})->throws(ParseError::class);

it('converts to array', function () {
    $layer = ImageLayerDescriptor::create('repo', 'sha256:abc', MediaType::IMAGE_LAYER_V1_TAR_GZIP, 100, ['https://example.com']);
    expect($layer->toArray())->toBe([
        'repository' => 'repo',
        'digest' => 'sha256:abc',
        'mediaType' => 'application/vnd.oci.image.layer.v1.tar+gzip',
        'size' => 100,
        'urls' => ['https://example.com'],
    ]);
});

it('implements jsonSerialize', function () {
    $layer = ImageLayerDescriptor::create('repo', 'sha256:abc', MediaType::IMAGE_LAYER_V1_TAR_GZIP, 100);
    expect($layer->jsonSerialize())->toBe($layer->toArray());
});
