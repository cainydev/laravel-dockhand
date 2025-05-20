# Laravel Dockhand

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cainy/laravel-dockhand.svg?style=flat-square)](https://packagist.org/packages/cainy/laravel-dockhand)
[![Total Downloads](https://img.shields.io/packagist/dt/cainy/laravel-dockhand.svg?style=flat-square)](https://packagist.org/packages/cainy/laravel-dockhand)

A Laravel Package for interacting with registries following the Open Container Initiative Distribution Specification.
This package was guided by the [CNCF Distribution Documentation](https://distribution.github.io/distribution/), so
if this package's documentation is lacking, please refer to the CNCF documentation.

## Installation

You can install the package via composer:

```bash
composer require cainy/laravel-dockhand
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="dockhand-config"
```

Make sure to set up the `DOCKHAND_PUBLIC_KEY` and `DOCKHAND_PRIVATE_KEY` environment variables in your `.env` file.
Although the itself registry can be used without them (I think), this package was designed for production use and
requires the key pair for signing the JWT tokens.

## Interacting with the registry

The `\Cainy\Dockhand\Facades\Dockhand` facade is used to directly interact with the registry's HTTP API.

```php
use Cainy\Dockhand\Facades\Dockhand;

// Registry related
Dockhand::isOnline();
Dockhand::getApiVersion();

// Repository related
Dockhand::getRepositories();
Dockhand::getRepository("john/busybox");
Dockhand::getTagsOfRepository("john/busybox");

// Tag related
Dockhand::getManifestOfTag("john/busybox", "latest");

// Also works with Repository/Tag instances
$repository = Dockhand::getRepository("john/busybox");
$repository->getTags()

$tag = Dockhand::getTagsOfRepository("john/busybox")->first();
$tag->getManifest();
```

## Listening to events

### Prerequisites

Generate a notify token with `php artisan dockhand:notify-token`.
Then you can configure the notification settings in the registry configuration file:

```yaml
notifications:
    endpoints:
        -   name: EventListener
            url: http://laravel/dockhand/notify
            headers:
                Authorization: [ "Bearer <token>" ]
            timeout: 500ms
            threshold: 5
            backoff: 1s
            ignore:
                actions:
                    - pull
```

The `DOCKHAND_NOTIFICATIONS_ENABLED` environment variable has to be set to `true` and the `DOCKHAND_NOTIFICATIONS_URL`
has to match the one in the configuration file.

### Available events

- `ManifestPushedEvent`
- `ManifestPulledEvent`
- `ManifestDeletedEvent`
- `BlobPushedEvent`
- `BlobPulledEvent`
- `BlobMountedEvent`
- `BlobDeletedEvent`
- `TagDeletedEvent`

## Example registry configuration

This is a minimal example of a registry configuration file that worked fine for me for development purposes (using
sail):

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
        realm: http://laravel/auth/token # replace with your auth server uri
        service: my_registry # replace with your DOCKHAND_REGISTRY_NAME
        issuer: my_auth # replace with your DOCKHAND_AUTHORITY_NAME
        rootcertbundle: /root/certs/cert.pem
notifications:
    endpoints:
        -   name: EventListener
            url: http://laravel/dockhand/notify # replace with your notify endpoint
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

## Contributing

Contributions are welcome! Just create an issue or pull request, and I'll take a look.

## Security Vulnerabilities

If you find any security vulnerabilities, please contact me via mail at [info@techbra.in](mailto:info@techbra.in).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
