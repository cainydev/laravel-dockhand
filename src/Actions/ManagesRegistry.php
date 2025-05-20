<?php

namespace Cainy\Dockhand\Actions;

use Cainy\Dockhand\Facades\Token;
use Cainy\Dockhand\Resources\RegistryApiVersion;
use Illuminate\Http\Client\ConnectionException;

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
                ->withToken(Token::toString())
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
            ->withToken(Token::toString())
            ->get('/');

        return match ($response->getHeaderLine('Docker-Distribution-Api-Version')) {
            'registry/1.0' => RegistryApiVersion::V1,
            'registry/2.0' => RegistryApiVersion::V2
        };
    }
}
