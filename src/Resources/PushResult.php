<?php

namespace Cainy\Dockhand\Resources;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 */
readonly class PushResult implements Arrayable, JsonSerializable
{
    /**
     * The location of the pushed resource (Location header).
     */
    public string $location;

    /**
     * The digest of the pushed resource (Docker-Content-Digest header).
     */
    public string $digest;

    public function __construct(string $location, string $digest)
    {
        $this->location = $location;
        $this->digest = $digest;
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
            'location' => $this->location,
            'digest' => $this->digest,
        ];
    }
}
