<?php

use Cainy\Dockhand\Auth\BasicAuthenticator;
use Illuminate\Support\Facades\Http;

it('adds basic auth header', function () {
    Http::fake(['*' => Http::response('ok')]);

    $auth = new BasicAuthenticator('user', 'pass');
    $request = Http::baseUrl('http://localhost');
    $result = $auth->authenticate($request, 'read', 'repo');

    $response = $result->get('/test');
    $sentRequest = Http::recorded()[0][0];

    expect($sentRequest->hasHeader('Authorization'))->toBeTrue();
    $authHeader = $sentRequest->header('Authorization')[0];
    expect($authHeader)->toStartWith('Basic ');
});

it('flush is a no-op', function () {
    $auth = new BasicAuthenticator('user', 'pass');
    $auth->flush();
    expect(true)->toBeTrue();
});
