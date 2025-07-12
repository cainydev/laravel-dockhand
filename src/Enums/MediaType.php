<?php

namespace Cainy\Dockhand\Enums;

use Illuminate\Support\Facades\Log;
use ValueError;

enum MediaType: string
{
    // Image Manifest Media Types
    case OCI_IMAGE_MANIFEST_V1 = 'application/vnd.oci.image.manifest.v1+json';
    case IMAGE_MANIFEST_V1 = 'application/vnd.docker.distribution.manifest.v1+json';
    case IMAGE_MANIFEST_V1_SIGNED = 'application/vnd.docker.distribution.manifest.v1+prettyjws';
    case IMAGE_MANIFEST_V2 = 'application/vnd.docker.distribution.manifest.v2+json';

    // Container config
    case CONTAINER_CONFIG_V1 = 'application/vnd.docker.container.image.v1+json';

    // Manifest List Media Types
    case IMAGE_INDEX_V1 = 'application/vnd.oci.image.index.v1+json';
    case IMAGE_MANIFEST_V2_LIST = 'application/vnd.docker.distribution.manifest.list.v2+json';

    // Image Config Media Types
    case IMAGE_CONFIG_V1 = 'application/vnd.oci.image.config.v1+json';

    // Image Layer Media Types
    case IMAGE_LAYER_V1_TAR = 'application/vnd.oci.image.layer.v1.tar';
    case IMAGE_LAYER_V1_TAR_GZIP = 'application/vnd.oci.image.layer.v1.tar+gzip';
    case IMAGE_LAYER_V1_TAR_ZSTD = 'application/vnd.oci.image.layer.v1.tar+zstd';
    case IMAGE_ROOTFS_DIFF_TAR_GZIP = 'application/vnd.docker.image.rootfs.diff.tar.gzip';
    case IMAGE_ROOTFS_FOREIGN_TAR_GZIP = 'application/vnd.docker.image.rootfs.foreign.tar.gzip';

    // Other Media Types
    case EMPTY_JSON = 'application/vnd.oci.empty.v1+json';
    case OCTET_STREAM = 'application/octet-stream';

    // Custom Media Type
    case CUSTOM = 'custom';

    /**
     * Create a MediaType from a string.
     * If the media type is unknown, return CUSTOM.
     */
    public static function fromString(string $mediaType): self
    {
        try {
            return self::from($mediaType);
        } catch (ValueError) {
            Log::warning("Unknown media type: {$mediaType}");

            return self::CUSTOM;
        }
    }

    /**
     * Get the manifest types as a string.
     */
    public static function getManifestTypesAsString(): string
    {
        return implode(',', [
            self::IMAGE_MANIFEST_V1->toString(),
            self::IMAGE_MANIFEST_V1_SIGNED->toString(),
            self::IMAGE_MANIFEST_V2->toString(),
            self::IMAGE_MANIFEST_V2_LIST->toString(),
            self::OCI_IMAGE_MANIFEST_V1->toString()
        ]);
    }

    /**
     * Get the media type as a string.
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Check if the media type is for an image manifest (single image).
     */
    public function isImageManifest(): bool
    {
        return $this === self::IMAGE_MANIFEST_V1
            || $this === self::IMAGE_MANIFEST_V1_SIGNED
            || $this === self::IMAGE_MANIFEST_V2
            || $this === self::OCI_IMAGE_MANIFEST_V1;
    }

    /**
     * Check if the media type is for an image manifest list (possibly multiple images).
     */
    public function isManifestList(): bool
    {
        return $this === self::IMAGE_INDEX_V1
            || $this === self::IMAGE_MANIFEST_V2_LIST;
    }

    /**
     * Check if the media type is custom.
     */
    public function isCustom(): bool
    {
        return $this === self::CUSTOM;
    }

    /**
     * Check if the media type is blob-like.
     */
    public function isBlobLike(): bool
    {
        return $this->isImageLayer()
            || $this->isImageConfig()
            || $this === self::OCTET_STREAM
            || $this === self::CUSTOM;
    }

    /**
     * Check if the media type is for an image layer.
     */
    public function isImageLayer(): bool
    {
        return $this === self::IMAGE_LAYER_V1_TAR
            || $this === self::IMAGE_LAYER_V1_TAR_GZIP
            || $this === self::IMAGE_LAYER_V1_TAR_ZSTD;
    }

    /**
     * Check if the media type is for an image config.
     */
    public function isImageConfig(): bool
    {
        return $this === self::IMAGE_CONFIG_V1
            || $this === self::CONTAINER_CONFIG_V1;
    }

    /**
     * Check if the media type is for an image root filesystem.
     */
    public function isImageRootfs(): bool
    {
        return $this === self::IMAGE_ROOTFS_DIFF_TAR_GZIP;
    }
}
