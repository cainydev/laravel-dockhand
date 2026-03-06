<?php

namespace Cainy\Dockhand\Helpers;

use Cainy\Dockhand\Facades\TokenService;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\UnencryptedToken;

/**
 * Token builder and general helper class. Offers a simple way to add
 * {@see \Cainy\Dockhand\Facades\Scope Scopes} (needed for registry)
 * to the tokens. Able to generate {@see UnencryptedToken}.
 */
class Token
{
    protected Builder $builder;

    /** @var array<int, mixed> */
    protected array $access;

    /**
     * Create a new TokenBuilder instance.
     *
     * @param  ?Builder  $builder  Optional custom builder instance.
     */
    final public function __construct(?Builder $builder = null)
    {
        $this->builder = $builder ?: TokenService::getBuilder();
        $this->access = [];
    }

    /**
     * Create a new Token instance.
     */
    public static function create(): static
    {
        return new static;
    }

    /**
     * Generate and return the signed JWT token as string.
     *
     * @see toString()
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Generate and return the signed JWT token as string.
     */
    public function toString(): string
    {
        return $this->sign()->toString();
    }

    /**
     * Generate and return the signed JWT token.
     */
    public function sign(): UnencryptedToken
    {
        $this->builder = $this->builder->withClaim('access', $this->access);

        return TokenService::signToken($this->builder);
    }

    /**
     * Add a custom claim.
     *
     * @param  non-empty-string  $name
     */
    public function withClaim(string $name, mixed $value): static
    {
        if ($name === 'access') {
            $this->access[] = $value;
        } else {
            $this->builder = $this->builder->withClaim($name, $value);
        }

        return $this;
    }

    /**
     * Set the subject (sub) claim.
     *
     * @param  non-empty-string  $subject
     */
    public function relatedTo(string $subject): static
    {
        $this->builder = $this->builder->relatedTo($subject);

        return $this;
    }

    /**
     * Set the issuer (iss) claim.
     *
     * @param  non-empty-string  $issuer
     */
    public function issuedBy(string $issuer): static
    {
        $this->builder = $this->builder->issuedBy($issuer);

        return $this;
    }

    /**
     * Set the audience (aud) claim.
     *
     * @param  non-empty-string  $audience
     */
    public function permittedFor(string $audience): static
    {
        $this->builder = $this->builder->permittedFor($audience);

        return $this;
    }

    /**
     * Set the expiration time (exp) claim.
     */
    public function expiresAt(Carbon $time): static
    {
        $this->builder = $this->builder->expiresAt(
            (new DateTimeImmutable)->setTimestamp($time->getTimestamp())
        );

        return $this;
    }

    /**
     * Set the "not before" (nbf) claim.
     */
    public function canOnlyBeUsedAfter(Carbon $time): static
    {
        $this->builder = $this->builder->canOnlyBeUsedAfter(
            (new DateTimeImmutable)->setTimestamp($time->getTimestamp())
        );

        return $this;
    }

    /**
     * Add a registry scope to the token.
     */
    public function withScope(Scope $scope): static
    {
        $this->access[] = $scope->toArray();

        return $this;
    }

    /**
     * Add a custom header.
     *
     * @param  non-empty-string  $name
     */
    public function withHeader(string $name, mixed $value): static
    {
        $this->builder = $this->builder->withHeader($name, $value);

        return $this;
    }

    /**
     * Generate and return the signed JWT token.
     *
     * @see sign()
     */
    public function get(): UnencryptedToken
    {
        return $this->sign();
    }
}
