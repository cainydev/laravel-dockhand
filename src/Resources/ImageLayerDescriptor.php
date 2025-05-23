<?php

namespace Cainy\Dockhand\Resources;

use Cainy\Dockhand\Enums\MediaType;

readonly class ImageLayerDescriptor
{
    /**
     * The repository this layer belongs to.
     */
    public string $repository;

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
     * The digest of the content, as defined by the Registry V2 HTTP API Specification.
     *
     * @var string
     */
    public string $digest;

    /**
     * Provides a list of URLs from which the content may be fetched.
     * This field is optional and uncommon.
     *
     * @var array
     */
    public array $urls;

    /**
     * Create a new Layer instance.
     *
     * @param string $repository
     * @param MediaType $mediaType
     * @param int $size
     * @param string $digest
     * @param array $urls
     */
    public function __construct(string $repository, MediaType $mediaType, int $size, string $digest, array $urls = [])
    {
        $this->repository = $repository;
        $this->mediaType = $mediaType;
        $this->size = $size;
        $this->digest = $digest;
        $this->urls = $urls;
    }

    /**
     * Create a new Layer instance.
     *
     * @param string $repository
     * @param MediaType $mediaType
     * @param int $size
     * @param string $digest
     * @param array $urls
     * @return self
     */
    public static function create(string $repository, MediaType $mediaType, int $size, string $digest, array $urls = []): self
    {
        return new self($repository, $mediaType, $size, $digest, $urls);
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

        return new self($repository, $mediaType, $size, $digest, $urls);
    }
}
