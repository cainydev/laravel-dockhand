<?php

namespace Cainy\Dockhand\Resources;

use Cainy\Dockhand\Facades\Dockhand;
use Illuminate\Http\Client\ConnectionException;

class Tag
{
    /**
     * The name of the repository this tag is part of.
     */
    protected string $repository {
        get {
            return $this->repository;
        }
    }

    /**
     * The name of the tag.
     */
    protected string $name {
        get {
            return $this->name;
        }
    }

    /**
     * Create a new tag instance.
     */
    public function __construct(string $repository, string $name)
    {
        $this->repository = $repository;
        $this->name = $name;
    }

    /**
     * Get the name of the tag.
     */
    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * Get the name of the tag.
     */
    public function toString(): string
    {
        return $this->name;
    }

    /**
     * Get the manifest of the tag.
     *
     * @throws ConnectionException
     */
    public function getManifest(): ImageManifest
    {
        return Dockhand::getManifestOfTag($this->repository, $this->name);
    }
}
