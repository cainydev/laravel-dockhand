<?php

namespace Cainy\Dockhand\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 */
readonly class PaginatedResult implements Arrayable, JsonSerializable
{
    /**
     * The items in this page of results.
     *
     * @var Collection<int, string>
     */
    public Collection $items;

    /**
     * The URL for the next page of results, or null if this is the last page.
     */
    public ?string $nextUrl;

    /**
     * @param  Collection<int, string>  $items
     */
    public function __construct(Collection $items, ?string $nextUrl)
    {
        $this->items = $items;
        $this->nextUrl = $nextUrl;
    }

    /**
     * Check if there are more results available.
     */
    public function hasMore(): bool
    {
        return $this->nextUrl !== null;
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
            'items' => $this->items->toArray(),
            'nextUrl' => $this->nextUrl,
        ];
    }
}
