<?php

namespace Cainy\Dockhand\Actions;

use Cainy\Dockhand\Exceptions\PaginationNumberInvalidException;
use Cainy\Dockhand\Resources\PaginatedResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use function collect;

/**
 * @phpstan-require-extends \Cainy\Dockhand\Drivers\AbstractRegistryDriver
 */
trait ManagesRepositories
{
    /**
     * Get a list of all the repositories in the registry.
     *
     * @param int|null $limit Maximum number of results per page. Null for no pagination.
     * @param string|null $last The last repository name from a previous paginated response.
     * @return Collection<int, string>|PaginatedResult Collection when not paginating, PaginatedResult when $limit is set.
     * @throws PaginationNumberInvalidException If $limit is less than 1.
     * @throws ConnectionException
     */
    public function getRepositories(?int $limit = null, ?string $last = null): Collection|PaginatedResult
    {
        if ($limit !== null && $limit < 1) {
            throw new PaginationNumberInvalidException("Pagination limit must be at least 1, got {$limit}.");
        }

        $url = '/_catalog';
        $query = [];

        if ($limit !== null) {
            $query['n'] = $limit;
        }

        if ($last !== null) {
            $query['last'] = $last;
        }

        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $response = $this->authenticatedRequest('catalog')
            ->get($url);

        /** @var array<int, string> $repositories */
        $repositories = $response['repositories'];
        $items = collect($repositories);

        if ($limit === null) {
            return $items;
        }

        return new PaginatedResult(
            $items,
            $this->parseLinkHeader($response->header('Link')),
        );
    }

    /**
     * Get a list of all the tags in the repository.
     *
     * @param string $repository The full repository name (e.g., "john/busybox").
     * @param int|null $limit Maximum number of results per page. Null for no pagination.
     * @param string|null $last The last tag name from a previous paginated response.
     * @return Collection<int, string>|PaginatedResult Collection when not paginating, PaginatedResult when $limit is set.
     * @throws PaginationNumberInvalidException If $limit is less than 1.
     * @throws ConnectionException
     */
    public function getTagsOfRepository(string $repository, ?int $limit = null, ?string $last = null): Collection|PaginatedResult
    {
        if ($limit !== null && $limit < 1) {
            throw new PaginationNumberInvalidException("Pagination limit must be at least 1, got {$limit}.");
        }

        $url = "/{$repository}/tags/list";
        $query = [];

        if ($limit !== null) {
            $query['n'] = $limit;
        }

        if ($last !== null) {
            $query['last'] = $last;
        }

        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $response = $this->authenticatedRequest('read', $repository)
            ->get($url);

        /** @var array<int, string> $tags */
        $tags = $response['tags'];
        $items = collect($tags);

        if ($limit === null) {
            return $items;
        }

        return new PaginatedResult(
            $items,
            $this->parseLinkHeader($response->header('Link')),
        );
    }

    /**
     * Parse an RFC5988 Link header to extract the "next" URL.
     *
     * @param string|null $header The Link header value.
     * @return string|null The next URL, or null if not present.
     */
    private function parseLinkHeader(?string $header): ?string
    {
        if (empty($header)) {
            return null;
        }

        if (preg_match('/<([^>]+)>;\s*rel="next"/', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
