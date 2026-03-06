<?php

use Cainy\Dockhand\Services\TokenService;
use Lcobucci\JWT\Builder;

beforeEach(function () {
    $this->keys = generateEcdsaKeyPair();
    $this->service = new TokenService($this->keys['private'], $this->keys['public']);
});

afterEach(function () {
    cleanupKeyPair($this->keys);
});

it('returns a builder', function () {
    $builder = $this->service->getBuilder();
    expect($builder)->toBeInstanceOf(Builder::class);
});

it('signs a token', function () {
    $builder = $this->service->getBuilder()
        ->issuedBy('test')
        ->permittedFor('registry')
        ->expiresAt((new DateTimeImmutable)->modify('+5 minutes'));

    $token = $this->service->signToken($builder);
    expect($token->toString())->toBeString()->not->toBeEmpty();
});

it('validates a valid token', function () {
    $builder = $this->service->getBuilder()
        ->issuedBy('test')
        ->permittedFor('registry')
        ->expiresAt((new DateTimeImmutable)->modify('+5 minutes'));

    $token = $this->service->signToken($builder);

    $valid = $this->service->validateToken($token->toString(), function ($validator, $token) {
        // No additional constraints
    });

    expect($valid)->toBeTrue();
});

it('rejects an expired token', function () {
    $builder = $this->service->getBuilder()
        ->issuedBy('test')
        ->permittedFor('registry')
        ->expiresAt((new DateTimeImmutable)->modify('-5 minutes'));

    $token = $this->service->signToken($builder);

    $valid = $this->service->validateToken($token->toString(), function ($validator, $token) {
        // No additional constraints
    });

    expect($valid)->toBeFalse();
});

it('rejects a token signed with different keys', function () {
    // Create a token with different keys
    $otherKeys = generateEcdsaKeyPair();
    $otherService = new TokenService($otherKeys['private'], $otherKeys['public']);

    $builder = $otherService->getBuilder()
        ->issuedBy('test')
        ->permittedFor('registry')
        ->expiresAt((new DateTimeImmutable)->modify('+5 minutes'));

    $otherToken = $otherService->signToken($builder);

    // Validate with original service's keys - should fail
    $valid = $this->service->validateToken($otherToken->toString(), function ($validator, $token) {
        // No additional constraints
    });

    expect($valid)->toBeFalse();

    cleanupKeyPair($otherKeys);
});

it('builder includes kid header', function () {
    $builder = $this->service->getBuilder()
        ->issuedBy('test')
        ->permittedFor('registry')
        ->expiresAt((new DateTimeImmutable)->modify('+5 minutes'));

    $token = $this->service->signToken($builder);
    $headers = $token->headers();

    expect($headers->get('kid'))->toBeString()->not->toBeEmpty();
});
