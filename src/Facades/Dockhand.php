<?php

namespace Cainy\Dockhand\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isOnline()
 * @method static \Cainy\Dockhand\Enums\RegistryApiVersion getApiVersion()
 * @method static \Illuminate\Support\Collection getCatalog()
 * @method static \Cainy\Dockhand\Resources\Repository getRepository(string $name)
 * @method static \Illuminate\Http\Client\PendingRequest request()
 * @method static \Illuminate\Support\Collection getTagsOfRepository(string $repository)
 * @method static \Cainy\Dockhand\Resources\ImageManifest getManifestOfTag(string $repository, string $tag)
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
