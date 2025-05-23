<?php

namespace Cainy\Dockhand\Resources;

use Cainy\Dockhand\Enums\MediaType;

readonly class ManifestListEntry
{
    /**
     * The repository this manifest list entry belongs to.
     */
    public string $repository;

    /**
     * The media type of this manifest list entry.
     */
    public MediaType $mediaType;

    /**
     * The size of this manifest list entry.
     */
    public int $size;

    /**
     * The digest of this manifest list entry.
     */
    public string $digest;

    /**
     * The platform of this manifest list entry.
     */
    public Platform $platform;

    /**
     * Create a new ManifestListEntry instance.
     *
     * @param string $repository
     * @param MediaType $mediaType
     * @param int $size
     * @param string $digest
     * @param Platform $platform
     */
    public function __construct(string $repository, MediaType $mediaType, int $size, string $digest, Platform $platform)
    {
        $this->repository = $repository;
        $this->mediaType = $mediaType;
        $this->size = $size;
        $this->digest = $digest;
        $this->platform = $platform;
    }

    /**
     * Parse a manifest list entry from an array.
     *
     * @param string $repository
     * @param array $data
     * @return self
     */
    public static function parse(string $repository, array $data): self
    {
        $mediaType = MediaType::from($data['mediaType']);
        $size = (int)$data['size'];
        $digest = (string)$data['digest'];
        $platform = Platform::parse($data['platform']);

        return new self($repository, $mediaType, $size, $digest, $platform);
    }

    /**
     * Create a new ManifestListEntry instance.
     *
     * @param string $repository
     * @param MediaType $mediaType
     * @param int $size
     * @param string $digest
     * @param Platform $platform
     * @return self
     */
    public static function create(string $repository, MediaType $mediaType, int $size, string $digest, Platform $platform): self
    {
        return new self($repository, $mediaType, $size, $digest, $platform);
    }
}
