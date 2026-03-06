<?php

namespace Cainy\Dockhand\Auth;

use Cainy\Dockhand\Contracts\Authenticator;
use Illuminate\Http\Client\PendingRequest;

class NullAuthenticator implements Authenticator
{
    /** @param array<string, mixed> $extra */
    public function authenticate(PendingRequest $request, string $action, ?string $repository = null, array $extra = []): PendingRequest
    {
        return $request;
    }

    public function flush(): void
    {
        // Nothing to flush.
    }
}
