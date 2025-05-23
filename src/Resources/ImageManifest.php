<?php

namespace Cainy\Dockhand\Resources;

use Cainy\Dockhand\Enums\MediaType;
use Illuminate\Support\Collection;

readonly class ImageManifest extends ManifestResource
{
    /**
     * The config field references a configuration object for a container, by digest.
     */
    public ImageConfigDescriptor $config;

    /**
     * The layer reference list is ordered starting from the base image.
     *
     * @var Collection<ImageLayerDescriptor>
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
     * @param Collection<ImageLayerDescriptor> $layers
     */
    public function __construct(string $repository, string $digest, MediaType $mediaType, int $schemaVersion, ImageConfigDescriptor $config, Collection $layers)
    {
        parent::__construct($repository, $mediaType, $schemaVersion, $digest);
        $this->config = $config;
        $this->layers = $layers;
    }

    /**
     * Create a new image manifest instance.
     *
     * @param string $repository
     * @param MediaType $mediaType
     * @param int $schemaVersion
     * @param string $digest
     * @param ImageConfigDescriptor $config
     * @param Collection<ImageLayerDescriptor> $layers
     * @return self
     */
    public static function create(string $repository, MediaType $mediaType, int $schemaVersion, string $digest, ImageConfigDescriptor $config, Collection $layers): self
    {
        return new self($repository, $mediaType, $schemaVersion, $digest, $config, $layers);
    }

    /**
     * Parse an image manifest from an array.
     *
     * @param string $repository
     * @param array $data
     * @return self
     */
    public static function parse(string $repository, array $data): self
    {
        if (!isset(
            $data['mediaType'],
            $data['schemaVersion'],
            $data['config'],
            $data['layers'])) {
            throw new \ParseError('Invalid image manifest data');
        }

        $mediaType = MediaType::from($data['mediaType']);
        $schemaVersion = (int)$data['schemaVersion'];
        $digest = (string)$data['digest'];
        $config = ImageConfigDescriptor::parse($repository, $data['config']);
        $layers = collect($data['layers'])->map(fn($l) => ImageLayerDescriptor::parse($repository, $l));

        return new self($repository, $mediaType, $schemaVersion, $digest, $config, $layers);
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
}
