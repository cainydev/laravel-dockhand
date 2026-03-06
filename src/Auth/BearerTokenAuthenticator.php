<?php

namespace Cainy\Dockhand\Auth;

use Cainy\Dockhand\Contracts\Authenticator;
use Illuminate\Http\Client\PendingRequest;

class BearerTokenAuthenticator implements Authenticator
{
    public function __construct(
        protected string $token,
    ) {}

    /** @param array<string, mixed> $extra */
    public function authenticate(PendingRequest $request, string $action, ?string $repository = null, array $extra = []): PendingRequest
    {
        return $request->withToken($this->token);
    }

    public function flush(): void
    {
        // No cached state to flush.
    }
}
