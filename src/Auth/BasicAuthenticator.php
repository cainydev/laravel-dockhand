<?php

namespace Cainy\Dockhand\Auth;

use Cainy\Dockhand\Contracts\Authenticator;
use Illuminate\Http\Client\PendingRequest;

class BasicAuthenticator implements Authenticator
{
    public function __construct(
        protected string $username,
        protected string $password,
    ) {}

    /** @param array<string, mixed> $extra */
    public function authenticate(PendingRequest $request, string $action, ?string $repository = null, array $extra = []): PendingRequest
    {
        return $request->withBasicAuth($this->username, $this->password);
    }

    public function flush(): void
    {
        // No cached state to flush.
    }
}
