<?php

use Cainy\Dockhand\Auth\JwtAuthenticator;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->keys = generateEcdsaKeyPair();
    $this->auth = new JwtAuthenticator('auth-server', 'registry', $this->keys['private'], $this->keys['public']);
});

afterEach(function () {
    cleanupKeyPair($this->keys);
});

it('adds bearer token for read action', function () {
    Http::fake(['*' => Http::response('ok')]);

    $request = Http::baseUrl('http://localhost');
    $result = $this->auth->authenticate($request, 'read', 'library/nginx');

    $response = $result->get('/test');
    $sentRequest = Http::recorded()[0][0];

    $authHeader = $sentRequest->header('Authorization')[0];
    expect($authHeader)->toStartWith('Bearer ');
});

it('caches tokens by scope', function () {
    Http::fake(['*' => Http::response('ok')]);

    $request1 = Http::baseUrl('http://localhost');
    $result1 = $this->auth->authenticate($request1, 'read', 'repo1');
    $result1->get('/test1');
    $token1 = Http::recorded()[0][0]->header('Authorization')[0];

    Http::recorded(); // reset

    $request2 = Http::baseUrl('http://localhost');
    $result2 = $this->auth->authenticate($request2, 'read', 'repo1');
    $result2->get('/test2');
    $token2 = Http::recorded()[1][0]->header('Authorization')[0];

    // Same scope should produce same token
    expect($token1)->toBe($token2);
});

it('generates different tokens for different scopes', function () {
    Http::fake(['*' => Http::response('ok')]);

    $r1 = $this->auth->authenticate(Http::baseUrl('http://localhost'), 'read', 'repo1');
    $r1->get('/test1');

    $r2 = $this->auth->authenticate(Http::baseUrl('http://localhost'), 'write', 'repo1');
    $r2->get('/test2');

    $recordings = Http::recorded();
    $token1 = $recordings[0][0]->header('Authorization')[0];
    $token2 = $recordings[1][0]->header('Authorization')[0];

    expect($token1)->not->toBe($token2);
});

it('generates mount tokens with dual scopes', function () {
    Http::fake(['*' => Http::response('ok')]);

    $request = Http::baseUrl('http://localhost');
    $result = $this->auth->authenticate($request, 'mount', 'target/repo', ['from' => 'source/repo']);
    $result->get('/test');

    $authHeader = Http::recorded()[0][0]->header('Authorization')[0];
    expect($authHeader)->toStartWith('Bearer ');
});

it('flushes token cache', function () {
    Http::fake(['*' => Http::response('ok')]);

    $r1 = $this->auth->authenticate(Http::baseUrl('http://localhost'), 'read', 'repo');
    $r1->get('/test1');
    $token1 = Http::recorded()[0][0]->header('Authorization')[0];

    $this->auth->flush();

    $r2 = $this->auth->authenticate(Http::baseUrl('http://localhost'), 'read', 'repo');
    $r2->get('/test2');
    $token2 = Http::recorded()[1][0]->header('Authorization')[0];

    // After flush, a new token should be generated (different iat/jti)
    expect($token1)->not->toBe($token2);
});

it('exposes token service', function () {
    expect($this->auth->getTokenService())->toBeInstanceOf(\Cainy\Dockhand\Services\TokenService::class);
});

it('handles catalog action', function () {
    Http::fake(['*' => Http::response('ok')]);

    $request = Http::baseUrl('http://localhost');
    $result = $this->auth->authenticate($request, 'catalog');
    $result->get('/test');

    $authHeader = Http::recorded()[0][0]->header('Authorization')[0];
    expect($authHeader)->toStartWith('Bearer ');
});

it('handles none action', function () {
    Http::fake(['*' => Http::response('ok')]);

    $request = Http::baseUrl('http://localhost');
    $result = $this->auth->authenticate($request, 'none');
    $result->get('/test');

    $authHeader = Http::recorded()[0][0]->header('Authorization')[0];
    expect($authHeader)->toStartWith('Bearer ');
});

it('handles delete action', function () {
    Http::fake(['*' => Http::response('ok')]);

    $request = Http::baseUrl('http://localhost');
    $result = $this->auth->authenticate($request, 'delete', 'repo');
    $result->get('/test');

    $authHeader = Http::recorded()[0][0]->header('Authorization')[0];
    expect($authHeader)->toStartWith('Bearer ');
});

it('handles unknown action with default match case', function () {
    Http::fake(['*' => Http::response('ok')]);

    $request = Http::baseUrl('http://localhost');
    $result = $this->auth->authenticate($request, 'unknown-action', 'repo');
    $result->get('/test');

    $authHeader = Http::recorded()[0][0]->header('Authorization')[0];
    expect($authHeader)->toStartWith('Bearer ');
});
