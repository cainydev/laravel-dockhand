<?php

namespace Cainy\Dockhand\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Client\Response;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 */
readonly class BlobUpload implements Arrayable, JsonSerializable
{
    /**
     * The repository this upload belongs to.
     */
    public string $repository;

    /**
     * The unique identifier for this upload (Docker-Upload-UUID header).
     */
    public string $uuid;

    /**
     * The location URL for subsequent requests (Location header).
     */
    public string $location;

    /**
     * The current byte offset of the upload (parsed from Range header).
     */
    public int $offset;

    public function __construct(string $repository, string $uuid, string $location, int $offset)
    {
        $this->repository = $repository;
        $this->uuid = $uuid;
        $this->location = $location;
        $this->offset = $offset;
    }

    /**
     * Create a BlobUpload from a registry response.
     *
     * @param  string  $repository  The repository name.
     * @param  Response  $response  The HTTP response from the registry.
     */
    public static function fromResponse(string $repository, Response $response): self
    {
        $uuid = $response->header('Docker-Upload-UUID');
        $location = $response->header('Location');
        $range = $response->header('Range');

        $offset = 0;
        if ($range && preg_match('/^(\d+)-(\d+)$/', $range, $matches)) {
            $offset = (int) $matches[2];
        }

        return new self($repository, $uuid, $location, $offset);
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
            'uuid' => $this->uuid,
            'location' => $this->location,
            'offset' => $this->offset,
        ];
    }
}
