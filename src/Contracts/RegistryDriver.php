<?php

namespace Cainy\Dockhand\Contracts;

use Cainy\Dockhand\Enums\MediaType;
use Cainy\Dockhand\Enums\RegistryApiVersion;
use Cainy\Dockhand\Resources\BlobUpload;
use Cainy\Dockhand\Resources\ImageConfig;
use Cainy\Dockhand\Resources\ImageConfigDescriptor;
use Cainy\Dockhand\Resources\ImageManifest;
use Cainy\Dockhand\Resources\ManifestHead;
use Cainy\Dockhand\Resources\ManifestList;
use Cainy\Dockhand\Resources\ManifestResource;
use Cainy\Dockhand\Resources\PaginatedResult;
use Cainy\Dockhand\Resources\PushResult;
use Illuminate\Support\Collection;

interface RegistryDriver
{
    // Registry
    public function isOnline(): bool;

    public function getApiVersion(): RegistryApiVersion;

    // Repositories
    /**
     * @return Collection<int, string>|PaginatedResult
     */
    public function getRepositories(?int $limit = null, ?string $last = null): Collection|PaginatedResult;

    /**
     * @return Collection<int, string>|PaginatedResult
     */
    public function getTagsOfRepository(string $repository, ?int $limit = null, ?string $last = null): Collection|PaginatedResult;

    // Manifests
    public function getManifest(string $repository, string $reference): ImageManifest|ManifestList|null;

    public function headManifest(string $repository, string $reference): ?ManifestHead;

    public function putManifest(string $repository, string $reference, ManifestResource|string $manifest, ?MediaType $mediaType = null): PushResult;

    public function deleteManifest(string $repository, string $reference): bool;

    // Blobs
    public function getBlob(string $repository, string $reference): string|null;

    public function getBlobSize(string $repository, string $reference): int|null;

    public function getImageConfigFromDescriptor(ImageConfigDescriptor $descriptor): ImageConfig|null;

    public function deleteBlob(string $repository, string $digest): bool;

    // Blob Uploads
    public function initiateBlobUpload(string $repository): BlobUpload;

    public function mountBlob(string $repository, string $digest, string $fromRepository): BlobUpload|PushResult;

    public function uploadBlobChunk(BlobUpload $upload, string $data): BlobUpload;

    public function completeBlobUpload(BlobUpload $upload, string $digest, ?string $data = null): PushResult;

    public function getBlobUploadStatus(string $repository, string $uuid): BlobUpload;

    public function cancelBlobUpload(string $repository, string $uuid): bool;

    public function uploadBlob(string $repository, string $data, string $digest): PushResult;

    // OCI v1.1 Referrers
    /**
     * @return array<int, mixed>
     */
    public function getReferrers(string $repository, string $digest, ?string $artifactType = null): array;
}
