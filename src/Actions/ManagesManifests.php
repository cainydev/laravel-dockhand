<?php

namespace Cainy\Dockhand\Actions;

use Cainy\Dockhand\Enums\MediaType;
use Cainy\Dockhand\Facades\Scope;
use Cainy\Dockhand\Facades\Token;
use Cainy\Dockhand\Resources\ImageManifest;
use Cainy\Dockhand\Resources\ManifestList;
use Cainy\Dockhand\Resources\ManifestListEntry;
use Exception;
use Illuminate\Http\Client\ConnectionException;

trait ManagesManifests
{
    /**
     * Get a manifest from a manifest list entry.
     *
     * @param ManifestListEntry $entry The manifest list entry to get the manifest from.
     * @return ImageManifest|ManifestList|null The manifest resource, or null if not found.
     * @throws Exception If there's an issue with the request or response processing (other than 404).
     */
    public function getManifestFromManifestListEntry(ManifestListEntry $entry): ImageManifest|ManifestList|null
    {
        return $this->getManifest($entry->repository, $entry->digest);
    }

    /**
     * Get a manifest from the registry.
     *
     * @param string $repository The full repository name (e.g., "john/busybox").
     * @param string $reference The tag or digest.
     * @return ImageManifest|ManifestList|null The manifest resource, or null if not found.
     * @throws Exception If there's an issue with the request or response processing (other than 404).
     */
    public function getManifest(string $repository, string $reference): ImageManifest|ManifestList|null
    {
        $this->logger()->debug("[ManagesManifests] Getting manifest", [
            'repository' => $repository,
            'reference' => $reference,
        ]);

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

        $this->logger()->debug("[ManagesManifests] Parsed manifest data from json", $manifestData);

        $digest = $response->header('Docker-Content-Digest');

        if (empty($digest)) {
            $this->logger()->debug("[ManagesManifests] Docker-Content-Digest header was empty", $response->headers());
            $digest = $response->header('ETag');
            if ($digest) {
                $digest = trim($digest, '"W/');

                $this->logger()->debug("[ManagesManifests] Got it instead from ETag header", [
                    'header' => $response->header('ETag'),
                    'parsed' => $digest,
                ]);
            }
        }

        if (empty($digest)) {
            throw new Exception("Registry did not provide 'Docker-Content-Digest' or 'ETag' header for manifest {$repository}:{$reference}.");
        }

        if (empty($manifestData['mediaType'])) {
            throw new Exception("Manifest for {$repository}:{$reference} does not contain 'mediaType' field.");
        }

        $mediaType = MediaType::from($manifestData['mediaType']);

        if ($mediaType->isManifestList()) {
            $this->logger()->debug("[ManagesManifests] Trying to parse ManifestList from data", $manifestData);
            return ManifestList::parse($repository, $digest, $manifestData);
        } elseif ($mediaType->isImageManifest()) {
            $this->logger()->debug("[ManagesManifests] Trying to parse ImageManifest from data", $manifestData);
            return ImageManifest::parse($repository, $digest, $manifestData);
        } else {
            throw new Exception("Unsupported media type '{$mediaType->toString()}' for manifest {$repository}:{$reference}.");
        }
    }
}
