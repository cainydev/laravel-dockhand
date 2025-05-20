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

## Contributing

Contributions are welcome! Just create an issue or pull request, and I'll take a look.

## Security Vulnerabilities

If you find any security vulnerabilities, please contact me via mail at [info@techbra.in](mailto:info@techbra.in).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
