<?php

namespace Cainy\Dockhand\Auth;

use Cainy\Dockhand\Contracts\Authenticator;
use Cainy\Dockhand\Helpers\Scope;
use Cainy\Dockhand\Services\TokenService;
use DateTimeImmutable;
use Illuminate\Http\Client\PendingRequest;

class JwtAuthenticator implements Authenticator
{
    protected TokenService $tokenService;

    /** @var non-empty-string */
    protected string $authorityName;

    /** @var non-empty-string */
    protected string $registryName;

    /** @var array<string, string> Cached tokens keyed by scope signature */
    protected array $tokenCache = [];

    /**
     * @param non-empty-string $authorityName
     * @param non-empty-string $registryName
     * @param non-empty-string $privateKeyPath
     * @param non-empty-string $publicKeyPath
     */
    public function __construct(string $authorityName, string $registryName, string $privateKeyPath, string $publicKeyPath)
    {
        $this->authorityName = $authorityName;
        $this->registryName = $registryName;
        $this->tokenService = new TokenService($privateKeyPath, $publicKeyPath);
    }

    /**
     * @param array<string, mixed> $extra
     */
    public function authenticate(PendingRequest $request, string $action, ?string $repository = null, array $extra = []): PendingRequest
    {
        $cacheKey = $this->buildCacheKey($action, $repository, $extra);

        if (!isset($this->tokenCache[$cacheKey])) {
            $this->tokenCache[$cacheKey] = $this->buildToken($action, $repository, $extra);
        }

        return $request->withToken($this->tokenCache[$cacheKey]);
    }

    public function flush(): void
    {
        $this->tokenCache = [];
    }

    public function getTokenService(): TokenService
    {
        return $this->tokenService;
    }

    /**
     * @param array<string, mixed> $extra
     */
    protected function buildCacheKey(string $action, ?string $repository, array $extra): string
    {
        /** @var string $from */
        $from = $extra['from'] ?? '';

        return implode(':', array_filter([$action, $repository, $from]));
    }

    /**
     * @param array<string, mixed> $extra
     */
    protected function buildToken(string $action, ?string $repository, array $extra): string
    {
        $scope = new Scope;
        /** @var list<array<string, mixed>> $access */
        $access = [];

        $repo = $repository ?? '';
        /** @var string $fromRepo */
        $fromRepo = $extra['from'] ?? '';

        match ($action) {
            'read' => $access[] = $scope->readRepository($repo)->toArray(),
            'write' => $access[] = $scope->writeRepository($repo)->toArray(),
            'delete' => $access[] = $scope->deleteRepository($repo)->toArray(),
            'catalog' => $access[] = $scope->catalog()->toArray(),
            'mount' => array_push(
                $access,
                $scope->readRepository($fromRepo)->toArray(),
                $scope->writeRepository($repo)->toArray(),
            ),
            'none' => null,
            default => null,
        };

        $builder = $this->tokenService->getBuilder()
            ->issuedBy($this->authorityName)
            ->permittedFor($this->registryName)
            ->expiresAt((new DateTimeImmutable)->setTimestamp(now()->addMinutes(2)->getTimestamp()))
            ->withClaim('access', $access);

        return $this->tokenService->signToken($builder)->toString();
    }
}
