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

    protected string $id {
        get {
            return $this->id;
        }
    }
    protected Carbon $timestamp {
        get {
            return $this->timestamp;
        }
    }
    protected EventAction $action {
        get {
            return $this->action;
        }
    }
    protected MediaType $targetMediaType {
        get {
            return $this->targetMediaType;
        }
    }
    protected string $targetDigest {
        get {
            return $this->targetDigest;
        }
    }
    protected string $targetRepository {
        get {
            return $this->targetRepository;
        }
    }
    protected string $requestId {
        get {
            return $this->requestId;
        }
    }
    protected string $requestAddr {
        get {
            return $this->requestAddr;
        }
    }
    protected string $requestHost {
        get {
            return $this->requestHost;
        }
    }
    protected string $requestMethod {
        get {
            return $this->requestMethod;
        }
    }
    protected string $requestUserAgent {
        get {
            return $this->requestUserAgent;
        }
    }
    protected string $actorName {
        get {
            return $this->actorName;
        }
    }
    protected string $sourceAddr {
        get {
            return $this->sourceAddr;
        }
    }
    protected string $sourceInstanceId {
        get {
            return $this->sourceInstanceId;
        }
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
