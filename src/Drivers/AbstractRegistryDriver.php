<?php

namespace Cainy\Dockhand\Drivers;

use Cainy\Dockhand\Actions\ManagesBlobs;
use Cainy\Dockhand\Actions\ManagesBlobUploads;
use Cainy\Dockhand\Actions\ManagesDeletion;
use Cainy\Dockhand\Actions\ManagesManifests;
use Cainy\Dockhand\Actions\ManagesRegistry;
use Cainy\Dockhand\Actions\ManagesRepositories;
use Cainy\Dockhand\Contracts\Authenticator;
use Cainy\Dockhand\Contracts\RegistryDriver as RegistryDriverContract;
use Cainy\Dockhand\Services\RegistryRequestService;
use Illuminate\Http\Client\PendingRequest;
use Psr\Log\LoggerInterface;

abstract class AbstractRegistryDriver implements RegistryDriverContract
{
    use ManagesBlobs,
        ManagesBlobUploads,
        ManagesDeletion,
        ManagesManifests,
        ManagesRegistry,
        ManagesRepositories;

    protected string $baseUrl;

    protected RegistryRequestService $http;

    protected Authenticator $auth;

    protected LoggerInterface $logger;

    public function __construct(string $baseUrl, Authenticator $auth, LoggerInterface $logger)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->http = new RegistryRequestService($baseUrl);
        $this->auth = $auth;
        $this->logger = $logger;
    }

    /**
     * Create an authenticated request for the given action and repository.
     *
     * On 401 response, flushes cached auth state and retries once.
     */
    /**
     * @param  array<string, mixed>  $extra
     */
    public function authenticatedRequest(string $action, ?string $repository = null, array $extra = []): PendingRequest
    {
        return $this->auth->authenticate($this->request(), $action, $repository, $extra);
    }

    /**
     * Whether this driver supports deleting manifests by tag reference.
     */
    public function supportsTagDeletion(): bool
    {
        return false;
    }

    /**
     * The header name used by the registry to return content digests.
     */
    public function contentDigestHeader(): string
    {
        return 'Docker-Content-Digest';
    }

    public function logger(): LoggerInterface
    {
        return $this->logger;
    }

    public function request(): PendingRequest
    {
        return $this->http->request();
    }

    /**
     * Get the authenticator instance.
     */
    public function getAuthenticator(): Authenticator
    {
        return $this->auth;
    }

    /**
     * Get the base URL of the registry.
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getReferrers(string $repository, string $digest, ?string $artifactType = null): array
    {
        return [];
    }
}
