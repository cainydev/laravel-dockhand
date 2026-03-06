<?php

namespace Cainy\Dockhand\Resources;

use Cainy\Dockhand\Enums\MediaType;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 */
readonly class ImageConfigDescriptor implements Arrayable, JsonSerializable
{
    /**
     * The repository this image config belongs to.
     */
    public string $repository;

    /**
     * The digest of the content, as defined by the Registry V2 HTTP API Specification.
     */
    public string $digest;

    /**
     * The MIME type of the referenced object.
     */
    public MediaType $mediaType;

    /**
     * The size in bytes of the object.
     */
    public int $size;

    /**
     * Create a new ImageConfig instance.
     */
    public function __construct(string $repository, string $digest, MediaType $mediaType, int $size)
    {
        $this->repository = $repository;
        $this->digest = $digest;
        $this->mediaType = $mediaType;
        $this->size = $size;
    }

    /**
     * Create a new ImageConfig instance.
     */
    public static function create(string $repository, string $digest, MediaType $mediaType, int $size): self
    {
        return new self($repository, $digest, $mediaType, $size);
    }

    /**
     * Parse a ImageConfig instance from an array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function parse(string $repository, array $data): self
    {
        if (! isset($data['mediaType'], $data['size'], $data['digest'])) {
            throw new \ParseError('Invalid layer data');
        }

        /** @var string $digest */
        $digest = $data['digest'];
        /** @var string $mediaTypeValue */
        $mediaTypeValue = $data['mediaType'];
        $mediaType = MediaType::from($mediaTypeValue);
        /** @var int $size */
        $size = $data['size'];

        return new self($repository, $digest, $mediaType, $size);
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
            'size' => $this->size,
        ];
    }
}
