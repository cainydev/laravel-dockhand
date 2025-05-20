<?php

namespace Cainy\Dockhand\Events;

abstract class RegistryEvent extends RegistryBaseEvent
{
    public int $targetSize {
        get => $this->targetSize;
    }

    public string $targetUrl {
        get => $this->targetUrl;
    }

    public string $targetTag {
        get => $this->targetTag;
    }

    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->targetSize = $data['target']['size'];
        $this->targetUrl = $data['target']['url'];
        $this->targetTag = $data['target']['tag'];
    }

}
