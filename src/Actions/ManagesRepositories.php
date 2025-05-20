<?php

namespace Cainy\Dockhand\Actions;

use Cainy\Dockhand\Facades\Dockhand;
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

        return collect($response['repositories']);
    }

    /**
     * Get a repository by name.
     */
    public function getRepository(string $repository): Repository
    {
        return new Repository($repository);
    }

    /**
     * Get a list of all the tags in the repository.
     *
     * @throws ConnectionException
     */
    public function getTagsOfRepository(string $repository): Collection
    {
        return collect(Dockhand::request()
            ->withToken(Token::withScope(Scope::readRepository($repository))
                ->issuedBy(config('dockhand.authority_name'))
                ->permittedFor(config('dockhand.registry_name'))
                ->expiresAt(now()->addMinutes(2))
                ->toString())
            ->get("/$repository/tags/list")['tags'])
            ->map(fn($tag) => new Tag($repository, $tag));
    }
}
