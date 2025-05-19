<?php

namespace Cainy\Dockhand\Resources;

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

    protected array $access;

    /**
     * Create a new TokenBuilder instance.
     *
     * @param ?Builder $builder Optional custom builder instance.
     */
    final public function __construct(?Builder $builder = null)
    {
        $this->builder = $builder ?: TokenService::getBuilder();
        $this->access = [];
    }

    /**
     * Create a new Token instance.
     *
     * @return static
     */
    public static function create(): static
    {
        return new static;
    }

    /**
     * Generate and return the signed JWT token as string.
     *
     * @return string
     * @see toString()
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Generate and return the signed JWT token as string.
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->sign()->toString();
    }

    /**
     * Generate and return the signed JWT token.
     *
     * @return UnencryptedToken
     */
    public function sign(): UnencryptedToken
    {
        $this->builder = $this->builder->withClaim('access', $this->access);

        return TokenService::signToken($this->builder);
    }

    /**
     * Add a custom claim.
     *
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public function withClaim(string $name, mixed $value): static
    {
        if ($name == 'access') {
            $this->access[] = $value;
        } else {
            $this->builder = $this->builder->withClaim($name, $value);
        }

        return $this;
    }

    /**
     * Set the subject (sub) claim.
     *
     * @param string $subject
     * @return static
     */
    public function relatedTo(string $subject): static
    {
        $this->builder = $this->builder->relatedTo($subject);

        return $this;
    }

    /**
     * Set the issuer (iss) claim.
     *
     * @param string $issuer
     * @return static
     */
    public function issuedBy(string $issuer): static
    {
        $this->builder = $this->builder->issuedBy($issuer);

        return $this;
    }

    /**
     * Set the audience (aud) claim.
     *
     * @param string $audience
     * @return static
     */
    public function permittedFor(string $audience): static
    {
        $this->builder = $this->builder->permittedFor($audience);

        return $this;
    }

    /**
     * Set the expiration time (exp) claim.
     *
     * @param Carbon $time
     * @return static
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
     *
     * @param Carbon $time
     * @return static
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
     *
     * @param Scope $scope
     * @return static
     */
    public function withScope(Scope $scope): static
    {
        $this->access[] = $scope->toArray();
        return $this;
    }

    /**
     * Add a custom header.
     *
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public function withHeader(string $name, mixed $value): static
    {
        $this->builder = $this->builder->withHeader($name, $value);
        return $this;
    }

    /**
     * Generate and return the signed JWT token.
     *
     * @return UnencryptedToken
     * @see sign()
     */
    public function get(): UnencryptedToken
    {
        return $this->sign();
    }
}
