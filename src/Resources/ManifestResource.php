<?php

namespace Cainy\Dockhand\Resources;

use Cainy\Dockhand\Enums\MediaType;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

abstract readonly class ManifestResource implements Arrayable, JsonSerializable
{
    /**
     * The repository this manifest belongs to.
     */
    public string $repository;

    /**
     * The digest of this manifest itself.
     */
    public string $digest;

    /**
     * The media type of this manifest.
     */
    public MediaType $mediaType;

    /**
     * The schema version of the manifest.
     */
    public int $schemaVersion;

    /**
     * Create a new manifest resource instance.
     *
     * @param string $repository
     * @param string $digest
     * @param MediaType $mediaType
     * @param int $schemaVersion
     */
    public function __construct(string $repository, string $digest, MediaType $mediaType, int $schemaVersion)
    {
        $this->repository = $repository;
        $this->digest = $digest;
        $this->mediaType = $mediaType;
        $this->schemaVersion = $schemaVersion;
    }


    /**
     * Return the size of the resource.
     *
     * @return int
     */
    abstract public function getSize(): int;

    /**
     * Check if this is a manifest list.
     *
     * @return bool
     */
    abstract public function isManifestList(): bool;

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
            'schemaVersion' => $this->schemaVersion,
        ];
    }
}
