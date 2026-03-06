<?php

namespace Cainy\Dockhand\Contracts;

use Illuminate\Http\Client\PendingRequest;

interface Authenticator
{
    /**
     * Authenticate a pending request for the given action and repository.
     *
     * @param PendingRequest $request The request to authenticate.
     * @param string $action One of: 'read', 'write', 'delete', 'catalog', 'none', 'mount'.
     * @param string|null $repository The target repository (null for catalog/none actions).
     * @param array<string, mixed> $extra Additional context (e.g., 'from' for mount actions).
     * @return PendingRequest The authenticated request.
     */
    public function authenticate(PendingRequest $request, string $action, ?string $repository = null, array $extra = []): PendingRequest;

    /**
     * Flush any cached authentication state (e.g., cached tokens).
     */
    public function flush(): void;
}
