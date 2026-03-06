# Laravel Dockhand

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cainy/laravel-dockhand.svg?style=flat-square)](https://packagist.org/packages/cainy/laravel-dockhand)
[![Total Downloads](https://img.shields.io/packagist/dt/cainy/laravel-dockhand.svg?style=flat-square)](https://packagist.org/packages/cainy/laravel-dockhand)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/cainydev/laravel-dockhand/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/cainydev/laravel-dockhand/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/cainydev/laravel-dockhand/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/cainydev/laravel-dockhand/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![GitHub PHPStan Action Status](https://img.shields.io/github/actions/workflow/status/cainydev/laravel-dockhand/phpstan.yml?branch=main&label=phpstan&style=flat-square)](https://github.com/cainydev/laravel-dockhand/actions?query=workflow%3APHPStan+branch%3Amain)
[![codecov](https://codecov.io/gh/cainydev/laravel-dockhand/branch/main/graph/badge.svg)](https://codecov.io/gh/cainydev/laravel-dockhand)

A Laravel package for interacting with container registries following the [OCI Distribution Specification](https://github.com/opencontainers/distribution-spec).

## Requirements

- PHP 8.4+
- Laravel 10, 11, 12, or 13

## Installation

Install the package via Composer:

```bash
composer require cainy/laravel-dockhand
```

Publish the config file:

```bash
php artisan vendor:publish --tag="dockhand-config"
```

## Configuration

Dockhand uses a multi-connection architecture. Each connection has its own driver, base URI, authentication, and logging configuration.

### Supported Drivers

| Driver | Description |
|---|---|
| `distribution` | Standard OCI Distribution registry (Docker Registry, Harbor, etc.) |
| `zot` | [Zot](https://zotregistry.dev/) registry with extension support (search, user preferences, tag deletion) |

### Supported Auth Drivers

| Auth Driver | Description |
|---|---|
| `jwt` | ECDSA JWT token authentication (for token-based registries) |
| `basic` | HTTP Basic authentication |
| `bearer` | Static Bearer token |
| `apikey` | API key authentication |
| `null` | No authentication |

### Default Connection

The default connection is configured via environment variables:

```dotenv
DOCKHAND_CONNECTION=default
DOCKHAND_DRIVER=distribution
DOCKHAND_BASE_URI=http://localhost:5000/v2/
DOCKHAND_AUTH_DRIVER=jwt
DOCKHAND_PRIVATE_KEY=/path/to/private_key.pem
DOCKHAND_PUBLIC_KEY=/path/to/public_key.pem
DOCKHAND_AUTHORITY_NAME=my_auth
DOCKHAND_REGISTRY_NAME=my_registry
DOCKHAND_LOG_DRIVER=stack
```

### Multiple Connections

Define additional connections in `config/dockhand.php`:

```php
'connections' => [
    'default' => [
        'driver' => env('DOCKHAND_DRIVER', 'distribution'),
        'base_uri' => env('DOCKHAND_BASE_URI', 'http://localhost:5000/v2/'),
        'logging' => [
            'driver' => env('DOCKHAND_LOG_DRIVER', 'stack'),
        ],
        'auth' => [
            'driver' => env('DOCKHAND_AUTH_DRIVER', 'jwt'),
            'jwt_private_key' => env('DOCKHAND_PRIVATE_KEY'),
            'jwt_public_key' => env('DOCKHAND_PUBLIC_KEY'),
            'authority_name' => env('DOCKHAND_AUTHORITY_NAME', 'auth'),
            'registry_name' => env('DOCKHAND_REGISTRY_NAME', 'registry'),
        ],
    ],

    'staging' => [
        'driver' => 'zot',
        'base_uri' => env('ZOT_STAGING_BASE_URI', 'http://localhost:5050/v2/'),
        'logging' => [
            'driver' => env('ZOT_STAGING_LOG_DRIVER', 'stack'),
        ],
        'auth' => [
            'driver' => 'basic',
            'username' => env('ZOT_STAGING_USERNAME'),
            'password' => env('ZOT_STAGING_PASSWORD'),
        ],
    ],

    'prod' => [
        'driver' => 'zot',
        'base_uri' => env('ZOT_PROD_BASE_URI'),
        'logging' => [
            'driver' => env('ZOT_PROD_LOG_DRIVER', 'stack'),
        ],
        'auth' => [
            'driver' => 'apikey',
            'api_key' => env('ZOT_PROD_API_KEY'),
        ],
    ],
],
```

## Usage

### Basic Usage

```php
use Cainy\Dockhand\Facades\Dockhand;

// Check if the registry is online
Dockhand::isOnline(); // bool

// Get the API version
Dockhand::getApiVersion(); // RegistryApiVersion enum
```

### Repositories & Tags

```php
// List all repositories
$repos = Dockhand::getRepositories();

// With pagination
$page = Dockhand::getRepositories(limit: 10);
// $page is a PaginatedResult when limit is set
$page->items;    // Collection<int, string>
$page->hasMore(); // bool
$page->nextUrl;  // ?string — pass to next request

// List tags of a repository
$tags = Dockhand::getTagsOfRepository('library/nginx');

// With pagination
$page = Dockhand::getTagsOfRepository('library/nginx', limit: 20);
```

### Manifests

```php
// Get a manifest (returns ImageManifest or ManifestList depending on content)
$manifest = Dockhand::getManifest('library/nginx', 'latest');

if ($manifest->isManifestList()) {
    // ManifestList — multi-platform image
    $entry = $manifest->findManifestListEntryByPlatform(
        Platform::create('linux', 'amd64')
    );

    // Fetch the platform-specific manifest from an entry
    $imageManifest = Dockhand::getManifestFromManifestListEntry($entry);
} else {
    // ImageManifest — single-platform image
    $imageManifest = $manifest;
}

// Access manifest properties
$imageManifest->digest;
$imageManifest->config;  // ImageConfigDescriptor
$imageManifest->layers;  // Collection<int, ImageLayerDescriptor>
$imageManifest->getSize();

// Head request (lightweight — returns digest, content length, media type)
$head = Dockhand::headManifest('library/nginx', 'latest');
$head->digest;
$head->contentLength;
$head->mediaType;

// Push a manifest
$result = Dockhand::putManifest('library/nginx', 'latest', $manifest);
$result->digest;
$result->location;
```

### Blobs

```php
// Download a blob
$data = Dockhand::getBlob('library/nginx', 'sha256:abc123...');

// Get blob size without downloading
$size = Dockhand::getBlobSize('library/nginx', 'sha256:abc123...');

// Get parsed image config from a manifest's config descriptor
$config = Dockhand::getImageConfigFromDescriptor($imageManifest->config);
$config->platform;  // Platform
$config->created;   // Carbon
```

### Blob Uploads

```php
// Monolithic upload (single request)
$result = Dockhand::uploadBlob('library/nginx', $data, $digest);

// Chunked upload
$upload = Dockhand::initiateBlobUpload('library/nginx');
$upload = Dockhand::uploadBlobChunk($upload, $chunk1);
$upload = Dockhand::uploadBlobChunk($upload, $chunk2);
$result = Dockhand::completeBlobUpload($upload, $digest);

// Mount a blob from another repository (avoids re-uploading)
$result = Dockhand::mountBlob('library/nginx', $digest, 'library/alpine');

// Check upload status / cancel
$upload = Dockhand::getBlobUploadStatus('library/nginx', $uuid);
Dockhand::cancelBlobUpload('library/nginx', $uuid);
```

### Deletion

```php
// Delete a manifest by digest
Dockhand::deleteManifest('library/nginx', 'sha256:abc123...');

// Delete a blob
Dockhand::deleteBlob('library/nginx', 'sha256:abc123...');
```

> **Note:** Tag deletion (by tag name instead of digest) is only supported by the Zot driver. See [Zot Driver Extensions](#zot-driver-extensions).

### Multiple Connections

```php
use Cainy\Dockhand\Facades\Dockhand;

// Use a specific connection
$repos = Dockhand::connection('staging')->getRepositories();

// Typed accessors (throws if the connection's driver doesn't match)
$zot = Dockhand::zot('staging');        // ZotDriver
$dist = Dockhand::distribution();       // DistributionDriver (default connection)

// Release a connection (useful in long-running workers)
Dockhand::disconnect('staging');
```

### Zot Driver Extensions

The Zot driver provides additional features beyond the standard OCI Distribution spec.

```php
$zot = Dockhand::zot('staging');

// Discover available extensions
$extensions = $zot->discoverExtensions();

// Search repositories via GraphQL
$results = $zot->search('{ GlobalSearch(query: "nginx") { ... } }');

// Search CVEs for an image
$cves = $zot->searchCVE('library/nginx', 'latest');

// User preferences
$zot->starRepository('library/nginx');
$zot->unstarRepository('library/nginx');
$zot->bookmarkRepository('library/nginx');
$zot->unbookmarkRepository('library/nginx');

// Tag deletion (not supported by standard distribution registries)
$zot->deleteManifest('library/nginx', 'latest');
```

## Authentication

### JWT (ECDSA)

JWT authentication is designed for token-based registry auth as described in the [Docker Token Authentication Specification](https://distribution.github.io/distribution/spec/auth/token/). Dockhand acts as the token authority — it signs JWTs with your private key, and the registry validates them using the corresponding public key.

Generate an ECDSA key pair:

```bash
# Generate private key
openssl ecparam -genkey -name prime256v1 -noout -out private_key.pem

# Extract public key
openssl ec -in private_key.pem -pubout -out public_key.pem
```

Configure the connection:

```dotenv
DOCKHAND_AUTH_DRIVER=jwt
DOCKHAND_PRIVATE_KEY=/path/to/private_key.pem
DOCKHAND_PUBLIC_KEY=/path/to/public_key.pem
DOCKHAND_AUTHORITY_NAME=my_auth
DOCKHAND_REGISTRY_NAME=my_registry
```

The `authority_name` must match the `issuer` in the registry config, and `registry_name` must match the `service`.

### Basic Auth

```php
'auth' => [
    'driver' => 'basic',
    'username' => env('REGISTRY_USERNAME'),
    'password' => env('REGISTRY_PASSWORD'),
],
```

### Bearer Token

```php
'auth' => [
    'driver' => 'bearer',
    'token' => env('REGISTRY_TOKEN'),
],
```

### API Key

```php
'auth' => [
    'driver' => 'apikey',
    'api_key' => env('REGISTRY_API_KEY'),
],
```

### No Auth

```php
'auth' => [
    'driver' => 'null',
],
```

## Webhook Notifications

Dockhand can receive and dispatch [registry notification events](https://distribution.github.io/distribution/about/notifications/) as Laravel events.

### Setup

1. Enable notifications in your `.env`:

```dotenv
DOCKHAND_NOTIFICATIONS_ENABLED=true
DOCKHAND_NOTIFICATIONS_ROUTE=/dockhand/notify
```

2. Generate a notification token:

```bash
php artisan dockhand:notify-token
```

3. Configure your registry to send notifications to Dockhand:

```yaml
notifications:
    endpoints:
        -   name: EventListener
            url: http://your-app.test/dockhand/notify
            headers:
                Authorization: [ "Bearer <your-notify-token>" ]
            timeout: 500ms
            threshold: 5
            backoff: 1s
            ignore:
                actions:
                    - pull
```

### Available Events

Events extend one of two base classes depending on whether the target still exists:

| Event | Extends | Trigger |
|---|---|---|
| `ManifestPushedEvent` | `RegistryEvent` | A manifest was pushed |
| `ManifestPulledEvent` | `RegistryEvent` | A manifest was pulled |
| `BlobPushedEvent` | `RegistryEvent` | A blob was pushed |
| `BlobPulledEvent` | `RegistryEvent` | A blob was pulled |
| `BlobMountedEvent` | `RegistryEvent` | A blob was mounted from another repository |
| `ManifestDeletedEvent` | `RegistryBaseEvent` | A manifest was deleted |
| `BlobDeletedEvent` | `RegistryBaseEvent` | A blob was deleted |
| `TagDeletedEvent` | `RegistryBaseEvent` | A tag was deleted |
| `RepoDeletedEvent` | `RegistryBaseEvent` | A repository was deleted |

### Event Properties

All events (`RegistryBaseEvent`) have:

| Property | Type |
|---|---|
| `$id` | `string` |
| `$timestamp` | `Carbon` |
| `$action` | `EventAction` (pull, push, mount, delete) |
| `$targetDigest` | `?string` |
| `$targetRepository` | `string` |
| `$requestId` | `string` |
| `$requestAddr` | `string` |
| `$requestHost` | `string` |
| `$requestMethod` | `string` |
| `$requestUserAgent` | `string` |
| `$actorName` | `?string` |
| `$sourceAddr` | `string` |
| `$sourceInstanceId` | `string` |

Events extending `RegistryEvent` additionally have:

| Property | Type |
|---|---|
| `$targetMediaType` | `MediaType` |
| `$targetSize` | `int` |
| `$targetUrl` | `string` |
| `$targetTag` | `?string` |

### Listening to Events

```php
use Cainy\Dockhand\Events\ManifestPushedEvent;

class ManifestPushedListener
{
    public function handle(ManifestPushedEvent $event): void
    {
        $repo = $event->targetRepository;
        $digest = $event->targetDigest;
        $tag = $event->targetTag;

        // Handle the pushed manifest...
    }
}
```

## Token & Scope Helpers

Dockhand provides facades for building JWT tokens and registry scopes, useful when implementing custom token endpoints or testing.

### Scope Facade

```php
use Cainy\Dockhand\Facades\Scope;

// Create from a registry scope string
$scope = Scope::fromString('repository:library/nginx:pull,push');

// Fluent builder
$scope = Scope::repository('library/nginx')->allowPull()->allowPush();
$scope = Scope::readRepository('library/nginx');   // pull only
$scope = Scope::writeRepository('library/nginx');  // push only
$scope = Scope::catalog()->allowPull();            // catalog access

$scope->hasPull();   // bool
$scope->hasPush();   // bool
$scope->hasDelete(); // bool
$scope->toString();  // "repository:library/nginx:pull,push"
```

### Token Facade

```php
use Cainy\Dockhand\Facades\Token;
use Cainy\Dockhand\Facades\Scope;

$token = Token::issuedBy('my_auth')
    ->permittedFor('my_registry')
    ->relatedTo('username')
    ->expiresAt(now()->addMinutes(5))
    ->withScope(Scope::readRepository('library/nginx'))
    ->sign();

$jwt = $token->toString();
```

### TokenService Facade

For low-level token operations:

```php
use Cainy\Dockhand\Facades\TokenService;

$builder = TokenService::getBuilder();
$token = TokenService::signToken($builder);
$valid = TokenService::validateToken($jwt, function ($token) {
    // Additional validation logic
});
```

## Example Registry Configuration

A minimal example pairing Dockhand with a Distribution registry (e.g., in Docker Compose):

```dotenv
DOCKHAND_PUBLIC_KEY=/path/to/public_key.pem
DOCKHAND_PRIVATE_KEY=/path/to/private_key.pem
DOCKHAND_BASE_URI=http://registry:5000/v2
DOCKHAND_AUTHORITY_NAME=my_auth
DOCKHAND_REGISTRY_NAME=my_registry
DOCKHAND_NOTIFICATIONS_ENABLED=true
DOCKHAND_NOTIFICATIONS_ROUTE=/dockhand/notify
```

```yaml
version: 0.1
log:
    fields:
        service: registry
storage:
    cache:
        blobdescriptor: inmemory
    filesystem:
        rootdirectory: /var/lib/registry
http:
    addr: :5000
    secret: devsecret
    headers:
        X-Content-Type-Options: [ nosniff ]
auth:
    token:
        realm: http://laravel/auth/token
        service: my_registry
        issuer: my_auth
        rootcertbundle: /root/certs/cert.pem
notifications:
    endpoints:
        -   name: EventListener
            url: http://laravel/dockhand/notify
            headers:
                Authorization: [ "Bearer <notify token>" ]
            timeout: 500ms
            threshold: 5
            backoff: 1s
            ignore:
                actions:
                    - pull
health:
    storagedriver:
        enabled: true
        interval: 10s
        threshold: 3
```

## Testing

```bash
composer test
composer test-coverage
composer analyse
```

## Contributing

Contributions are welcome! Just create an issue or pull request, and I'll take a look.

## Security Vulnerabilities

If you find any security vulnerabilities, please contact me via mail at [info@techbra.in](mailto:info@techbra.in).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
