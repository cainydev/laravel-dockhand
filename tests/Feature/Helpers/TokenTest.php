<?php

use Cainy\Dockhand\Helpers\Scope;
use Cainy\Dockhand\Helpers\Token;
use Lcobucci\JWT\UnencryptedToken;

beforeEach(function () {
    $this->keys = generateEcdsaKeyPair();

    config()->set('dockhand.connections.default.auth', [
        'driver' => 'jwt',
        'authority_name' => 'auth',
        'registry_name' => 'registry',
        'jwt_private_key' => $this->keys['private'],
        'jwt_public_key' => $this->keys['public'],
    ]);

    app()->forgetInstance(\Cainy\Dockhand\Services\TokenService::class);
    app()->forgetInstance(\Cainy\Dockhand\DockhandManager::class);
});

afterEach(function () {
    cleanupKeyPair($this->keys);
});

it('creates a token via static factory', function () {
    $token = Token::create();
    expect($token)->toBeInstanceOf(Token::class);
});

it('signs and returns a token', function () {
    $token = Token::create()->sign();
    expect($token)->toBeInstanceOf(UnencryptedToken::class);
});

it('converts to string', function () {
    $tokenString = Token::create()->toString();
    expect($tokenString)->toBeString()->not->toBeEmpty();
    // JWT tokens have 3 parts
    expect(explode('.', $tokenString))->toHaveCount(3);
});

it('adds custom claims', function () {
    $token = Token::create()
        ->withClaim('custom', 'value')
        ->sign();

    expect($token->claims()->get('custom'))->toBe('value');
});

it('adds scope to access claim', function () {
    $scope = (new Scope)->readRepository('library/nginx');

    $token = Token::create()
        ->withScope($scope)
        ->sign();

    $access = $token->claims()->get('access');
    expect($access)->toBeArray()
        ->and($access[0]['name'])->toBe('library/nginx');
});

it('accumulates multiple scopes', function () {
    $scope1 = (new Scope)->readRepository('repo1');
    $scope2 = (new Scope)->writeRepository('repo2');

    $token = Token::create()
        ->withScope($scope1)
        ->withScope($scope2)
        ->sign();

    $access = $token->claims()->get('access');
    expect($access)->toHaveCount(2);
});

it('sets subject', function () {
    $token = Token::create()
        ->relatedTo('user-123')
        ->sign();

    expect($token->claims()->get('sub'))->toBe('user-123');
});

it('sets issuer', function () {
    $token = Token::create()
        ->issuedBy('my-auth')
        ->sign();

    expect($token->claims()->get('iss'))->toBe('my-auth');
});

it('sets audience', function () {
    $token = Token::create()
        ->permittedFor('my-registry')
        ->sign();

    expect($token->claims()->get('aud'))->toContain('my-registry');
});

it('sets expiration', function () {
    $token = Token::create()
        ->expiresAt(now()->addMinutes(5))
        ->sign();

    expect($token->claims()->get('exp'))->not->toBeNull();
});

it('sets not-before', function () {
    $token = Token::create()
        ->canOnlyBeUsedAfter(now()->subMinute())
        ->sign();

    expect($token->claims()->get('nbf'))->not->toBeNull();
});

it('adds custom header', function () {
    $token = Token::create()
        ->withHeader('x-custom', 'test')
        ->sign();

    expect($token->headers()->get('x-custom'))->toBe('test');
});

it('get is alias for sign', function () {
    $token = Token::create()->get();
    expect($token)->toBeInstanceOf(UnencryptedToken::class);
});

it('converts to string via __toString', function () {
    $token = Token::create();
    $string = (string) $token;
    expect($string)->toBeString()->not->toBeEmpty();
});
