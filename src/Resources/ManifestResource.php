<?php

namespace Cainy\Dockhand\Resources;

namespace Cainy\Dockhand\Resources;

use Cainy\Dockhand\Enums\Platform;
use Cainy\Dockhand\Facades\Dockhand;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;

readonly class ManifestResource
{
    /**
     * The name of the repository.
     */
    public string $repository;

    /**
     * The reference used to fetch this manifest (tag or digest).
     */
    public string $reference;

    /**
     * The digest of this manifest itself.
     * This might be different from $reference if $reference was a tag.
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
     * The config object (only if it's an Image Manifest).
     * @property string $mediaType
     * @property int $size
     * @property string $digest
     */
    public ?array $config;

    /**
     * The layers (only if it's an Image Manifest).
     * Each item:
     * @property string $mediaType
     * @property int $size
     * @property string $digest
     * @property ?array $urls
     */
    public ?array $layers;

    /**
     * The list of manifest entries (only if it's a Manifest List).
     * Each item:
     * @property string $mediaType
     * @property int $size
     * @property string $digest
     * @property array $platform
     * @property string $architecture
     * @property string $os
     * @property ?string $os.version
     * @property ?array $os.features
     * @property ?string $variant
     * @property ?array $features
     */
    public ?array $manifests;

    /**
     * Create a new manifest resource instance.
     * The $data parameter is the raw JSON decoded array from the registry.
     * The $digest is the actual digest of the manifest content.
     * @throws Exception
     */
    public function __construct(
        string $repository,
        string $reference,
        string $digest,
        array  $data
    )
    {
        $this->repository = $repository;
        $this->reference = $reference;
        $this->digest = $digest;

        // It's crucial to get mediaType from the $data (manifest content) itself,
        // as the Content-Type header might sometimes be less specific (e.g., OCI manifest)
        $mediaTypeValue = Arr::get($data, 'mediaType');
        if (!$mediaTypeValue) {
            throw new Exception("Manifest data is missing 'mediaType' field.");
        }

        $this->mediaType = MediaType::tryFrom($mediaTypeValue)
            ?? throw new Exception("Unsupported media type: {$mediaTypeValue}");

        $this->schemaVersion = (int)Arr::get($data, 'schemaVersion', 2);

        if ($this->isImageManifest()) {
            $this->config = Arr::get($data, 'config');
            $this->layers = Arr::get($data, 'layers');
            $this->manifests = null;

            if (!$this->config || !is_array($this->config)) {
                throw new Exception("Image manifest is missing or has invalid 'config' data.");
            }

            if (!$this->layers || !is_array($this->layers)) {
                throw new Exception("Image manifest is missing or has invalid 'layers' data.");
            }
        } elseif ($this->isImageManifestList()) {
            $this->manifests = Arr::get($data, 'manifests');
            $this->config = null;
            $this->layers = null;

            if (!$this->manifests || !is_array($this->manifests)) {
                throw new Exception("Manifest list is missing or has invalid 'manifests' data.");
            }
        } else {
            throw new Exception("Cannot determine manifest type from mediaType: {$this->mediaType->value}");
        }
    }

    public function isImageManifest(): bool
    {
        return $this->mediaType->isImageManifest();
    }

    public function isImageManifestList(): bool
    {
        return $this->mediaType->isImageManifestList();
    }

    /**
     * Fetch a manifest from the registry by repository and reference (tag or digest).
     *
     * @throws ConnectionException
     * @throws Exception
     */
    public static function fetch(string $repository, string $reference): ?ManifestResource
    {
        return Dockhand::getManifest($repository, $reference);
    }

    /**
     * Get the config digest if this is an image manifest.
     */
    public function getConfigDigest(): ?string
    {
        return $this->isImageManifest() ? Arr::get($this->config, 'digest') : null;
    }

    /**
     * Get a specific layer by its index if this is an image manifest.
     */
    public function getLayer(int $index): ?array
    {
        return $this->isImageManifest() ? Arr::get($this->layers, $index) : null;
    }

    /**
     * Get a specific manifest entry from a manifest list by its index.
     */
    public function getManifestListEntry(int $index): ?array
    {
        return $this->isImageManifestList() ? Arr::get($this->manifests, $index) : null;
    }

    /**
     * Find a manifest entry in a manifest list by platform.
     *
     * @param Platform $platform The platform to match against.
     * @return array|null The manifest entry if found, null otherwise.
     */
    public function findManifestListEntryByPlatform(Platform $platform): ?array
    {
        if (!$this->isImageManifestList() || empty($this->manifests)) {
            return null;
        }

        foreach ($this->manifests as $entry) {
            if (!is_array($entry)
                || !Arr::has($entry, 'platform')
                || !is_array(Arr::get($entry, 'platform'))) {
                continue;
            }

            $entryPlatformData = Arr::get($entry, 'platform');

            $entryOs = Arr::get($entryPlatformData, 'os');
            $entryArchitecture = Arr::get($entryPlatformData, 'architecture');

            if ($entryOs === null || $entryArchitecture === null) {
                continue;
            }

            $entryPlatformEnum = Platform::fromOsArch($entryOs, $entryArchitecture);

            if ($entryPlatformEnum === $platform) {
                return $entry;
            }
        }

        return null;
    }
}
