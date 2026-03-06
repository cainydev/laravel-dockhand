<?php

use Cainy\Dockhand\Events\BlobPushedEvent;
use Cainy\Dockhand\Events\ManifestPushedEvent;
use Cainy\Dockhand\Events\EventAction;
use Cainy\Dockhand\Enums\MediaType;

function buildEventData(array $overrides = []): array
{
    return array_merge([
        'id' => 'evt-123',
        'timestamp' => '2024-01-15T10:30:00Z',
        'action' => 'push',
        'target' => [
            'mediaType' => 'application/vnd.docker.distribution.manifest.v2+json',
            'size' => 1024,
            'digest' => 'sha256:abc123',
            'repository' => 'library/nginx',
            'url' => 'http://registry/v2/library/nginx/manifests/sha256:abc123',
            'tag' => 'latest',
        ],
        'request' => [
            'id' => 'req-123',
            'addr' => '127.0.0.1',
            'host' => 'registry.example.com',
            'method' => 'PUT',
            'useragent' => 'docker/20.10',
        ],
        'actor' => ['name' => 'admin'],
        'source' => [
            'addr' => 'registry:5000',
            'instanceID' => 'instance-abc',
        ],
    ], $overrides);
}

it('parses base event properties', function () {
    $data = buildEventData();
    $event = new ManifestPushedEvent($data);

    expect($event->id)->toBe('evt-123')
        ->and($event->action)->toBe(EventAction::PUSH)
        ->and($event->targetRepository)->toBe('library/nginx')
        ->and($event->targetDigest)->toBe('sha256:abc123')
        ->and($event->requestId)->toBe('req-123')
        ->and($event->requestAddr)->toBe('127.0.0.1')
        ->and($event->requestHost)->toBe('registry.example.com')
        ->and($event->requestMethod)->toBe('PUT')
        ->and($event->requestUserAgent)->toBe('docker/20.10')
        ->and($event->actorName)->toBe('admin')
        ->and($event->sourceAddr)->toBe('registry:5000')
        ->and($event->sourceInstanceId)->toBe('instance-abc');
});

it('sets actorName to null when actor is empty', function () {
    $data = buildEventData();
    unset($data['actor']);
    $event = new ManifestPushedEvent($data);

    expect($event->actorName)->toBeNull();
});

it('parses registry event properties', function () {
    $data = buildEventData();
    $event = new ManifestPushedEvent($data);

    expect($event->targetMediaType)->toBe(MediaType::IMAGE_MANIFEST_V2)
        ->and($event->targetSize)->toBe(1024)
        ->and($event->targetUrl)->toBe('http://registry/v2/library/nginx/manifests/sha256:abc123')
        ->and($event->targetTag)->toBe('latest');
});

it('sets targetTag to null when tag is missing', function () {
    $data = buildEventData();
    unset($data['target']['tag']);
    $event = new ManifestPushedEvent($data);

    expect($event->targetTag)->toBeNull();
});

it('handles nullable targetDigest', function () {
    $data = buildEventData();
    unset($data['target']['digest']);
    $data['target']['digest'] = null;
    $event = new ManifestPushedEvent($data);

    expect($event->targetDigest)->toBeNull();
});
