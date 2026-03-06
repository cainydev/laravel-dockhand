<?php

use Cainy\Dockhand\Enums\MediaType;

it('has correct number of cases', function () {
    expect(MediaType::cases())->toHaveCount(16);
});

it('can convert to string', function () {
    expect(MediaType::IMAGE_MANIFEST_V2->toString())
        ->toBe('application/vnd.docker.distribution.manifest.v2+json');
});

it('creates from valid string', function () {
    expect(MediaType::fromString('application/vnd.docker.distribution.manifest.v2+json'))
        ->toBe(MediaType::IMAGE_MANIFEST_V2);
});

it('returns CUSTOM for unknown media type', function () {
    expect(MediaType::fromString('application/unknown'))
        ->toBe(MediaType::CUSTOM);
});

it('identifies image manifests correctly', function (MediaType $type) {
    expect($type->isImageManifest())->toBeTrue();
})->with([
    MediaType::OCI_IMAGE_MANIFEST_V1,
    MediaType::IMAGE_MANIFEST_V1,
    MediaType::IMAGE_MANIFEST_V1_SIGNED,
    MediaType::IMAGE_MANIFEST_V2,
]);

it('identifies non-image manifests correctly', function () {
    expect(MediaType::IMAGE_INDEX_V1->isImageManifest())->toBeFalse()
        ->and(MediaType::IMAGE_CONFIG_V1->isImageManifest())->toBeFalse();
});

it('identifies manifest lists correctly', function (MediaType $type) {
    expect($type->isManifestList())->toBeTrue();
})->with([
    MediaType::IMAGE_INDEX_V1,
    MediaType::IMAGE_MANIFEST_V2_LIST,
]);

it('identifies non-manifest lists', function () {
    expect(MediaType::IMAGE_MANIFEST_V2->isManifestList())->toBeFalse();
});

it('identifies custom type', function () {
    expect(MediaType::CUSTOM->isCustom())->toBeTrue()
        ->and(MediaType::IMAGE_MANIFEST_V2->isCustom())->toBeFalse();
});

it('identifies blob-like types', function () {
    expect(MediaType::IMAGE_LAYER_V1_TAR_GZIP->isBlobLike())->toBeTrue()
        ->and(MediaType::IMAGE_CONFIG_V1->isBlobLike())->toBeTrue()
        ->and(MediaType::OCTET_STREAM->isBlobLike())->toBeTrue()
        ->and(MediaType::CUSTOM->isBlobLike())->toBeTrue()
        ->and(MediaType::IMAGE_MANIFEST_V2->isBlobLike())->toBeFalse();
});

it('identifies image layers', function (MediaType $type) {
    expect($type->isImageLayer())->toBeTrue();
})->with([
    MediaType::IMAGE_LAYER_V1_TAR,
    MediaType::IMAGE_LAYER_V1_TAR_GZIP,
    MediaType::IMAGE_LAYER_V1_TAR_ZSTD,
]);

it('identifies image configs', function () {
    expect(MediaType::IMAGE_CONFIG_V1->isImageConfig())->toBeTrue()
        ->and(MediaType::CONTAINER_CONFIG_V1->isImageConfig())->toBeTrue()
        ->and(MediaType::IMAGE_MANIFEST_V2->isImageConfig())->toBeFalse();
});

it('identifies image rootfs', function () {
    expect(MediaType::IMAGE_ROOTFS_DIFF_TAR_GZIP->isImageRootfs())->toBeTrue()
        ->and(MediaType::IMAGE_LAYER_V1_TAR_GZIP->isImageRootfs())->toBeFalse();
});

it('returns manifest types as comma-separated string', function () {
    $result = MediaType::getManifestTypesAsString();
    expect($result)->toContain('application/vnd.docker.distribution.manifest.v1+json')
        ->toContain('application/vnd.docker.distribution.manifest.v2+json')
        ->toContain('application/vnd.oci.image.manifest.v1+json');
});
