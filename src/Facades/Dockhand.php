<?php

namespace Cainy\Dockhand\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Cainy\Dockhand\Drivers\AbstractRegistryDriver connection(?string $name = null)
 * @method static \Cainy\Dockhand\Drivers\ZotDriver zot(?string $name = null)
 * @method static \Cainy\Dockhand\Drivers\DistributionDriver distribution(?string $name = null)
 * @method static void disconnect(?string $name = null)
 * @method static bool isOnline()
 * @method static \Cainy\Dockhand\Enums\RegistryApiVersion getApiVersion()
 * @method static \Illuminate\Http\Client\PendingRequest request()
 * @method static \Illuminate\Support\Collection<int, string>|\Cainy\Dockhand\Resources\PaginatedResult getRepositories(?int $limit = null, ?string $last = null)
 * @method static \Illuminate\Support\Collection<int, string>|\Cainy\Dockhand\Resources\PaginatedResult getTagsOfRepository(string $repository, ?int $limit = null, ?string $last = null)
 * @method static \Cainy\Dockhand\Resources\ImageManifest|\Cainy\Dockhand\Resources\ManifestList|null getManifest(string $repository, string $reference)
 * @method static \Cainy\Dockhand\Resources\ManifestHead|null headManifest(string $repository, string $reference)
 * @method static \Cainy\Dockhand\Resources\PushResult putManifest(string $repository, string $reference, \Cainy\Dockhand\Resources\ManifestResource|string $manifest, ?\Cainy\Dockhand\Enums\MediaType $mediaType = null)
 * @method static bool deleteManifest(string $repository, string $reference)
 * @method static bool deleteBlob(string $repository, string $digest)
 * @method static string|null getBlob(string $repository, string $reference)
 * @method static int|null getBlobSize(string $repository, string $reference)
 * @method static \Cainy\Dockhand\Resources\ImageConfig|null getImageConfigFromDescriptor(\Cainy\Dockhand\Resources\ImageConfigDescriptor $descriptor)
 * @method static \Cainy\Dockhand\Resources\BlobUpload initiateBlobUpload(string $repository)
 * @method static \Cainy\Dockhand\Resources\BlobUpload|\Cainy\Dockhand\Resources\PushResult mountBlob(string $repository, string $digest, string $fromRepository)
 * @method static \Cainy\Dockhand\Resources\BlobUpload uploadBlobChunk(\Cainy\Dockhand\Resources\BlobUpload $upload, string $data)
 * @method static \Cainy\Dockhand\Resources\PushResult completeBlobUpload(\Cainy\Dockhand\Resources\BlobUpload $upload, string $digest, ?string $data = null)
 * @method static \Cainy\Dockhand\Resources\BlobUpload getBlobUploadStatus(string $repository, string $uuid)
 * @method static bool cancelBlobUpload(string $repository, string $uuid)
 * @method static \Cainy\Dockhand\Resources\PushResult uploadBlob(string $repository, string $data, string $digest)
 * @method static array<int, mixed> getReferrers(string $repository, string $digest, ?string $artifactType = null)
 *
 * @see \Cainy\Dockhand\DockhandManager
 */
class Dockhand extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Cainy\Dockhand\DockhandManager::class;
    }
}
