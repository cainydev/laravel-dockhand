<?php

namespace Cainy\Dockhand\Actions;

use Cainy\Dockhand\Enums\MediaType;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use InvalidArgumentException;

/**
 * @phpstan-require-extends \Cainy\Dockhand\Drivers\AbstractRegistryDriver
 */
trait ManagesDeletion
{
    /**
     * Delete a manifest from the registry.
     *
     * @param  string  $repository  The full repository name (e.g., "john/busybox").
     * @param  string  $reference  The manifest digest (e.g., "sha256:abc123...") or tag (if supported by the driver).
     * @return bool True if deleted, false if not found.
     *
     * @throws InvalidArgumentException If the reference is a tag and the driver doesn't support tag deletion.
     * @throws Exception If there's an issue with the request or response processing.
     */
    public function deleteManifest(string $repository, string $reference): bool
    {
        if (! str_contains($reference, ':') && ! $this->supportsTagDeletion()) {
            throw new InvalidArgumentException(
                "This registry only supports deletion by digest. Got: {$reference}"
            );
        }

        $this->logger()->debug('[ManagesDeletion] Deleting manifest', [
            'repository' => $repository,
            'reference' => $reference,
        ]);

        try {
            $response = $this->authenticatedRequest('delete', $repository)
                ->accept(MediaType::getManifestTypesAsString())
                ->delete("/{$repository}/manifests/{$reference}");
        } catch (ConnectionException $e) {
            throw new Exception("Connection to registry failed for {$repository}@{$reference}: ".$e->getMessage(), 0, $e);
        }

        if ($response->notFound()) {
            return false;
        }

        if ($response->status() === 405) {
            throw new Exception(
                "Delete is not allowed for {$repository}@{$reference}. "
                .'The registry may have delete disabled or is a pull-through cache.'
            );
        }

        if (! $response->successful()) {
            $errorMessage = "Failed to delete manifest for {$repository}@{$reference}. Status: ".$response->status();
            $responseBody = $response->body();
            if ($responseBody) {
                $errorMessage .= ' Body: '.$responseBody;
            }
            throw new Exception($errorMessage);
        }

        return true;
    }

    /**
     * Delete a blob from the registry by digest.
     *
     * @param  string  $repository  The full repository name (e.g., "john/busybox").
     * @param  string  $digest  The blob digest (e.g., "sha256:abc123...").
     * @return bool True if deleted, false if not found.
     *
     * @throws Exception If there's an issue with the request or response processing.
     */
    public function deleteBlob(string $repository, string $digest): bool
    {
        $this->logger()->debug('[ManagesDeletion] Deleting blob', [
            'repository' => $repository,
            'digest' => $digest,
        ]);

        try {
            $response = $this->authenticatedRequest('delete', $repository)
                ->delete("/{$repository}/blobs/{$digest}");
        } catch (ConnectionException $e) {
            throw new Exception("Connection to registry failed for {$repository}@{$digest}: ".$e->getMessage(), 0, $e);
        }

        if ($response->notFound()) {
            return false;
        }

        if (! $response->successful()) {
            $errorMessage = "Failed to delete blob for {$repository}@{$digest}. Status: ".$response->status();
            $responseBody = $response->body();
            if ($responseBody) {
                $errorMessage .= ' Body: '.$responseBody;
            }
            throw new Exception($errorMessage);
        }

        return true;
    }
}
