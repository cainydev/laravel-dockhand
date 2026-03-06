<?php

use Cainy\Dockhand\Enums\MediaType;
use Cainy\Dockhand\Resources\ImageConfig;

it('parses from data', function () {
    $config = ImageConfig::parse('my/repo', 'sha256:cfg', MediaType::IMAGE_CONFIG_V1, sampleImageConfigData());

    expect($config->repository)->toBe('my/repo')
        ->and($config->digest)->toBe('sha256:cfg')
        ->and($config->mediaType)->toBe(MediaType::IMAGE_CONFIG_V1)
        ->and($config->platform->os)->toBe('linux')
        ->and($config->platform->architecture)->toBe('amd64')
        ->and($config->created->toIso8601String())->not->toBeEmpty();
});

it('throws on invalid data', function () {
    ImageConfig::parse('repo', 'sha256:x', MediaType::IMAGE_CONFIG_V1, ['os' => 'linux']);
})->throws(ParseError::class);

it('converts to array', function () {
    $config = ImageConfig::parse('repo', 'sha256:cfg', MediaType::IMAGE_CONFIG_V1, sampleImageConfigData());
    $array = $config->toArray();

    expect($array)->toHaveKeys(['repository', 'digest', 'mediaType', 'platform', 'created'])
        ->and($array['platform']['os'])->toBe('linux');
});

it('creates via factory', function () {
    $config = ImageConfig::create(
        'repo',
        'sha256:cfg',
        MediaType::IMAGE_CONFIG_V1,
        \Cainy\Dockhand\Resources\Platform::create('linux', 'amd64'),
        \Carbon\Carbon::parse('2024-01-15T10:30:00Z'),
    );
    expect($config)->toBeInstanceOf(ImageConfig::class);
});

it('implements jsonSerialize', function () {
    $config = ImageConfig::parse('repo', 'sha256:cfg', MediaType::IMAGE_CONFIG_V1, sampleImageConfigData());
    expect($config->jsonSerialize())->toBe($config->toArray());
});
