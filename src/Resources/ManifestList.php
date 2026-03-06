<?php

namespace Cainy\Dockhand\Resources;

use Cainy\Dockhand\Enums\MediaType;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 */
readonly class ManifestList extends ManifestResource implements Arrayable, JsonSerializable
{
    /**
     * The manifests field contains a list of manifests for specific platforms.
     *
     * @var Collection<int, ManifestListEntry>
     */
    public Collection $manifests;

    /**
     * Create a new manifest list instance.
     *
     * @param string $repository
     * @param string $digest
     * @param MediaType $mediaType
     * @param int $schemaVersion
     * @param Collection<int, ManifestListEntry> $manifests
     */
    public function __construct(string $repository, string $digest, MediaType $mediaType, int $schemaVersion, Collection $manifests)
    {
        parent::__construct($repository, $digest, $mediaType, $schemaVersion);
        $this->manifests = $manifests;
    }

    /**
     * Create a new manifest list instance.
     *
     * @param string $repository
     * @param string $digest
     * @param MediaType $mediaType
     * @param int $schemaVersion
     * @param Collection<int, ManifestListEntry> $manifests
     * @return self
     */
    public static function create(string $repository, string $digest, MediaType $mediaType, int $schemaVersion, Collection $manifests): self
    {
        return new self($repository, $digest, $mediaType, $schemaVersion, $manifests);
    }

    /**
     * Parse a manifest list from an array.
     *
     * @param string $repository
     * @param string $digest
     * @param array<string, mixed> $data
     * @return self
     */
    public static function parse(string $repository, string $digest, array $data): self
    {
        if (!isset($data['mediaType'], $data['schemaVersion'], $data['manifests'])) {
            throw new \ParseError('Invalid manifest list data');
        }

        /** @var string $mediaTypeValue */
        $mediaTypeValue = $data['mediaType'];
        $mediaType = MediaType::from($mediaTypeValue);
        /** @var int $schemaVersion */
        $schemaVersion = $data['schemaVersion'];
        /** @var array<int, array<string, mixed>> $manifestsData */
        $manifestsData = $data['manifests'];
        $manifests = collect($manifestsData)->map(fn(array $m) => ManifestListEntry::parse($repository, $m));

        return new self($repository, $digest, $mediaType, $schemaVersion, $manifests);
    }

    /**
     * Find a manifest entry in a manifest list by platform.
     *
     * @param Platform $platform The platform to match against.
     * @return ManifestListEntry|null The manifest entry if found, null otherwise.
     */
    public function findManifestListEntryByPlatform(Platform $platform): ?ManifestListEntry
    {
        return $this->manifests->first(fn(ManifestListEntry $m) => $m->platform === $platform);
    }

    /**
     * Return the size of the resource.
     *
     * @return int
     */
    public function getSize(): int
    {
        return $this->manifests->sum(fn (ManifestListEntry $entry) => $entry->size);
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
            ...parent::toArray(),
            'manifests' => $this->manifests->toArray(),
        ];
    }
}
