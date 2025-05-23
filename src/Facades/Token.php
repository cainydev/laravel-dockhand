<?php

namespace Cainy\Dockhand\Facades;

use Cainy\Dockhand\Helpers\Scope;
use Cainy\Dockhand\Helpers\Token as TokenResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Facade;
use Lcobucci\JWT\UnencryptedToken;

/**
 * Facade for the {@see TokenResource Token} class providing a clean static interface.
 *
 * This facade provides static access to the {@see TokenResource Token} class methods
 * while preserving instance-based functionality (not using singletons).
 *
 * @method static TokenResource relatedTo(string $subject): Set the subject (sub) claim.
 * @method static TokenResource issuedBy(string $issuer) Set the issuer (iss) claim.
 * @method static TokenResource permittedFor(string $audience) Set the audience (aud) claim.
 * @method static TokenResource expiresAt(Carbon $time) Set the expiration time (exp) claim.
 * @method static TokenResource canOnlyBeUsedAfter(Carbon $time) Set the "not before" (nbf) claim.
 * @method static TokenResource withClaim(string $name, mixed $value) Add a custom claim.
 * @method static TokenResource withScope(Scope $scope) Add a registry scope to the token.
 * @method static TokenResource withHeader(string $name, mixed $value) Add a custom header.
 * @method static UnencryptedToken sign() Generate and return the signed JWT token.
 * @method static UnencryptedToken get() Generate and return the signed JWT token.
 * @method static string toString() Generate and return the signed JWT token as a string.
 * @method static string __toString() Generate and return the signed JWT token as a string.
 *
 * @see TokenResource
 */
class Token extends Facade
{
    protected static function getFacadeAccessor(): TokenResource
    {
        return new TokenResource;
    }

    protected static function resolveFacadeInstance($name)
    {
        return $name instanceof \Closure ? $name() : $name;
    }
}
