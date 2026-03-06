<?php

namespace Cainy\Dockhand\Auth;

class ApiKeyAuthenticator extends BearerTokenAuthenticator
{
    public function __construct(string $apiKey)
    {
        parent::__construct($apiKey);
    }
}
