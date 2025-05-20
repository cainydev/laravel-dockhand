<?php

namespace Cainy\Dockhand\Actions;

use Cainy\Dockhand\Facades\Dockhand;
use Cainy\Dockhand\Resources\RegistryApiVersion;
use Cainy\Dockhand\Resources\Scope;
use Cainy\Dockhand\Resources\Token;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;

trait ManagesRegistry
{
    /**
     * Check if registry is online
     */
    public function isOnline(): bool
    {
        try {
            return $this
                ->request()
                ->withToken(Token::create()->toString())
                ->get('/')
                ->successful();
        } catch (ConnectionException $e) {
            return false;
        }
    }

    /**
     * Get the version of the registry api.
     *
     * @throws ConnectionException
     */
    public function getApiVersion(): RegistryApiVersion
    {
        $response = $this
            ->request()
            ->withToken(Token::create()->toString())
            ->get('/');

        return match ($response->getHeaderLine('Docker-Distribution-Api-Version')) {
            'registry/1.0' => RegistryApiVersion::V1,
            'registry/2.0' => RegistryApiVersion::V2
        };
    }

    /**
     * Get a list of all the repositories in the registry.
     * Only returns the names of the repositories.
     *
     * @throws ConnectionException
     */
    public function getCatalog(): Collection
    {
        return collect(Dockhand::request()
            ->withToken(Token::withScope(Scope::catalog())->toString())
            ->get('/_catalog')['repositories']);
    }
}
