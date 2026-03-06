<?php

namespace Cainy\Dockhand;

use Cainy\Dockhand\Auth\ApiKeyAuthenticator;
use Cainy\Dockhand\Auth\BasicAuthenticator;
use Cainy\Dockhand\Auth\BearerTokenAuthenticator;
use Cainy\Dockhand\Auth\JwtAuthenticator;
use Cainy\Dockhand\Auth\NullAuthenticator;
use Cainy\Dockhand\Contracts\Authenticator;
use Cainy\Dockhand\Drivers\AbstractRegistryDriver;
use Cainy\Dockhand\Drivers\DistributionDriver;
use Cainy\Dockhand\Drivers\ZotDriver;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class DockhandManager
{
    /** @var array<string, AbstractRegistryDriver> */
    protected array $connections = [];

    /**
     * Get the default connection name.
     */
    public function getDefaultDriver(): string
    {
        /** @var string $default */
        $default = config('dockhand.default', 'default');

        return $default;
    }

    /**
     * Get a named connection instance.
     */
    public function connection(?string $name = null): AbstractRegistryDriver
    {
        $name = $name ?: $this->getDefaultDriver();

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->resolve($name);
        }

        return $this->connections[$name];
    }

    /**
     * Get a typed ZotDriver instance.
     *
     * @throws InvalidArgumentException If the connection is not a ZotDriver.
     */
    public function zot(?string $name = null): ZotDriver
    {
        $connection = $this->connection($name);

        if (!$connection instanceof ZotDriver) {
            $name = $name ?: $this->getDefaultDriver();
            throw new InvalidArgumentException("Connection \"{$name}\" is not a Zot driver.");
        }

        return $connection;
    }

    /**
     * Get a typed DistributionDriver instance.
     *
     * @throws InvalidArgumentException If the connection is not a DistributionDriver.
     */
    public function distribution(?string $name = null): DistributionDriver
    {
        $connection = $this->connection($name);

        if (!$connection instanceof DistributionDriver) {
            $name = $name ?: $this->getDefaultDriver();
            throw new InvalidArgumentException("Connection \"{$name}\" is not a Distribution driver.");
        }

        return $connection;
    }

    /**
     * Resolve a connection by name.
     */
    protected function resolve(string $name): AbstractRegistryDriver
    {
        /** @var array<string, mixed>|null $config */
        $config = config("dockhand.connections.{$name}");

        if ($config === null) {
            throw new InvalidArgumentException("Dockhand connection [{$name}] is not configured.");
        }

        /** @var string $driver */
        $driver = $config['driver'] ?? 'distribution';
        /** @var array<string, mixed> $authConfig */
        $authConfig = $config['auth'] ?? [];
        /** @var array<string, mixed> $loggingConfig */
        $loggingConfig = $config['logging'] ?? [];
        $auth = $this->resolveAuthenticator($authConfig);
        $logger = $this->resolveLogger($loggingConfig);
        /** @var string $baseUrl */
        $baseUrl = $config['base_uri'] ?? 'http://localhost:5000/v2/';

        /** @var int $extensionCacheTtl */
        $extensionCacheTtl = $config['extension_cache_ttl'] ?? 300;

        return match ($driver) {
            'distribution' => new DistributionDriver($baseUrl, $auth, $logger),
            'zot' => new ZotDriver($baseUrl, $auth, $logger, $extensionCacheTtl),
            default => throw new InvalidArgumentException("Unsupported Dockhand driver [{$driver}]."),
        };
    }

    /**
     * Resolve an authenticator from config.
     *
     * @param array<string, mixed> $config
     */
    protected function resolveAuthenticator(array $config): Authenticator
    {
        /** @var string $driver */
        $driver = $config['driver'] ?? 'null';

        /** @var non-empty-string $authorityName */
        $authorityName = $config['authority_name'] ?? 'auth';
        /** @var non-empty-string $registryName */
        $registryName = $config['registry_name'] ?? 'registry';

        /** @var non-empty-string $jwtPrivateKey */
        $jwtPrivateKey = $config['jwt_private_key'] ?? '';
        /** @var non-empty-string $jwtPublicKey */
        $jwtPublicKey = $config['jwt_public_key'] ?? '';
        /** @var string $username */
        $username = $config['username'] ?? '';
        /** @var string $password */
        $password = $config['password'] ?? '';
        /** @var string $token */
        $token = $config['token'] ?? '';
        /** @var string $apiKey */
        $apiKey = $config['api_key'] ?? '';

        return match ($driver) {
            'jwt' => new JwtAuthenticator($authorityName, $registryName, $jwtPrivateKey, $jwtPublicKey),
            'basic' => new BasicAuthenticator($username, $password),
            'bearer' => new BearerTokenAuthenticator($token),
            'apikey' => new ApiKeyAuthenticator($apiKey),
            'null', 'none' => new NullAuthenticator,
            default => throw new InvalidArgumentException("Unsupported Dockhand auth driver [{$driver}]."),
        };
    }

    /**
     * Resolve a logger from config.
     *
     * @param array<string, mixed> $config
     */
    protected function resolveLogger(array $config): LoggerInterface
    {
        /** @var string|null $driver */
        $driver = $config['driver'] ?? null;

        if ($driver === null) {
            return new NullLogger;
        }

        return Log::driver($driver);
    }

    /**
     * Disconnect a named connection (useful for testing or long-running workers).
     */
    public function disconnect(?string $name = null): void
    {
        $name = $name ?: $this->getDefaultDriver();
        unset($this->connections[$name]);
    }

    /**
     * Forward calls to the default connection.
     *
     * @param array<int, mixed> $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->connection()->$method(...$parameters);
    }
}
