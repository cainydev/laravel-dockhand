<?php

namespace Cainy\Dockhand\Actions;

use Cainy\Dockhand\Enums\RegistryApiVersion;
use Cainy\Dockhand\Facades\Token;
use Illuminate\Http\Client\ConnectionException;

trait ManagesRegistry
{
    /**
     * Check if registry is online
     */
    public function isOnline(): bool
    {
        try {
            $this
                ->request()
                ->withToken(Token::toString())
                ->get('/');

            return true;
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
            default => RegistryApiVersion::V2,
        };
    }
}
