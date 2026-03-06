<?php

use Cainy\Dockhand\Enums\MediaType;
use Cainy\Dockhand\Resources\ImageConfigDescriptor;

it('constructs correctly', function () {
    $desc = new ImageConfigDescriptor('repo', 'sha256:abc', MediaType::IMAGE_CONFIG_V1, 1470);
    expect($desc->repository)->toBe('repo')
        ->and($desc->digest)->toBe('sha256:abc')
        ->and($desc->mediaType)->toBe(MediaType::IMAGE_CONFIG_V1)
        ->and($desc->size)->toBe(1470);
});

it('parses from array', function () {
    $desc = ImageConfigDescriptor::parse('my/repo', [
        'mediaType' => 'application/vnd.oci.image.config.v1+json',
        'digest' => 'sha256:abc123',
        'size' => 2048,
    ]);
    expect($desc->repository)->toBe('my/repo')
        ->and($desc->digest)->toBe('sha256:abc123')
        ->and($desc->mediaType)->toBe(MediaType::IMAGE_CONFIG_V1)
        ->and($desc->size)->toBe(2048);
});

it('throws on invalid data', function () {
    ImageConfigDescriptor::parse('repo', ['mediaType' => 'application/json']);
})->throws(ParseError::class);

it('converts to array', function () {
    $desc = new ImageConfigDescriptor('repo', 'sha256:abc', MediaType::CONTAINER_CONFIG_V1, 1470);
    expect($desc->toArray())->toBe([
        'repository' => 'repo',
        'digest' => 'sha256:abc',
        'mediaType' => 'application/vnd.docker.container.image.v1+json',
        'size' => 1470,
    ]);
});

it('creates via factory method', function () {
    $desc = ImageConfigDescriptor::create('repo', 'sha256:x', MediaType::IMAGE_CONFIG_V1, 100);
    expect($desc)->toBeInstanceOf(ImageConfigDescriptor::class);
});

it('implements jsonSerialize', function () {
    $desc = new ImageConfigDescriptor('repo', 'sha256:abc', MediaType::IMAGE_CONFIG_V1, 1470);
    expect($desc->jsonSerialize())->toBe($desc->toArray());
});
