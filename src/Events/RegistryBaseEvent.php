<?php

namespace Cainy\Dockhand\Events;

use Cainy\Dockhand\Resources\MediaType;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

abstract class RegistryBaseEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $id {
        get => $this->id;
    }

    public Carbon $timestamp {
        get => $this->timestamp;
    }

    public EventAction $action {
        get => $this->action;
    }

    public MediaType $targetMediaType {
        get => $this->targetMediaType;
    }

    public string $targetDigest {
        get => $this->targetDigest;
    }

    public string $targetRepository {
        get => $this->targetRepository;
    }

    public string $requestId {
        get => $this->requestId;
    }

    public string $requestAddr {
        get => $this->requestAddr;
    }

    public string $requestHost {
        get => $this->requestHost;
    }

    public string $requestMethod {
        get => $this->requestMethod;
    }

    public string $requestUserAgent {
        get => $this->requestUserAgent;
    }

    public string $actorName {
        get => $this->actorName;
    }

    public string $sourceAddr {
        get => $this->sourceAddr;
    }
    public string $sourceInstanceId {
        get => $this->sourceInstanceId;
    }

    /**
     * Base constructor for all registry events.
     *
     * @param array $data Raw notification data from the registry webhook.
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->timestamp = Carbon::parse($data['timestamp']);
        $this->action = EventAction::from($data['action']);

        $this->targetDigest = $data['target']['digest'];
        $this->targetRepository = $data['target']['repository'];

        $this->requestId = $data['request']['id'];
        $this->requestAddr = $data['request']['addr'];
        $this->requestHost = $data['request']['host'];
        $this->requestMethod = $data['request']['method'];
        $this->requestUserAgent = $data['request']['useragent'];

        $this->actorName = $data['actor']['name'];

        $this->sourceAddr = $data['source']['addr'];
        $this->sourceInstanceId = $data['source']['instanceID'];
    }
}
