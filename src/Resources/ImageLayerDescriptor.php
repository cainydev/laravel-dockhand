<?php

namespace Cainy\Dockhand\Resources;

use Cainy\Dockhand\Enums\MediaType;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

readonly class ImageLayerDescriptor implements Arrayable, JsonSerializable
{
    /**
     * The repository this layer belongs to.
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
     * Provides a list of URLs from which the content may be fetched.
     * This field is optional and uncommon.
     *
     * @var array<string>
     */
    public array $urls;

    /**
     * Create a new Layer instance.
     *
     * @param string $repository
     * @param string $digest
     * @param MediaType $mediaType
     * @param int $size
     * @param array $urls
     */
    public function __construct(string $repository, string $digest, MediaType $mediaType, int $size, array $urls = [])
    {
        $this->repository = $repository;
        $this->digest = $digest;
        $this->mediaType = $mediaType;
        $this->size = $size;
        $this->urls = $urls;
    }

    /**
     * Create a new Layer instance.
     *
     * @param string $repository
     * @param string $digest
     * @param MediaType $mediaType
     * @param int $size
     * @param array $urls
     * @return self
     */
    public static function create(string $repository, string $digest, MediaType $mediaType, int $size, array $urls = []): self
    {
        return new self($repository, $digest, $mediaType, $size, $urls);
    }

    /**
     * Parse a Layer instance from an array.
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

        $mediaType = MediaType::from($data['mediaType']);
        $size = (int)($data['size']);
        $digest = (string)($data['digest']);
        $urls = (array)($data['urls'] ?? []);

        return new self($repository, $digest, $mediaType, $size, $urls);
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
            'repository' => $this->repository,
            'digest' => $this->digest,
            'mediaType' => $this->mediaType,
            'size' => $this->size,
            'urls' => $this->urls,
        ];
    }
}
