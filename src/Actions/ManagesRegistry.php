<?php

namespace Cainy\Dockhand\Actions;

use Cainy\Dockhand\Enums\RegistryApiVersion;
use Illuminate\Http\Client\ConnectionException;

/**
 * @phpstan-require-extends \Cainy\Dockhand\Drivers\AbstractRegistryDriver
 */
trait ManagesRegistry
{
    /**
     * Check if registry is online
     */
    public function isOnline(): bool
    {
        try {
            $this->authenticatedRequest('none')
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
        $response = $this->authenticatedRequest('none')
            ->get('/');

        return match ($response->header('Docker-Distribution-Api-Version')) {
            'registry/1.0' => RegistryApiVersion::V1,
            default => RegistryApiVersion::V2,
        };
    }
}
