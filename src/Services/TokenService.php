<?php

namespace Cainy\Dockhand\Services;

use Base32\Base32;
use Closure;
use DateTimeImmutable;
use Illuminate\Support\Facades\Log;
use Lcobucci\Clock\FrozenClock;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Ecdsa\Sha256 as ES256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Lcobucci\JWT\Validation\Validator;
use Ramsey\Uuid\Uuid;
use function now;

class TokenService
{
    /**
     * Configuration container for the JWT Builder and Parser.
     */
    protected Configuration $config;

    /**
     * Public key used for asymmetric signer. This key is also
     * used by the registry to verify the incoming requests.
     */
    protected InMemory $publicKey;

    /**
     * Private key used for asymmetric signer.
     */
    protected InMemory $privateKey;

    /**
     * The key id (kid) gets generated once and is then included
     * in the token, in order for the registry to know which
     * public key was used for signing the token.
     */
    protected string $kid;

    public function __construct(string $privateKeyPath, string $publicKeyPath)
    {
        $signer = new ES256;

        $this->privateKey = InMemory::file($privateKeyPath);
        $this->publicKey = InMemory::file($publicKeyPath);

        $this->kid = self::generateKeyId($this->privateKey);

        $this->config = Configuration::forAsymmetricSigner(
            $signer,
            $this->privateKey,
            $this->publicKey
        );
    }

    /**
     * Generate the key id (kid), which is used by the registry
     * to identify which public key to use for verification.
     */
    private static function generateKeyId(InMemory $privateKey): string
    {
        // Extract public key from private key
        $privateKey = openssl_pkey_get_private($privateKey->contents());
        $publicKeyContent = openssl_pkey_get_details($privateKey)['key'];

        // Clean pem header and footer
        $pattern = '/-----BEGIN [^-]*-----\r?\n?|-----END [^-]*-----\r?\n?/';
        $cleanedPem = trim(preg_replace($pattern, '', $publicKeyContent));

        // Convert to der
        $der = base64_decode(preg_replace('/\s+/', '', $cleanedPem));

        // Calculate digest
        $algorithm = hash_init('sha256');
        hash_update($algorithm, $der);
        $digest = hash_final($algorithm, true);

        // Shorten digest to 30 bytes
        $digest = substr($digest, 0, 30);

        // Use Base32\Base32 to encode digest
        $source = Base32::encode($digest);
        $source = str_replace('=', '', $source);

        // Format with :
        $result = [];
        for ($i = 0; $i < strlen($source); $i += 4) {
            $result[] = substr($source, $i, 4);
        }

        return implode(':', $result);
    }

    /**
     * Get a builder for constructing a token.
     */
    public function getBuilder(): Builder
    {
        return $this->config->builder()
            ->identifiedBy(Uuid::uuid4()->toString())
            ->withHeader('kid', $this->kid);
    }

    /**
     * Create and sign the token from the builder.
     */
    public function signToken(Builder $builder): UnencryptedToken
    {
        return $builder
            ->issuedAt((new DateTimeImmutable)->setTimestamp(now()->getTimestamp()))
            ->getToken($this->config->signer(), $this->config->signingKey());
    }

    /**
     * Validate a token that was issued by this service.
     */
    public function validateToken(string $token, Closure $closure): bool
    {
        $parser = new Parser(new JoseEncoder);
        $token = $parser->parse($token);

        $validator = new Validator;

        try {
            $closure($validator, $token);
            $validator->assert($token, new SignedWith($this->config->signer(), $this->config->verificationKey()));
            $validator->assert($token, new LooseValidAt(new FrozenClock(now()->toDateTimeImmutable())));

            return true;
        } catch (RequiredConstraintsViolated $e) {
            foreach ($e->violations() as $v) {
                Log::info($v);
            }

            return false;
        }
    }
}
