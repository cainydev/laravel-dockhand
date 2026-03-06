<?php

use Cainy\Dockhand\Events\BlobMountedEvent;
use Cainy\Dockhand\Events\BlobPushedEvent;
use Cainy\Dockhand\Events\ManifestDeletedEvent;
use Cainy\Dockhand\Events\ManifestPulledEvent;
use Cainy\Dockhand\Events\ManifestPushedEvent;
use Cainy\Dockhand\Events\TagDeletedEvent;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    // Enable notifications and configure JWT for token validation
    $this->keys = generateEcdsaKeyPair();

    config()->set('dockhand.notifications.enabled', true);
    config()->set('dockhand.notifications.route', '/dockhand/notify');
    config()->set('dockhand.connections.default.auth', [
        'driver' => 'jwt',
        'authority_name' => 'auth',
        'registry_name' => 'registry',
        'jwt_private_key' => $this->keys['private'],
        'jwt_public_key' => $this->keys['public'],
    ]);

    // Re-resolve to pick up new config
    app()->forgetInstance(\Cainy\Dockhand\Services\TokenService::class);
    app()->forgetInstance(\Cainy\Dockhand\DockhandManager::class);

    // The notifications route is loaded at boot time via package config.
    // We need to re-register it since we changed the config after boot.
    $routePath = __DIR__.'/../../../routes/notifications.php';
    if (file_exists($routePath)) {
        \Illuminate\Support\Facades\Route::middleware('api')->group($routePath);
    }
});

afterEach(function () {
    cleanupKeyPair($this->keys);
});

function generateNotifyToken(): string
{
    $service = app(\Cainy\Dockhand\Services\TokenService::class);
    $builder = $service->getBuilder()
        ->issuedBy('auth')
        ->permittedFor('registry')
        ->expiresAt((new DateTimeImmutable)->modify('+5 minutes'))
        ->withClaim('access', ['notify']);

    return $service->signToken($builder)->toString();
}

function sampleEventPayload(string $action = 'push', string $mediaType = 'application/vnd.docker.distribution.manifest.v2+json', array $overrides = []): array
{
    return array_merge([
        'id' => 'evt-'.uniqid(),
        'timestamp' => now()->toIso8601String(),
        'action' => $action,
        'target' => [
            'mediaType' => $mediaType,
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

it('rejects requests without bearer token', function () {
    $response = $this->postJson('/dockhand/notify', [
        'events' => [sampleEventPayload()],
    ]);

    $response->assertStatus(500); // UnauthorizedException
});

it('dispatches ManifestPushedEvent on manifest push', function () {
    Event::fake([ManifestPushedEvent::class]);

    $token = generateNotifyToken();
    $response = $this->postJson('/dockhand/notify', [
        'events' => [sampleEventPayload('push', 'application/vnd.docker.distribution.manifest.v2+json')],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(202);
    Event::assertDispatched(ManifestPushedEvent::class);
});

it('dispatches BlobPushedEvent on layer push', function () {
    Event::fake([BlobPushedEvent::class]);

    $token = generateNotifyToken();
    $response = $this->postJson('/dockhand/notify', [
        'events' => [sampleEventPayload('push', 'application/vnd.oci.image.layer.v1.tar+gzip')],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(202);
    Event::assertDispatched(BlobPushedEvent::class);
});

it('dispatches ManifestPulledEvent on manifest pull', function () {
    Event::fake([ManifestPulledEvent::class]);

    $token = generateNotifyToken();
    $response = $this->postJson('/dockhand/notify', [
        'events' => [sampleEventPayload('pull', 'application/vnd.docker.distribution.manifest.v2+json')],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(202);
    Event::assertDispatched(ManifestPulledEvent::class);
});

it('dispatches ManifestDeletedEvent on manifest delete', function () {
    Event::fake([ManifestDeletedEvent::class]);

    $token = generateNotifyToken();
    $event = sampleEventPayload('delete', 'application/vnd.docker.distribution.manifest.v2+json');
    unset($event['target']['tag']); // delete by digest, not tag

    $response = $this->postJson('/dockhand/notify', [
        'events' => [$event],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(202);
    Event::assertDispatched(ManifestDeletedEvent::class);
});

it('dispatches TagDeletedEvent on tag delete', function () {
    Event::fake([TagDeletedEvent::class]);

    $token = generateNotifyToken();
    $event = sampleEventPayload('delete', 'application/vnd.docker.distribution.manifest.v2+json');
    $event['target']['tag'] = 'v1.0';

    $response = $this->postJson('/dockhand/notify', [
        'events' => [$event],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(202);
    Event::assertDispatched(TagDeletedEvent::class);
});

it('dispatches BlobMountedEvent on mount action', function () {
    Event::fake([BlobMountedEvent::class]);

    $token = generateNotifyToken();
    $response = $this->postJson('/dockhand/notify', [
        'events' => [sampleEventPayload('mount', 'application/vnd.oci.image.layer.v1.tar+gzip')],
    ], ['Authorization' => "Bearer {$token}"]);

    $response->assertStatus(202);
    Event::assertDispatched(BlobMountedEvent::class);
});
