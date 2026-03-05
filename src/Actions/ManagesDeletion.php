<?php

namespace Cainy\Dockhand\Actions;

use Cainy\Dockhand\Enums\MediaType;
use Cainy\Dockhand\Facades\Scope;
use Cainy\Dockhand\Facades\Token;
use Exception;
use InvalidArgumentException;
use Illuminate\Http\Client\ConnectionException;

trait ManagesDeletion
{
    /**
     * Delete a manifest from the registry by digest.
     *
     * @param string $repository The full repository name (e.g., "john/busybox").
     * @param string $digest The manifest digest (e.g., "sha256:abc123..."). Tags are not accepted.
     * @return bool True if deleted, false if not found.
     * @throws InvalidArgumentException If the reference is not a digest.
     * @throws Exception If there's an issue with the request or response processing.
     */
    public function deleteManifest(string $repository, string $digest): bool
    {
        if (!str_contains($digest, ':')) {
            throw new InvalidArgumentException(
                "Manifests can only be deleted by digest, not by tag. Got: {$digest}"
            );
        }

        $this->logger()->debug('[ManagesDeletion] Deleting manifest', [
            'repository' => $repository,
            'digest' => $digest,
        ]);

        try {
            $response = $this->request()
                ->withToken(Token::withScope(Scope::deleteRepository($repository))
                    ->issuedBy($this->authorityName)
                    ->permittedFor($this->registryName)
                    ->expiresAt(now()->addMinutes(2))
                    ->toString())
                ->accept(MediaType::getManifestTypesAsString())
                ->delete("/{$repository}/manifests/{$digest}");
        } catch (ConnectionException $e) {
            throw new Exception("Connection to registry failed for {$repository}@{$digest}: " . $e->getMessage(), 0, $e);
        }

        if ($response->notFound()) {
            return false;
        }

        if ($response->status() === 405) {
            throw new Exception(
                "Delete is not allowed for {$repository}@{$digest}. "
                . 'The registry may have delete disabled or is a pull-through cache.'
            );
        }

        if (!$response->successful()) {
            $errorMessage = "Failed to delete manifest for {$repository}@{$digest}. Status: " . $response->status();
            $responseBody = $response->body();
            if ($responseBody) {
                $errorMessage .= " Body: " . $responseBody;
            }
            throw new Exception($errorMessage);
        }

        return true;
    }

    /**
     * Delete a blob from the registry by digest.
     *
     * @param string $repository The full repository name (e.g., "john/busybox").
     * @param string $digest The blob digest (e.g., "sha256:abc123...").
     * @return bool True if deleted, false if not found.
     * @throws Exception If there's an issue with the request or response processing.
     */
    public function deleteBlob(string $repository, string $digest): bool
    {
        $this->logger()->debug('[ManagesDeletion] Deleting blob', [
            'repository' => $repository,
            'digest' => $digest,
        ]);

        try {
            $response = $this->request()
                ->withToken(Token::withScope(Scope::deleteRepository($repository))
                    ->issuedBy($this->authorityName)
                    ->permittedFor($this->registryName)
                    ->expiresAt(now()->addMinutes(2))
                    ->toString())
                ->delete("/{$repository}/blobs/{$digest}");
        } catch (ConnectionException $e) {
            throw new Exception("Connection to registry failed for {$repository}@{$digest}: " . $e->getMessage(), 0, $e);
        }

        if ($response->notFound()) {
            return false;
        }

        if (!$response->successful()) {
            $errorMessage = "Failed to delete blob for {$repository}@{$digest}. Status: " . $response->status();
            $responseBody = $response->body();
            if ($responseBody) {
                $errorMessage .= " Body: " . $responseBody;
            }
            throw new Exception($errorMessage);
        }

        return true;
    }
}
