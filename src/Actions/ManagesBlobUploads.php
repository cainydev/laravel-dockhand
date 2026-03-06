<?php

namespace Cainy\Dockhand\Actions;

use Cainy\Dockhand\Exceptions\BlobUploadInvalidException;
use Cainy\Dockhand\Exceptions\BlobUploadUnknownException;
use Cainy\Dockhand\Exceptions\DigestInvalidException;
use Cainy\Dockhand\Exceptions\RangeInvalidException;
use Cainy\Dockhand\Resources\BlobUpload;
use Cainy\Dockhand\Resources\PushResult;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;

/**
 * @phpstan-require-extends \Cainy\Dockhand\Drivers\AbstractRegistryDriver
 */
trait ManagesBlobUploads
{
    /**
     * Initiate a blob upload.
     *
     * @param  string  $repository  The full repository name.
     * @return BlobUpload The upload state for subsequent requests.
     *
     * @throws Exception
     */
    public function initiateBlobUpload(string $repository): BlobUpload
    {
        $this->logger()->debug('[ManagesBlobUploads] Initiating blob upload', [
            'repository' => $repository,
        ]);

        try {
            $response = $this->authenticatedRequest('write', $repository)
                ->withBody('', 'application/octet-stream')
                ->post("/{$repository}/blobs/uploads/");
        } catch (ConnectionException $e) {
            throw new Exception("Connection to registry failed for {$repository}: ".$e->getMessage(), 0, $e);
        }

        if (! $response->successful()) {
            throw new Exception("Failed to initiate blob upload for {$repository}. Status: ".$response->status().' Body: '.$response->body());
        }

        return BlobUpload::fromResponse($repository, $response);
    }

    /**
     * Mount a blob from another repository.
     *
     * @param  string  $repository  The target repository.
     * @param  string  $digest  The blob digest to mount.
     * @param  string  $fromRepository  The source repository.
     * @return BlobUpload|PushResult PushResult if mount succeeded, BlobUpload if fallback to upload.
     *
     * @throws Exception
     */
    public function mountBlob(string $repository, string $digest, string $fromRepository): BlobUpload|PushResult
    {
        $this->logger()->debug('[ManagesBlobUploads] Mounting blob', [
            'repository' => $repository,
            'digest' => $digest,
            'fromRepository' => $fromRepository,
        ]);

        try {
            $response = $this->authenticatedRequest('mount', $repository, ['from' => $fromRepository])
                ->withBody('', 'application/octet-stream')
                ->post("/{$repository}/blobs/uploads/?mount={$digest}&from={$fromRepository}");
        } catch (ConnectionException $e) {
            throw new Exception("Connection to registry failed for {$repository}: ".$e->getMessage(), 0, $e);
        }

        if ($response->status() === 201) {
            return new PushResult(
                $response->header('Location'),
                $response->header($this->contentDigestHeader()),
            );
        }

        if ($response->status() === 202) {
            return BlobUpload::fromResponse($repository, $response);
        }

        throw new Exception("Failed to mount blob for {$repository}. Status: ".$response->status().' Body: '.$response->body());
    }

    /**
     * Upload a chunk of blob data.
     *
     * @param  BlobUpload  $upload  The current upload state.
     * @param  string  $data  The chunk data to upload.
     * @return BlobUpload The updated upload state.
     *
     * @throws RangeInvalidException
     * @throws BlobUploadUnknownException
     * @throws Exception
     */
    public function uploadBlobChunk(BlobUpload $upload, string $data): BlobUpload
    {
        $this->logger()->debug('[ManagesBlobUploads] Uploading blob chunk', [
            'repository' => $upload->repository,
            'uuid' => $upload->uuid,
            'offset' => $upload->offset,
            'dataLength' => strlen($data),
        ]);

        $endOffset = $upload->offset + strlen($data) - 1;

        try {
            [$request, $path] = $this->resolveUploadLocation($upload);
            $response = $request
                ->withHeaders([
                    'Content-Type' => 'application/octet-stream',
                    'Content-Range' => "{$upload->offset}-{$endOffset}",
                ])
                ->withBody($data, 'application/octet-stream')
                ->patch($path);
        } catch (ConnectionException $e) {
            throw new Exception('Connection to registry failed: '.$e->getMessage(), 0, $e);
        }

        if ($response->status() === 416) {
            throw new RangeInvalidException('Invalid range for blob upload chunk. Status: '.$response->status().' Body: '.$response->body());
        }

        if ($response->notFound()) {
            throw new BlobUploadUnknownException("Blob upload not found for {$upload->repository} (UUID: {$upload->uuid}).");
        }

        if (! $response->successful()) {
            throw new Exception("Failed to upload blob chunk for {$upload->repository}. Status: ".$response->status().' Body: '.$response->body());
        }

        return BlobUpload::fromResponse($upload->repository, $response);
    }

