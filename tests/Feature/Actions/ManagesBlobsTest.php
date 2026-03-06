<?php

use Cainy\Dockhand\Auth\NullAuthenticator;
use Cainy\Dockhand\Drivers\DistributionDriver;
use Cainy\Dockhand\Enums\MediaType;
use Cainy\Dockhand\Resources\ImageConfig;
use Cainy\Dockhand\Resources\ImageConfigDescriptor;
use Illuminate\Support\Facades\Http;
use Psr\Log\NullLogger;

beforeEach(function () {
    $this->driver = new DistributionDriver('http://localhost:5000/v2', new NullAuthenticator, new NullLogger);
});

it('gets a blob', function () {
    Http::fake([
        'localhost:5000/v2/repo/blobs/sha256:abc*' => Http::response('blob-content', 200, [
            'Docker-Content-Digest' => 'sha256:abc',
        ]),
    ]);

    $result = $this->driver->getBlob('repo', 'sha256:abc');
    expect($result)->toBe('blob-content');
});

it('returns null for 404 on getBlob', function () {
    Http::fake([
        'localhost:5000/v2/repo/blobs/*' => Http::response('', 404),
    ]);

    expect($this->driver->getBlob('repo', 'sha256:missing'))->toBeNull();
});

it('throws on server error for getBlob', function () {
    Http::fake([
        'localhost:5000/v2/repo/blobs/*' => Http::response('error', 500),
    ]);

    $this->driver->getBlob('repo', 'sha256:err');
})->throws(Exception::class, 'Failed to fetch blob');

it('uses ETag fallback for blob digest', function () {
    Http::fake([
        'localhost:5000/v2/repo/blobs/sha256:abc*' => Http::response('content', 200, [
            'ETag' => '"sha256:etag"',
        ]),
    ]);

    $result = $this->driver->getBlob('repo', 'sha256:abc');
    expect($result)->toBe('content');
});

it('throws when no digest header for blob', function () {
    Http::fake([
        'localhost:5000/v2/repo/blobs/sha256:abc*' => Http::response('content', 200),
    ]);

    $this->driver->getBlob('repo', 'sha256:abc');
})->throws(Exception::class, 'did not provide');

it('gets blob size', function () {
    Http::fake([
        'localhost:5000/v2/repo/blobs/sha256:abc*' => Http::response('', 200, [
            'Content-Length' => '4096',
        ]),
    ]);

    $result = $this->driver->getBlobSize('repo', 'sha256:abc');
    expect($result)->toBe(4096);
});

it('returns null for 404 on getBlobSize', function () {
    Http::fake([
        'localhost:5000/v2/repo/blobs/*' => Http::response('', 404),
    ]);

    expect($this->driver->getBlobSize('repo', 'sha256:missing'))->toBeNull();
});

it('gets image config from descriptor', function () {
    Http::fake([
        'localhost:5000/v2/repo/blobs/sha256:cfg*' => Http::response(sampleImageConfigData(), 200, [
            'Docker-Content-Digest' => 'sha256:cfg',
        ]),
    ]);

    $descriptor = new ImageConfigDescriptor('repo', 'sha256:cfg', MediaType::IMAGE_CONFIG_V1, 500);
    $result = $this->driver->getImageConfigFromDescriptor($descriptor);

    expect($result)->toBeInstanceOf(ImageConfig::class)
        ->and($result->platform->os)->toBe('linux');
});

it('returns null for 404 on getImageConfigFromDescriptor', function () {
    Http::fake([
        'localhost:5000/v2/repo/blobs/*' => Http::response('', 404),
    ]);

    $descriptor = new ImageConfigDescriptor('repo', 'sha256:missing', MediaType::IMAGE_CONFIG_V1, 500);
    expect($this->driver->getImageConfigFromDescriptor($descriptor))->toBeNull();
});

it('throws on connection error for getBlob', function () {
    Http::fake([
        'localhost:5000/v2/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('fail'),
    ]);

    $this->driver->getBlob('repo', 'sha256:abc');
})->throws(Exception::class, 'Connection to registry failed');

it('throws on connection error for getBlobSize', function () {
    Http::fake([
        'localhost:5000/v2/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('fail'),
    ]);

    $this->driver->getBlobSize('repo', 'sha256:abc');
})->throws(Exception::class, 'Connection to registry failed');

it('throws on server error for getBlobSize', function () {
    Http::fake([
        'localhost:5000/v2/repo/blobs/*' => Http::response('server error', 500),
    ]);

    $this->driver->getBlobSize('repo', 'sha256:abc');
})->throws(Exception::class, 'Failed to fetch blob size');

it('includes body in getBlobSize error message', function () {
    Http::fake([
        'localhost:5000/v2/repo/blobs/*' => Http::response('detailed error', 500),
    ]);

    try {
        $this->driver->getBlobSize('repo', 'sha256:abc');
    } catch (Exception $e) {
        expect($e->getMessage())->toContain('Body: detailed error');
    }
});

it('throws on connection error for getImageConfigFromDescriptor', function () {
    Http::fake([
        'localhost:5000/v2/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('fail'),
    ]);

    $descriptor = new ImageConfigDescriptor('repo', 'sha256:cfg', MediaType::IMAGE_CONFIG_V1, 500);
    $this->driver->getImageConfigFromDescriptor($descriptor);
})->throws(Exception::class, 'Connection to registry failed');

it('throws on server error for getImageConfigFromDescriptor', function () {
    Http::fake([
        'localhost:5000/v2/repo/blobs/*' => Http::response('error body', 500),
    ]);

    $descriptor = new ImageConfigDescriptor('repo', 'sha256:cfg', MediaType::IMAGE_CONFIG_V1, 500);
    $this->driver->getImageConfigFromDescriptor($descriptor);
})->throws(Exception::class, 'Failed to fetch image config');

it('includes body in getBlob error message', function () {
    Http::fake([
        'localhost:5000/v2/repo/blobs/*' => Http::response('detailed error', 500),
    ]);

    try {
        $this->driver->getBlob('repo', 'sha256:abc');
    } catch (Exception $e) {
        expect($e->getMessage())->toContain('Body: detailed error');
    }
});
