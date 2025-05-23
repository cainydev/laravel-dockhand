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
