<?php

use Cainy\Dockhand\Auth\ApiKeyAuthenticator;
use Cainy\Dockhand\Auth\BearerTokenAuthenticator;
use Illuminate\Support\Facades\Http;

it('extends BearerTokenAuthenticator', function () {
    $auth = new ApiKeyAuthenticator('api-key-123');
    expect($auth)->toBeInstanceOf(BearerTokenAuthenticator::class);
});

it('adds bearer token header with api key', function () {
    Http::fake(['*' => Http::response('ok')]);

    $auth = new ApiKeyAuthenticator('api-key-123');
    $request = Http::baseUrl('http://localhost');
    $result = $auth->authenticate($request, 'read', 'repo');

    $response = $result->get('/test');
    $sentRequest = Http::recorded()[0][0];

    $authHeader = $sentRequest->header('Authorization')[0];
    expect($authHeader)->toBe('Bearer api-key-123');
});
