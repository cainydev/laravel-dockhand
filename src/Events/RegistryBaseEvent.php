<?php

namespace Cainy\Dockhand\Events;

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

    public ?string $targetDigest {
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

    public ?string $actorName {
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
     * @param array{id: string, timestamp: string, action: string, target: array{digest?: string, repository: string}, request: array{id: string, addr: string, host: string, method: string, useragent: string}, actor?: array{name: string}, source: array{addr: string, instanceID: string}} $data Raw notification data from the registry webhook.
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->timestamp = Carbon::parse($data['timestamp']);
        $this->action = EventAction::from($data['action']);

        $target = $data['target'];
        $this->targetDigest = $target['digest'] ?? null;
        $this->targetRepository = $target['repository'];

        $request = $data['request'];
        $this->requestId = $request['id'];
        $this->requestAddr = $request['addr'];
        $this->requestHost = $request['host'];
        $this->requestMethod = $request['method'];
        $this->requestUserAgent = $request['useragent'];

        if (!empty($data['actor'])) {
            $this->actorName = $data['actor']['name'];
        } else {
            $this->actorName = null;
        }

        $source = $data['source'];
        $this->sourceAddr = $source['addr'];
        $this->sourceInstanceId = $source['instanceID'];
    }
}
