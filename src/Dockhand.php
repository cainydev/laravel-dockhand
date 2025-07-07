<?php

namespace Cainy\Dockhand;

use Cainy\Dockhand\Actions\ManagesBlobs;
use Cainy\Dockhand\Actions\ManagesManifests;
use Cainy\Dockhand\Actions\ManagesRegistry;
use Cainy\Dockhand\Actions\ManagesRepositories;
use Cainy\Dockhand\Services\RegistryRequestService as HttpClient;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Dockhand
{
    use ManagesManifests,
        ManagesRegistry,
        ManagesRepositories,
        ManagesBlobs;

    /**
     * The base URL of the registry.
     */
    protected string $baseUrl;

    /**
     * The name of the registry.
     */
    protected string $registryName;

    /**
     * The name of the authority.
     */
    protected string $authorityName;

    /**
     * The HTTP Client to communicate with the registry instance.
     */
    protected HttpClient $http;

    /**
     * The logger interface to be used by the Dockhand instance.
     */
    protected LoggerInterface $logger;

    /**
     * Create a new Dockhand instance.
     *
     * @return void
     */
    public function __construct(string $baseUrl, string $registryName, string $authorityName, string|LoggerInterface|null $logger = null)
    {
        $this->registryName = $registryName;
        $this->authorityName = $authorityName;
        $this->baseUrl = $baseUrl;
        $this->http = new HttpClient($baseUrl);

        if ($logger instanceof LoggerInterface) {
            $this->logger = $logger;
        } else if ($logger === null) {
            $this->logger = new NullLogger();
        } else {
            $this->logger = Log::driver($logger);
        }
    }

    /**
     * Log something.
     */
    public function logger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Make a request to the registry.
     */
    public function request(): PendingRequest
    {
        return $this->http->request();
    }

    /**
     * Transform the items of the collection to the given class.
     */
    protected function transformCollection(array $collection, string $class, array $extraData = []): array
    {
        return array_map(function ($data) use ($class, $extraData) {
            return new $class($data + $extraData, $this);
        }, $collection);
    }
}
