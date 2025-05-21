<?php

namespace Cainy\Dockhand\Actions;

use Cainy\Dockhand\Facades\Scope;
use Cainy\Dockhand\Facades\Token;
use Cainy\Dockhand\Resources\ManifestResource;
use Cainy\Dockhand\Resources\MediaType;
use Exception;
use Illuminate\Http\Client\ConnectionException;

trait ManagesManifests
{
    /**
     * Get a manifest from the registry.
     *
     * @param string $repository The full repository name (e.g., "john/busybox").
     * @param string $reference The tag or digest.
     * @return ManifestResource|null The manifest resource, or null if not found.
     * @throws Exception If there's an issue with the request or response processing (other than 404).
     */
    public function getManifest(string $repository, string $reference): ?ManifestResource
    {
        try {
            $response = $this->request()
                ->withToken(Token::withScope(Scope::readRepository($repository))
                    ->issuedBy($this->authorityName)
                    ->permittedFor($this->registryName)
                    ->expiresAt(now()->addMinutes(2))
                    ->toString())
                ->accept(MediaType::getManifestTypesAsString())
                ->get("/{$repository}/manifests/{$reference}");
        } catch (ConnectionException $e) {
            throw new Exception("Connection to registry failed for {$repository}:{$reference}: " . $e->getMessage(), 0, $e);
        }

        if ($response->notFound()) {
            return null;
        }

        if (!$response->successful()) {
            $errorMessage = "Failed to fetch manifest for {$repository}:{$reference}. Status: " . $response->status();
            $responseBody = $response->body();
            if ($responseBody) {
                $errorMessage .= " Body: " . $responseBody;
            }
            throw new Exception($errorMessage);
        }

        $manifestData = $response->json();
        if ($manifestData === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Failed to decode manifest JSON for {$repository}:{$reference}. Response Body: " . $response->body());
        }

        $actualDigest = $response->header('Docker-Content-Digest');

        if (empty($actualDigest)) {
            $actualDigest = $response->header('ETag');
            if ($actualDigest) {
                $actualDigest = trim($actualDigest, '"W/');
            }
        }

        if (empty($actualDigest)) {
            throw new Exception("Registry did not provide 'Docker-Content-Digest' or 'ETag' header for manifest {$repository}:{$reference}.");
        }

        try {
            return new ManifestResource(
                $repository,
                $reference,
                $actualDigest,
                $manifestData
            );
        } catch (Exception $e) {
            throw new Exception("Error constructing ManifestResource for {$repository}:{$reference}: " . $e->getMessage(), 0, $e);
        }
    }
}
