<?php

namespace Cainy\Dockhand\Actions;

use Cainy\Dockhand\Helpers\Scope;
use Cainy\Dockhand\Helpers\Token;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use function collect;
use function now;

trait ManagesRepositories
{
    /**
     * Get a list of all the repositories in the registry.
     *
     * @return Collection<string>
     * @throws ConnectionException
     */
    public function getRepositories(): Collection
    {
        $response = $this->request()
            ->withToken(\Cainy\Dockhand\Facades\Token::withScope(\Cainy\Dockhand\Facades\Scope::catalog())
                ->issuedBy($this->authorityName)
                ->permittedFor($this->registryName)
                ->expiresAt(now()->addMinutes(2))
                ->toString())
            ->get('/_catalog');

        return collect($response['repositories']);
    }

    /**
     * Get a list of all the tags in the repository.
     *
     * @param string $repository The full repository name (e.g., "john/busybox").
     * @return Collection<string>
     * @throws ConnectionException
     */
    public function getTagsOfRepository(string $repository): Collection
    {
        return collect($this->request()
            ->withToken(Token::withScope(Scope::readRepository($repository))
                ->issuedBy($this->authorityName)
                ->permittedFor($this->registryName)
                ->expiresAt(now()->addMinutes(2))
                ->toString())
            ->get("/$repository/tags/list")['tags']);
    }
}
