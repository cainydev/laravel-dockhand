<?php

namespace Cainy\Dockhand\Facades;

use Cainy\Dockhand\Resources\Scope as ScopeResource;
use Cainy\Dockhand\Resources\ScopeResourceType;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for the Scope class providing a clean static interface.
 *
 * This facade provides static access to the Scope class methods
 * while preserving instance-based functionality.
 *
 * @method static ScopeResource fromString(string $scope) Create a new scope instance by parsing a scope string.
 * @method static ScopeResource allowPull(?bool $enabled = true) Add pull access.
 * @method static ScopeResource allowPush(bool $enabled = true) Add push access.
 * @method static ScopeResource allowDelete(bool $enabled = true) Add delete access.
 * @method static ScopeResource catalog() Factory method to create a new catalog scope.
 * @method static ScopeResource allowAll() Allow all actions.
 * @method static ScopeResource repository(string $repo) Factory method to create a new scope for a repo.
 * @method static ScopeResource writeRepository(string $repo) Factory method to create a new scope for a repo with push access.
 * @method static ScopeResource readRepository(string $repo) Factory method to create a new scope for a repo with pull access.
 * @method static ScopeResource allowPushAndPull() Allow both push and pull actions.
 * @method static ScopeResource allowNone() Allow no actions.
 * @method static bool hasPull() Check if the scope has pull access.
 * @method static bool hasPush() Check if the scope has push access.
 * @method static bool hasDelete() Check if the scope has delete access.
 * @method static string toString() Convert the scope to a registry-compatible string.
 * @method static string toJson(int $options = 0) Convert the scope to JSON.
 * @method static array toArray() Convert the scope to an array.
 * @method static ScopeResourceType getResourceType() Get the resource type of the scope.
 * @method static string getResourceName() Get the resource name of the scope.
 * @method static array getActions() Get the actions of the scope.
 *
 * @see ScopeResource
 */
class Scope extends Facade
{
    protected static function getFacadeAccessor(): ScopeResource
    {
        return new ScopeResource;
    }

    protected static function resolveFacadeInstance($name)
    {
        return $name instanceof \Closure ? $name() : $name;
    }
}
