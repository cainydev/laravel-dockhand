<?php

namespace Cainy\Dockhand\Actions;

use Arr;
use Cainy\Dockhand\Resources\Repository;
use Cainy\Dockhand\Resources\Scope;
use Cainy\Dockhand\Resources\Tag;
use Cainy\Dockhand\Resources\Token;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use function collect;
use function config;
use function now;

trait ManagesRepositories
{
    /**
     * Get a list of all the repositories in the registry.
     *
     * @return Collection<Repository>
     * @throws ConnectionException
     */
    public function getRepositories(): Collection
    {
        $response = $this->request()
            ->withToken(\Cainy\Dockhand\Facades\Token::withScope(\Cainy\Dockhand\Facades\Scope::catalog())
                ->issuedBy(config('dockhand.authority_name'))
                ->permittedFor(config('dockhand.registry_name'))
                ->expiresAt(now()->addMinutes(2))
                ->toString())
            ->get('/_catalog');

        return collect(Arr::map($response['repositories'], fn($repository) => new Repository($repository)));
    }

    /**
     * Get a repository by name.
     *
     * @param string $repository The full repository name (e.g., "john/busybox").
     * @return Repository
     */
    public function getRepository(string $repository): Repository
    {
        return new Repository($repository);
    }

    /**
     * Get a list of all the tags in the repository.
     *
     * @param string|Repository $repository The full repository name (e.g., "john/busybox").
     * @return Collection<Tag>
     * @throws ConnectionException
     */
    public function getTagsOfRepository(string|Repository $repository): Collection
    {
        if ($repository instanceof Repository) {
            $repository = $repository->name;
        }

        return collect($this->request()
            ->withToken(Token::withScope(Scope::readRepository($repository))
                ->issuedBy(config('dockhand.authority_name'))
                ->permittedFor(config('dockhand.registry_name'))
                ->expiresAt(now()->addMinutes(2))
                ->toString())
            ->get("/$repository/tags/list")['tags'])
            ->map(fn($tag) => new Tag($repository, $tag));
    }
}
