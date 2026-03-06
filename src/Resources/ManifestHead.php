<?php

namespace Cainy\Dockhand\Resources;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 */
readonly class ManifestHead implements Arrayable, JsonSerializable
{
    /**
     * The digest of the manifest (Docker-Content-Digest header).
     */
    public string $digest;

    /**
     * The content length of the manifest in bytes.
     */
    public int $contentLength;

    /**
     * The media type of the manifest (Content-Type header).
     */
    public ?string $mediaType;

    public function __construct(string $digest, int $contentLength, ?string $mediaType)
    {
        $this->digest = $digest;
        $this->contentLength = $contentLength;
        $this->mediaType = $mediaType;
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
            'digest' => $this->digest,
            'contentLength' => $this->contentLength,
            'mediaType' => $this->mediaType,
        ];
    }
}
