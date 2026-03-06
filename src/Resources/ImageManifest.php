<?php

namespace Cainy\Dockhand\Resources;

use Cainy\Dockhand\Enums\MediaType;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 */
readonly class ImageManifest extends ManifestResource implements Arrayable, JsonSerializable
{
    /**
     * The config field references a configuration object for a container, by digest.
     */
    public ImageConfigDescriptor $config;

    /**
     * The layer reference list is ordered starting from the base image.
     *
     * @var Collection<int, ImageLayerDescriptor>
     */
    public Collection $layers;

    /**
     * Create a new image manifest instance.
     *
     * @param string $repository
     * @param string $digest
     * @param MediaType $mediaType
     * @param int $schemaVersion
     * @param ImageConfigDescriptor $config
     * @param Collection<int, ImageLayerDescriptor> $layers
     */
    public function __construct(string $repository, string $digest, MediaType $mediaType, int $schemaVersion, ImageConfigDescriptor $config, Collection $layers)
    {
        parent::__construct($repository, $digest, $mediaType, $schemaVersion);
        $this->config = $config;
        $this->layers = $layers;
    }

    /**
     * Create a new image manifest instance.
     *
     * @param string $repository
     * @param string $digest
     * @param MediaType $mediaType
     * @param int $schemaVersion
     * @param ImageConfigDescriptor $config
     * @param Collection<int, ImageLayerDescriptor> $layers
     * @return self
     */
    public static function create(string $repository, string $digest, MediaType $mediaType, int $schemaVersion, ImageConfigDescriptor $config, Collection $layers): self
    {
        return new self($repository, $digest, $mediaType, $schemaVersion, $config, $layers);
    }

    /**
     * Parse an image manifest from an array.
     *
     * @param string $repository
     * @param string $digest
     * @param array<string, mixed> $data
     * @return self
     */
    public static function parse(string $repository, string $digest, array $data): self
    {
        if (!isset(
            $data['mediaType'],
            $data['schemaVersion'],
            $data['config'],
            $data['layers'])) {
            throw new \ParseError('Invalid image manifest data');
        }

        /** @var string $mediaTypeValue */
        $mediaTypeValue = $data['mediaType'];
        $mediaType = MediaType::from($mediaTypeValue);
        /** @var int $schemaVersion */
        $schemaVersion = $data['schemaVersion'];
        /** @var array<string, mixed> $configData */
        $configData = $data['config'];
        $config = ImageConfigDescriptor::parse($repository, $configData);
        /** @var array<int, array<string, mixed>> $layersData */
        $layersData = $data['layers'];
        $layers = collect($layersData)->map(fn(array $l) => ImageLayerDescriptor::parse($repository, $l));

        return new self($repository, $digest, $mediaType, $schemaVersion, $config, $layers);
    }

    /**
     * Return the size of the resource.
     *
     * @return int
     */
    public function getSize(): int
    {
        return $this->config->size;
    }

    /**
     * Check if this is a manifest list.
     *
     * @return bool
     */
    public function isManifestList(): bool
    {
        return false;
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
            'config' => $this->config->toArray(),
            'layers' => $this->layers->toArray(),
        ];
    }
}
