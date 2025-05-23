<?php

namespace Cainy\Dockhand\Resources;

use Cainy\Dockhand\Enums\MediaType;

abstract readonly class ManifestResource
{
    /**
     * The repository this manifest belongs to.
     */
    public string $repository;

    /**
     * The media type of this manifest.
     */
    public MediaType $mediaType;

    /**
     * The schema version of the manifest.
     */
    public int $schemaVersion;

    /**
     * The digest of this manifest itself.
     */
    public string $digest;

    /**
     * Create a new manifest resource instance.
     *
     * @param string $repository
     * @param MediaType $mediaType
     * @param int $schemaVersion
     * @param string $digest
     */
    public function __construct(string $repository, MediaType $mediaType, int $schemaVersion, string $digest)
    {
        $this->repository = $repository;
        $this->mediaType = $mediaType;
        $this->schemaVersion = $schemaVersion;
        $this->digest = $digest;
    }

    /**
     * Check if this is a manifest list.
     *
     * @return bool
     */
    abstract public function isManifestList(): bool;
}
