<?php

namespace Cainy\Dockhand\Resources;

use Cainy\Dockhand\Enums\MediaType;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

readonly class ManifestListEntry implements Arrayable, JsonSerializable
{
    /**
     * The repository this manifest list entry belongs to.
     */
    public string $repository;

    /**
     * The digest of this manifest list entry.
     */
    public string $digest;

    /**
     * The media type of this manifest list entry.
     */
    public MediaType $mediaType;

    /**
     * The size of this manifest list entry.
     */
    public int $size;


    /**
     * The platform of this manifest list entry.
     */
    public Platform $platform;

    /**
     * Create a new ManifestListEntry instance.
     *
     * @param string $repository
     * @param string $digest
     * @param MediaType $mediaType
     * @param int $size
     * @param Platform $platform
     */
    public function __construct(string $repository, string $digest, MediaType $mediaType, int $size, Platform $platform)
    {
        $this->repository = $repository;
        $this->digest = $digest;
        $this->mediaType = $mediaType;
        $this->size = $size;
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
        $digest = (string)$data['digest'];
        $mediaType = MediaType::from($data['mediaType']);
        $size = (int)$data['size'];
        $platform = Platform::parse($data['platform']);

        return new self($repository, $digest, $mediaType, $size, $platform);
    }

    /**
     * Create a new ManifestListEntry instance.
     *
     * @param string $repository
     * @param string $digest
     * @param MediaType $mediaType
     * @param int $size
     * @param Platform $platform
     * @return self
     */
    public static function create(string $repository, string $digest, MediaType $mediaType, int $size, Platform $platform): self
    {
        return new self($repository, $digest, $mediaType, $size, $platform);
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'repository' => $this->repository,
            'digest' => $this->digest,
            'mediaType' => $this->mediaType->toString(),
            'size' => $this->size,
            'platform' => $this->platform->toArray(),
        ];
    }
}
