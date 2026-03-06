<?php

namespace Cainy\Dockhand\Contracts;

interface ZotCapabilities
{
    /**
     * Discover available Zot extensions.
     *
     * @return array<int, mixed>
     */
    public function discoverExtensions(): array;

    /**
     * Clear the cached extension discovery results.
     */
    public function clearExtensionCache(): void;

    /**
     * Execute a GraphQL search query against the Zot search extension.
     *
     * @param  string  $query  The GraphQL query string.
     * @param  array<string, mixed>  $variables  Variables to pass to the GraphQL query.
     * @return array<string, mixed>
     */
    public function search(string $query, array $variables = []): array;

    /**
     * Search for CVEs affecting a specific image.
     *
     * @param  string  $repository  The repository name.
     * @param  string  $reference  The tag or digest.
     * @return array<string, mixed>
     */
    public function searchCVE(string $repository, string $reference): array;

    /**
     * Star a repository.
     */
    public function starRepository(string $repository): bool;

    /**
     * Unstar a repository.
     */
    public function unstarRepository(string $repository): bool;

    /**
     * Bookmark a repository.
     */
    public function bookmarkRepository(string $repository): bool;

    /**
     * Unbookmark a repository.
     */
    public function unbookmarkRepository(string $repository): bool;
}
