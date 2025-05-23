<?php

namespace Cainy\Dockhand\Resources;

use Cainy\Dockhand\Enums\MediaType;

readonly class ImageConfigDescriptor
{
    /**
     * The repository this image config belongs to.
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
     * Create a new ImageConfig instance.
     *
     * @param string $repository
     * @param MediaType $mediaType
     * @param int $size
     * @param string $digest
     */
    public function __construct(string $repository, MediaType $mediaType, int $size, string $digest)
    {
        $this->repository = $repository;
        $this->mediaType = $mediaType;
        $this->size = $size;
        $this->digest = $digest;
    }

    /**
     * Create a new ImageConfig instance.
     *
     * @param string $repository
     * @param MediaType $mediaType
     * @param int $size
     * @param string $digest
     * @return self
     */
    public static function create(string $repository, MediaType $mediaType, int $size, string $digest): self
    {
        return new self($repository, $mediaType, $size, $digest);
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

        $mediaType = MediaType::from($data['mediaType']);
        $size = (int)($data['size']);
        $digest = (string)($data['digest']);

        return new self($repository, $mediaType, $size, $digest);
    }
}
