<?php

namespace Cainy\Dockhand\Resources;

use Cainy\Dockhand\Enums\MediaType;
use Cainy\Dockhand\Enums\Platform;
use Illuminate\Support\Collection;

readonly class ManifestList extends ManifestResource
{
    /**
     * The manifests field contains a list of manifests for specific platforms.
     *
     * @var Collection<ManifestListEntry>
     */
    public Collection $manifests;

    /**
     * Create a new manifest list instance.
     *
     * @param string $repository
     * @param MediaType $mediaType
     * @param int $schemaVersion
     * @param string $digest
     * @param Collection<ManifestListEntry> $manifests
     */
    public function __construct(string $repository, MediaType $mediaType, int $schemaVersion, string $digest, Collection $manifests)
    {
        parent::__construct($repository, $mediaType, $schemaVersion, $digest);
        $this->manifests = $manifests;
    }

    /**
     * Create a new manifest list instance.
     *
     * @param string $repository
     * @param MediaType $mediaType
     * @param int $schemaVersion
     * @param string $digest
     * @param Collection $manifests
     * @return self
     */
    public static function create(string $repository, MediaType $mediaType, int $schemaVersion, string $digest, Collection $manifests): self
    {
        return new self($repository, $mediaType, $schemaVersion, $digest, $manifests);
    }

    /**
     * Parse a manifest list from an array.
     *
     * @param string $repository
     * @param array $data
     * @return self
     */
    public static function parse(string $repository, array $data): self
    {
        if (!isset($data['mediaType'], $data['schemaVersion'], $data['digest'], $data['manifests'])) {
            throw new \ParseError('Invalid manifest list data');
        }

        $mediaType = MediaType::from($data['mediaType']);
        $schemaVersion = (int)$data['schemaVersion'];
        $digest = (string)$data['digest'];
        $manifests = collect($data['manifests'])->map(fn($m) => ManifestListEntry::parse($repository, $m));

        return new self($repository, $mediaType, $schemaVersion, $digest, $manifests);
    }

    /**
     * Find a manifest entry in a manifest list by platform.
     *
     * @param Platform $platform The platform to match against.
     * @return ManifestListEntry|null The manifest entry if found, null otherwise.
     */
    public function findManifestListEntryByPlatform(Platform $platform): ?ManifestListEntry
    {
        return $this->manifests->first(fn($m) => $m->platform === $platform);
    }

    /**
     * Check if this is a manifest list.
     *
     * @return bool
     */
    public function isManifestList(): bool
    {
        return true;
    }
}
