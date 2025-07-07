<?php

namespace Cainy\Dockhand\Resources;

use Cainy\Dockhand\Enums\MediaType;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

readonly class ImageConfigDescriptor implements Arrayable, JsonSerializable
{
    /**
     * The repository this image config belongs to.
     */
    public string $repository;

    /**
     * The digest of the content, as defined by the Registry V2 HTTP API Specification.
     *
     * @var string
     */
    public string $digest;

    /**
     * The MIME type of the referenced object.
     *
     * @var MediaType
     */
    public MediaType $mediaType;

    /**
     * The size in bytes of the object.
     *
     * @var int
     */
    public int $size;

    /**
     * Create a new ImageConfig instance.
     *
     * @param string $repository
     * @param string $digest
     * @param MediaType $mediaType
     * @param int $size
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
     *
     * @param string $repository
     * @param string $digest
     * @param MediaType $mediaType
     * @param int $size
     * @return self
     */
    public static function create(string $repository, string $digest, MediaType $mediaType, int $size): self
    {
        return new self($repository, $digest, $mediaType, $size);
    }

    /**
     * Parse a ImageConfig instance from an array.
     *
     * @param string $repository
     * @param array $data
     * @return self
     */
    public static function parse(string $repository, array $data): self
    {
        if (!isset($data['mediaType'], $data['size'], $data['digest'])) {
            throw new \ParseError('Invalid layer data');
        }

        $digest = (string)($data['digest']);
        $mediaType = MediaType::from($data['mediaType']);
        $size = (int)($data['size']);

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
