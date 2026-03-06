<?php

namespace Cainy\Dockhand\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class RegistryRequestService
{
    protected string $baseUri;

    protected int $timeout;

    /** @var array<string, string> */
    protected array $defaultHeaders;

    public function __construct(string $baseUri, int $timeout = 30)
    {
        $this->baseUri = $baseUri;
        $this->timeout = $timeout;
        $this->defaultHeaders = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'Laravel Dockhand PHP/1.0',
        ];
    }

    public function request(): PendingRequest
    {
        return Http::withHeaders($this->defaultHeaders)
            ->timeout($this->timeout)
            ->baseUrl($this->baseUri);
    }
}
