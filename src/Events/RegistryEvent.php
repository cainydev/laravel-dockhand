<?php

namespace Cainy\Dockhand\Events;

abstract class RegistryEvent extends RegistryBaseEvent
{
    protected int $targetSize {
        get {
            return $this->targetSize;
        }
    }

    protected string $targetUrl {
        get {
            return $this->targetUrl;
        }
    }

    protected string $targetTag {
        get {
            return $this->targetTag;
        }
    }

    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->targetSize = $data['target']['size'];
        $this->targetUrl = $data['target']['url'];
        $this->targetTag = $data['target']['tag'];
    }

}
