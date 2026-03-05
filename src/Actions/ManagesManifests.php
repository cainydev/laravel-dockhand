<?php

namespace Cainy\Dockhand\Actions;

use Cainy\Dockhand\Enums\MediaType;
use Cainy\Dockhand\Exceptions\ManifestBlobUnknownException;
use Cainy\Dockhand\Exceptions\ManifestInvalidException;
use Cainy\Dockhand\Exceptions\UnsupportedException;
use Cainy\Dockhand\Facades\Scope;
use Cainy\Dockhand\Facades\Token;
use Cainy\Dockhand\Resources\ImageManifest;
use Cainy\Dockhand\Resources\ManifestHead;
use Cainy\Dockhand\Resources\ManifestList;
use Cainy\Dockhand\Resources\ManifestListEntry;
use Cainy\Dockhand\Resources\ManifestResource;
use Cainy\Dockhand\Resources\PushResult;
use Exception;
use InvalidArgumentException;
use Illuminate\Http\Client\ConnectionException;

trait ManagesManifests
{
    /**
     * Check if a manifest exists and get its metadata via HEAD request.
     *
     * @param string $repository The full repository name (e.g., "john/busybox").
     * @param string $reference The tag or digest.
     * @return ManifestHead|null The manifest head info, or null if not found.
     * @throws Exception If there's an issue with the request or response processing (other than 404).
     */
    public function headManifest(string $repository, string $reference): ?ManifestHead
    {
        $this->logger()->debug('[ManagesManifests] HEAD manifest', [
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
                ->head("/{$repository}/manifests/{$reference}");
        } catch (ConnectionException $e) {
            throw new Exception("Connection to registry failed for {$repository}:{$reference}: " . $e->getMessage(), 0, $e);
        }

        if ($response->notFound()) {
            return null;
        }

        if (!$response->successful()) {
            throw new Exception("Failed to HEAD manifest for {$repository}:{$reference}. Status: " . $response->status());
        }

        return new ManifestHead(
            $response->header('Docker-Content-Digest'),
            (int) $response->header('Content-Length'),
            $response->header('Content-Type'),
        );
    }

    /**
     * Push a manifest to the registry.
     *
     * @param string $repository The full repository name (e.g., "john/busybox").
     * @param string $reference The tag or digest.
     * @param ManifestResource|string $manifest The manifest to push (resource or raw JSON string).
     * @param MediaType|null $mediaType Required when $manifest is a string.
     * @return PushResult
     * @throws InvalidArgumentException If $manifest is a string and $mediaType is null.
     * @throws ManifestBlobUnknownException If a referenced blob is unknown.
     * @throws ManifestInvalidException If the manifest is invalid.
     * @throws UnsupportedException If the registry does not support manifest puts.
     * @throws Exception
     */
    public function putManifest(string $repository, string $reference, ManifestResource|string $manifest, ?MediaType $mediaType = null): PushResult
    {
        $this->logger()->debug('[ManagesManifests] Pushing manifest', [
            'repository' => $repository,
            'reference' => $reference,
        ]);

        if ($manifest instanceof ManifestResource) {
            $mediaType = $manifest->mediaType;
            $body = json_encode($this->buildManifestBody($manifest->toArray()));
        } else {
            if ($mediaType === null) {
                throw new InvalidArgumentException('$mediaType is required when $manifest is a string.');
            }
            $body = $manifest;
        }

        try {
            $response = $this->request()
                ->withToken(Token::withScope(Scope::writeRepository($repository))
                    ->issuedBy($this->authorityName)
                    ->permittedFor($this->registryName)
                    ->expiresAt(now()->addMinutes(2))
                    ->toString())
                ->contentType($mediaType->toString())
                ->withBody($body, $mediaType->toString())
                ->put("/{$repository}/manifests/{$reference}");
        } catch (ConnectionException $e) {
            throw new Exception("Connection to registry failed for {$repository}:{$reference}: " . $e->getMessage(), 0, $e);
        }

        if ($response->status() === 201) {
            return new PushResult(
                $response->header('Location'),
                $response->header('Docker-Content-Digest'),
            );
        }

        if ($response->status() === 400) {
            $errorBody = $response->json();
            $code = $errorBody['errors'][0]['code'] ?? '';

            if ($code === 'BLOB_UNKNOWN') {
                throw new ManifestBlobUnknownException("Referenced blob unknown for {$repository}:{$reference}: " . $response->body());
            }

            throw new ManifestInvalidException("Manifest invalid for {$repository}:{$reference}: " . $response->body());
        }

        if ($response->status() === 405) {
            throw new UnsupportedException("Manifest PUT not supported for {$repository}:{$reference}.");
        }

        if (!$response->successful()) {
            throw new Exception("Failed to push manifest for {$repository}:{$reference}. Status: " . $response->status() . " Body: " . $response->body());
        }

        // Some registries return 200 instead of 201
        return new PushResult(
            $response->header('Location') ?? '',
            $response->header('Docker-Content-Digest') ?? '',
        );
    }

    /**
     * Build the wire-format manifest body by stripping non-wire fields.
     *
     * @param array $data
     * @return array
     */
    private function buildManifestBody(array $data): array
    {
        unset($data['repository'], $data['digest']);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->buildManifestBody($value);
            }
        }

        return $data;
    }

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
