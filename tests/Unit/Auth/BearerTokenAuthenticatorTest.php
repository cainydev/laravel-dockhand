<?php

use Cainy\Dockhand\Auth\BearerTokenAuthenticator;
use Illuminate\Support\Facades\Http;

it('adds bearer token header', function () {
    Http::fake(['*' => Http::response('ok')]);

    $auth = new BearerTokenAuthenticator('my-token-123');
    $request = Http::baseUrl('http://localhost');
    $result = $auth->authenticate($request, 'read', 'repo');

    $response = $result->get('/test');
    $sentRequest = Http::recorded()[0][0];

    $authHeader = $sentRequest->header('Authorization')[0];
    expect($authHeader)->toBe('Bearer my-token-123');
});

it('flush is a no-op', function () {
    $auth = new BearerTokenAuthenticator('token');
    $auth->flush();
    expect(true)->toBeTrue();
});