    /**
     * Complete a blob upload.
     *
     * @param  BlobUpload  $upload  The current upload state.
     * @param  string  $digest  The expected digest of the complete blob.
     * @param  string|null  $data  Optional final chunk of data.
     *
     * @throws DigestInvalidException
     * @throws BlobUploadInvalidException
     * @throws Exception
     */
    public function completeBlobUpload(BlobUpload $upload, string $digest, ?string $data = null): PushResult
    {
        $this->logger()->debug('[ManagesBlobUploads] Completing blob upload', [
            'repository' => $upload->repository,
            'uuid' => $upload->uuid,
            'digest' => $digest,
            'hasData' => $data !== null,
        ]);

        try {
            [$request, $path] = $this->resolveUploadLocation($upload);

            $separator = str_contains($path, '?') ? '&' : '?';
            $path .= "{$separator}digest={$digest}";

            if ($data !== null) {
                $request = $request->withBody($data, 'application/octet-stream');
            } else {
                $request = $request->withHeaders(['Content-Length' => '0']);
            }

            $response = $request->put($path);
        } catch (ConnectionException $e) {
            throw new Exception('Connection to registry failed: '.$e->getMessage(), 0, $e);
        }

        if ($response->status() === 201) {
            return new PushResult(
                $response->header('Location'),
                $response->header($this->contentDigestHeader()),
            );
        }

        if ($response->status() === 400) {
            /** @var array<string, mixed>|null $body */
            $body = $response->json();
            /** @var array<int, array<string, string>> $errors */
            $errors = $body['errors'] ?? [];
            $code = $errors[0]['code'] ?? '';

            if ($code === 'DIGEST_INVALID') {
                throw new DigestInvalidException('Digest invalid for blob upload completion: '.$response->body());
            }

            throw new BlobUploadInvalidException("Blob upload invalid for {$upload->repository}: ".$response->body());
        }

        throw new Exception("Failed to complete blob upload for {$upload->repository}. Status: ".$response->status().' Body: '.$response->body());
    }

    /**
     * Get the status of a blob upload.
     *
     * @param  string  $repository  The full repository name.
     * @param  string  $uuid  The upload UUID.
     *
     * @throws BlobUploadUnknownException
     * @throws Exception
     */
    public function getBlobUploadStatus(string $repository, string $uuid): BlobUpload
    {
        $this->logger()->debug('[ManagesBlobUploads] Getting blob upload status', [
            'repository' => $repository,
            'uuid' => $uuid,
        ]);

        try {
            $response = $this->authenticatedRequest('write', $repository)
                ->get("/{$repository}/blobs/uploads/{$uuid}");
        } catch (ConnectionException $e) {
            throw new Exception("Connection to registry failed for {$repository}: ".$e->getMessage(), 0, $e);
        }

        if ($response->notFound()) {
            throw new BlobUploadUnknownException("Blob upload not found for {$repository} (UUID: {$uuid}).");
        }

        if (! $response->successful()) {
            throw new Exception("Failed to get blob upload status for {$repository}. Status: ".$response->status().' Body: '.$response->body());
        }

        return BlobUpload::fromResponse($repository, $response);
    }

    /**
     * Cancel a blob upload.
     *
     * @param  string  $repository  The full repository name.
     * @param  string  $uuid  The upload UUID.
     * @return bool True if cancelled, false if not found.
     *
     * @throws Exception
     */
    public function cancelBlobUpload(string $repository, string $uuid): bool
    {
        $this->logger()->debug('[ManagesBlobUploads] Cancelling blob upload', [
            'repository' => $repository,
            'uuid' => $uuid,
        ]);

        try {
            $response = $this->authenticatedRequest('write', $repository)
                ->delete("/{$repository}/blobs/uploads/{$uuid}");
        } catch (ConnectionException $e) {
            throw new Exception("Connection to registry failed for {$repository}: ".$e->getMessage(), 0, $e);
        }

        if ($response->notFound()) {
            return false;
        }

        if (! $response->successful()) {
            throw new Exception("Failed to cancel blob upload for {$repository}. Status: ".$response->status().' Body: '.$response->body());
        }

        return true;
    }

    /**
     * Upload a complete blob in a single monolithic upload.
     *
     * Convenience method that initiates an upload and immediately completes it.
     *
     * @param  string  $repository  The full repository name.
     * @param  string  $data  The blob data.
     * @param  string  $digest  The expected digest of the blob.
     *
     * @throws Exception
     */
    public function uploadBlob(string $repository, string $data, string $digest): PushResult
    {
        $this->logger()->debug('[ManagesBlobUploads] Uploading blob (monolithic)', [
            'repository' => $repository,
            'digest' => $digest,
            'dataLength' => strlen($data),
        ]);

        $upload = $this->initiateBlobUpload($repository);

        return $this->completeBlobUpload($upload, $digest, $data);
    }

    /**
     * Resolve the upload location to a request and path.
     *
     * The Location header from blob upload responses may be absolute.
     * This helper strips the base URL prefix if present to produce a relative
     * path for use with $this->request().
     *
     * @return array{PendingRequest, string} [$request, $path]
     */
    private function resolveUploadLocation(BlobUpload $upload): array
    {
        $location = $upload->location;
        $request = $this->authenticatedRequest('write', $upload->repository);

        if (str_starts_with($location, 'http://') || str_starts_with($location, 'https://')) {
            if (str_starts_with($location, $this->baseUrl)) {
                $path = substr($location, strlen($this->baseUrl));

                return [$request, $path];
            }

            // Absolute URL that doesn't match our base URL — use it directly
            return [$request->baseUrl(''), $location];
        }

        return [$request, $location];
    }
}
