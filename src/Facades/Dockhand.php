<?php

namespace Cainy\Dockhand\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isOnline()
 * @method static \Cainy\Dockhand\Enums\RegistryApiVersion getApiVersion()
 * @method static \Illuminate\Support\Collection getCatalog()
 * @method static \Cainy\Dockhand\Resources\Repository getRepository(string $name)
 * @method static \Illuminate\Http\Client\PendingRequest request()
 * @method static \Illuminate\Support\Collection|\Cainy\Dockhand\Resources\PaginatedResult getRepositories(?int $limit = null, ?string $last = null)
 * @method static \Illuminate\Support\Collection|\Cainy\Dockhand\Resources\PaginatedResult getTagsOfRepository(string $repository, ?int $limit = null, ?string $last = null)
 * @method static \Cainy\Dockhand\Resources\ImageManifest|\Cainy\Dockhand\Resources\ManifestList|null getManifest(string $repository, string $reference)
 * @method static \Cainy\Dockhand\Resources\ManifestHead|null headManifest(string $repository, string $reference)
 * @method static \Cainy\Dockhand\Resources\PushResult putManifest(string $repository, string $reference, \Cainy\Dockhand\Resources\ManifestResource|string $manifest, ?\Cainy\Dockhand\Enums\MediaType $mediaType = null)
 * @method static bool deleteManifest(string $repository, string $digest)
 * @method static bool deleteBlob(string $repository, string $digest)
 * @method static \Cainy\Dockhand\Resources\BlobUpload initiateBlobUpload(string $repository)
 * @method static \Cainy\Dockhand\Resources\BlobUpload|\Cainy\Dockhand\Resources\PushResult mountBlob(string $repository, string $digest, string $fromRepository)
 * @method static \Cainy\Dockhand\Resources\BlobUpload uploadBlobChunk(\Cainy\Dockhand\Resources\BlobUpload $upload, string $data)
 * @method static \Cainy\Dockhand\Resources\PushResult completeBlobUpload(\Cainy\Dockhand\Resources\BlobUpload $upload, string $digest, ?string $data = null)
 * @method static \Cainy\Dockhand\Resources\BlobUpload getBlobUploadStatus(string $repository, string $uuid)
 * @method static bool cancelBlobUpload(string $repository, string $uuid)
 * @method static \Cainy\Dockhand\Resources\PushResult uploadBlob(string $repository, string $data, string $digest)
 *
 * @see \Cainy\Dockhand\Dockhand
 */
class Dockhand extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Cainy\Dockhand\Dockhand::class;
    }
}
