<?php

namespace Cainy\Dockhand\Events;

use Cainy\Dockhand\Enums\MediaType;

abstract class RegistryEvent extends RegistryBaseEvent
{
    public MediaType $targetMediaType {
        get => $this->targetMediaType;
    }

    public int $targetSize {
        get => $this->targetSize;
    }

    public string $targetUrl {
        get => $this->targetUrl;
    }

    public ?string $targetTag {
        get => $this->targetTag;
    }

    /**
     * @param array{id: string, timestamp: string, action: string, target: array{mediaType: string, size: int, url: string, tag?: string, digest?: string, repository: string}, request: array{id: string, addr: string, host: string, method: string, useragent: string}, actor?: array{name: string}, source: array{addr: string, instanceID: string}} $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->targetMediaType = MediaType::from($data['target']['mediaType']);
        $this->targetSize = $data['target']['size'];
        $this->targetUrl = $data['target']['url'];

        if (isset($data['target']['tag'])) {
            $this->targetTag = $data['target']['tag'];
        } else {
            $this->targetTag = null;
        }
    }

}
