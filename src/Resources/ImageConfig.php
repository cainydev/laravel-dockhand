<?php

namespace Cainy\Dockhand\Resources;

use Cainy\Dockhand\Enums\MediaType;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

readonly class ImageConfig implements Arrayable, JsonSerializable
{
    /**
     * The repository this image config belongs to.
     */
    public string $repository;

    /**
     * The digest of this image config.
     */
    public string $digest;

    /**
     * The MIME type of this image config.
     */
    public MediaType $mediaType;

    /**
     * The platform of the image.
     */
    public Platform $platform;

    /**
     * The created date of the image.
     */
    public Carbon $created;

    /**
     * Create a new ImageConfig instance.
     *
     * @param string $repository
     * @param string $digest
     * @param MediaType $mediaType
     * @param Platform $platform
     * @param Carbon $created
     */
    public function __construct(string $repository, string $digest, MediaType $mediaType, Platform $platform, Carbon $created)
    {
        $this->repository = $repository;
        $this->digest = $digest;
        $this->mediaType = $mediaType;
        $this->platform = $platform;
        $this->created = $created;
    }

    /**
     * Create a new ImageConfig instance.
     *
     * @param string $repository
     * @param string $digest
     * @param MediaType $mediaType
     * @param Platform $platform
     * @param Carbon $created
     * @return self
     */
    public static function create(string $repository, string $digest, MediaType $mediaType, Platform $platform, Carbon $created): self
    {
        return new self($repository, $digest, $mediaType, $platform, $created);
    }

    /**
     * Parse an image config from an array.
     *
     * @param string $repository
     * @param string $digest
     * @param MediaType $mediaType
     * @param array $data
     * @return self
     */
    public static function parse(string $repository, string $digest, MediaType $mediaType, array $data): self
    {
        if (!isset($data['os'], $data['architecture'], $data['created'])) {
            throw new \ParseError('Invalid image config data');
        }

        $platform = Platform::parse($data);
        $created = Carbon::parse($data['created']);

        return new self($repository, $digest, $mediaType, $platform, $created);
    }

    /**
     * Specify data which should be serialized to JSON
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
            'platform' => $this->platform->toArray(),
            'created' => $this->created->toIso8601String(),
        ];
    }
}
