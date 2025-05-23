<?php

namespace Cainy\Dockhand\Actions;

use Cainy\Dockhand\Facades\Scope;
use Cainy\Dockhand\Facades\Token;
use Cainy\Dockhand\Resources\ImageConfig;
use Cainy\Dockhand\Resources\ImageConfigDescriptor;
use Exception;
use Illuminate\Http\Client\ConnectionException;

trait ManagesBlobs
{
    /**
     * Get a blob from the registry as a string.
     *
     * @param string $repository The full repository name (e.g., "john/busybox").
     * @param string $reference The tag or digest.
     * @return string|null The blobs content or null if not found.
     * @throws Exception If there's an issue with the request or response processing (other than 404).
     */
    public function getBlob(string $repository, string $reference): string|null
    {
        try {
            $response = $this->request()
                ->withToken(Token::withScope(Scope::readRepository($repository))
                    ->issuedBy($this->authorityName)
                    ->permittedFor($this->registryName)
                    ->expiresAt(now()->addMinutes(2))
                    ->toString())
                ->accept('*/*')
                ->get("/{$repository}/blobs/{$reference}");
        } catch (ConnectionException $e) {
            throw new Exception("Connection to registry failed for {$repository}:{$reference}: " . $e->getMessage(), 0, $e);
        }

        if ($response->notFound()) {
            return null;
        }

        if (!$response->successful()) {
            $errorMessage = "Failed to fetch blob for {$repository}:{$reference}. Status: " . $response->status();
            $responseBody = $response->body();
            if ($responseBody) {
                $errorMessage .= " Body: " . $responseBody;
            }
            throw new Exception($errorMessage);
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

        return $response->body();
    }

    /**
     * Get the size of a blob from the registry.
     *
     * @param string $repository The full repository name (e.g., "john/busybox").
     * @param string $reference The tag or digest.
     * @return int|null The size of the blob in bytes, or null if not found.
     * @throws Exception If there's an issue with the request or response processing (other than 404).
     */
    public function getBlobSize(string $repository, string $reference): int|null
    {
        try {
            $response = $this->request()
                ->withToken(Token::withScope(Scope::readRepository($repository))
                    ->issuedBy($this->authorityName)
                    ->permittedFor($this->registryName)
                    ->expiresAt(now()->addMinutes(2))
                    ->toString())
                ->head("/{$repository}/blobs/{$reference}");
        } catch (ConnectionException $e) {
            throw new Exception("Connection to registry failed for {$repository}:{$reference}: " . $e->getMessage(), 0, $e);
        }

        if ($response->notFound()) {
            return null;
        }

        if (!$response->successful()) {
            $errorMessage = "Failed to fetch blob size for {$repository}:{$reference}. Status: " . $response->status();
            $responseBody = $response->body();
            if ($responseBody) {
                $errorMessage .= " Body: " . $responseBody;
            }
            throw new Exception($errorMessage);
        }

        return (int)$response->header('Content-Length');
    }

    /**
     * Get the image config from the registry using a descriptor.
     *
     * @param ImageConfigDescriptor $descriptor
     * @return ImageConfig|null
     * @throws Exception
     */
    public function getImageConfigFromDescriptor(ImageConfigDescriptor $descriptor): ImageConfig|null
    {
        try {
            $response = $this->request()
                ->withToken(Token::withScope(Scope::readRepository($descriptor->repository))
                    ->issuedBy($this->authorityName)
                    ->permittedFor($this->registryName)
                    ->expiresAt(now()->addMinutes(2))
                    ->toString())
                ->get("/{$descriptor->repository}/blobs/{$descriptor->digest}");
        } catch (ConnectionException $e) {
            throw new Exception("Connection to registry failed for {$descriptor->repository}:{$descriptor->digest}: " . $e->getMessage(), 0, $e);
        }

        if ($response->notFound()) {
            return null;
        }

        if (!$response->successful()) {
            $errorMessage = "Failed to fetch image config for {$descriptor->repository}:{$descriptor->digest}. Status: " . $response->status();
            $responseBody = $response->body();
            if ($responseBody) {
                $errorMessage .= " Body: " . $responseBody;
            }
            throw new Exception($errorMessage);
        }

        return ImageConfig::parse(
            $descriptor->repository,
            $descriptor->digest,
            $descriptor->mediaType,
            $response->json());
    }
}
