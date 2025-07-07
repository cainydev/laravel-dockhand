<?php

namespace Cainy\Dockhand\Resources;

use Cainy\Dockhand\Enums\MediaType;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use JsonSerializable;

readonly class ManifestList extends ManifestResource implements Arrayable, JsonSerializable
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
     * @param string $digest
     * @param MediaType $mediaType
     * @param int $schemaVersion
     * @param Collection<ManifestListEntry> $manifests
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
     * @param Collection $manifests
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
     * @param array $data
     * @return self
     */
    public static function parse(string $repository, string $digest, array $data): self
    {
        if (!isset($data['mediaType'], $data['schemaVersion'], $data['manifests'])) {
            throw new \ParseError('Invalid manifest list data');
        }

        $mediaType = MediaType::from($data['mediaType']);
        $schemaVersion = (int)$data['schemaVersion'];
        $manifests = collect($data['manifests'])->map(fn($m) => ManifestListEntry::parse($repository, $m));

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
        return $this->manifests->first(fn($m) => $m->platform === $platform);
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
            parent::toArray(),
            'manifests' => $this->manifests->toArray(),
        ];
    }
}
