<?php

use Cainy\Dockhand\Auth\NullAuthenticator;
use Cainy\Dockhand\Drivers\DistributionDriver;
use Cainy\Dockhand\Enums\MediaType;
use Cainy\Dockhand\Exceptions\ManifestBlobUnknownException;
use Cainy\Dockhand\Exceptions\ManifestInvalidException;
use Cainy\Dockhand\Exceptions\UnsupportedException;
use Cainy\Dockhand\Resources\ImageManifest;
use Cainy\Dockhand\Resources\ManifestHead;
use Cainy\Dockhand\Resources\ManifestList;
use Cainy\Dockhand\Resources\PushResult;
use Illuminate\Support\Facades\Http;
use Psr\Log\NullLogger;

beforeEach(function () {
    $this->driver = new DistributionDriver('http://localhost:5000/v2', new NullAuthenticator, new NullLogger);
});

// headManifest
it('heads a manifest successfully', function () {
    Http::fake([
        'localhost:5000/v2/repo/manifests/latest' => Http::response('', 200, [
            'Docker-Content-Digest' => 'sha256:abc123',
            'Content-Length' => '1024',
            'Content-Type' => 'application/vnd.docker.distribution.manifest.v2+json',
        ]),
    ]);

    $result = $this->driver->headManifest('repo', 'latest');
    expect($result)->toBeInstanceOf(ManifestHead::class)
        ->and($result->digest)->toBe('sha256:abc123')
        ->and($result->contentLength)->toBe(1024)
        ->and($result->mediaType)->toBe('application/vnd.docker.distribution.manifest.v2+json');
});

it('returns null for 404 on headManifest', function () {
    Http::fake([
        'localhost:5000/v2/repo/manifests/missing' => Http::response('', 404),
    ]);

    expect($this->driver->headManifest('repo', 'missing'))->toBeNull();
});

it('throws on non-404 error for headManifest', function () {
    Http::fake([
        'localhost:5000/v2/repo/manifests/err' => Http::response('', 500),
    ]);

    $this->driver->headManifest('repo', 'err');
})->throws(Exception::class, 'Failed to HEAD manifest');

it('throws on connection error for headManifest', function () {
    Http::fake([
        'localhost:5000/v2/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('fail'),
    ]);

    $this->driver->headManifest('repo', 'latest');
})->throws(Exception::class, 'Connection to registry failed');

// getManifest
it('gets an image manifest', function () {
    Http::fake([
        'localhost:5000/v2/repo/manifests/latest' => Http::response(sampleManifestData(), 200, [
            'Docker-Content-Digest' => 'sha256:manifest123',
            'Content-Type' => 'application/vnd.docker.distribution.manifest.v2+json',
        ]),
    ]);

    $result = $this->driver->getManifest('repo', 'latest');
    expect($result)->toBeInstanceOf(ImageManifest::class)
        ->and($result->digest)->toBe('sha256:manifest123')
        ->and($result->layers)->toHaveCount(2);
});

it('gets a manifest list', function () {
    Http::fake([
        'localhost:5000/v2/repo/manifests/latest' => Http::response(sampleManifestListData(), 200, [
            'Docker-Content-Digest' => 'sha256:list123',
        ]),
    ]);

    $result = $this->driver->getManifest('repo', 'latest');
    expect($result)->toBeInstanceOf(ManifestList::class)
        ->and($result->manifests)->toHaveCount(2);
});

it('returns null for 404 on getManifest', function () {
    Http::fake([
        'localhost:5000/v2/repo/manifests/missing' => Http::response('', 404),
    ]);

    expect($this->driver->getManifest('repo', 'missing'))->toBeNull();
});

it('falls back to ETag when digest header is empty', function () {
    Http::fake([
        'localhost:5000/v2/repo/manifests/latest' => Http::response(sampleManifestData(), 200, [
            'ETag' => '"sha256:etag123"',
            'Content-Type' => 'application/vnd.docker.distribution.manifest.v2+json',
        ]),
    ]);

    $result = $this->driver->getManifest('repo', 'latest');
    expect($result)->toBeInstanceOf(ImageManifest::class)
        ->and($result->digest)->toBe('sha256:etag123');
});

it('throws when no digest or ETag available', function () {
    Http::fake([
        'localhost:5000/v2/repo/manifests/latest' => Http::response(sampleManifestData(), 200),
    ]);

    $this->driver->getManifest('repo', 'latest');
})->throws(Exception::class, 'did not provide');

it('throws on connection error for getManifest', function () {
    Http::fake([
        'localhost:5000/v2/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('fail'),
    ]);

    $this->driver->getManifest('repo', 'latest');
})->throws(Exception::class, 'Connection to registry failed');

// putManifest
it('puts a manifest with ManifestResource', function () {
    Http::fake([
        'localhost:5000/v2/repo/manifests/latest' => Http::response('', 201, [
            'Location' => '/v2/repo/manifests/sha256:new',
            'Docker-Content-Digest' => 'sha256:new',
        ]),
    ]);

    $manifest = ImageManifest::parse('repo', 'sha256:old', sampleManifestData());
    $result = $this->driver->putManifest('repo', 'latest', $manifest);

    expect($result)->toBeInstanceOf(PushResult::class)
        ->and($result->digest)->toBe('sha256:new');
});

it('puts a manifest with raw string and mediaType', function () {
    Http::fake([
        'localhost:5000/v2/repo/manifests/latest' => Http::response('', 201, [
            'Location' => '/v2/repo/manifests/sha256:new',
            'Docker-Content-Digest' => 'sha256:new',
        ]),
    ]);

    $result = $this->driver->putManifest('repo', 'latest', '{}', MediaType::IMAGE_MANIFEST_V2);
    expect($result)->toBeInstanceOf(PushResult::class);
});

it('throws when string manifest has no mediaType', function () {
    $this->driver->putManifest('repo', 'latest', '{}');
})->throws(InvalidArgumentException::class, 'mediaType is required');

it('throws ManifestBlobUnknownException on 400 BLOB_UNKNOWN', function () {
    Http::fake([
        'localhost:5000/v2/repo/manifests/latest' => Http::response([
            'errors' => [['code' => 'BLOB_UNKNOWN', 'message' => 'blob unknown']],
        ], 400),
    ]);

    $this->driver->putManifest('repo', 'latest', '{}', MediaType::IMAGE_MANIFEST_V2);
})->throws(ManifestBlobUnknownException::class);

it('throws ManifestInvalidException on 400 other', function () {
    Http::fake([
        'localhost:5000/v2/repo/manifests/latest' => Http::response([
            'errors' => [['code' => 'MANIFEST_INVALID', 'message' => 'invalid']],
        ], 400),
    ]);

    $this->driver->putManifest('repo', 'latest', '{}', MediaType::IMAGE_MANIFEST_V2);
})->throws(ManifestInvalidException::class);

it('throws UnsupportedException on 405', function () {
    Http::fake([
        'localhost:5000/v2/repo/manifests/latest' => Http::response('', 405),
    ]);

    $this->driver->putManifest('repo', 'latest', '{}', MediaType::IMAGE_MANIFEST_V2);
})->throws(UnsupportedException::class);

it('handles 200 response from putManifest', function () {
    Http::fake([
        'localhost:5000/v2/repo/manifests/latest' => Http::response('', 200, [
            'Location' => '/v2/repo/manifests/sha256:ok',
            'Docker-Content-Digest' => 'sha256:ok',
        ]),
    ]);

    $result = $this->driver->putManifest('repo', 'latest', '{}', MediaType::IMAGE_MANIFEST_V2);
    expect($result)->toBeInstanceOf(PushResult::class);
});

it('throws on connection error for putManifest', function () {
    Http::fake([
        'localhost:5000/v2/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('fail'),
    ]);

    $this->driver->putManifest('repo', 'latest', '{}', MediaType::IMAGE_MANIFEST_V2);
})->throws(Exception::class, 'Connection to registry failed');

it('throws on server error for getManifest', function () {
    Http::fake([
        'localhost:5000/v2/repo/manifests/latest' => Http::response('error body', 500),
    ]);

    $this->driver->getManifest('repo', 'latest');
})->throws(Exception::class, 'Failed to fetch manifest');

it('throws on missing mediaType in manifest', function () {
    $data = sampleManifestData();
    unset($data['mediaType']);

    Http::fake([
        'localhost:5000/v2/repo/manifests/latest' => Http::response($data, 200, [
            'Docker-Content-Digest' => 'sha256:abc',
        ]),
    ]);

    $this->driver->getManifest('repo', 'latest');
})->throws(Exception::class, 'does not contain');

it('throws on unsupported media type in getManifest', function () {
    $data = sampleManifestData();
    $data['mediaType'] = 'application/vnd.docker.container.image.v1+json';

    Http::fake([
        'localhost:5000/v2/repo/manifests/latest' => Http::response($data, 200, [
            'Docker-Content-Digest' => 'sha256:abc',
        ]),
    ]);

    $this->driver->getManifest('repo', 'latest');
})->throws(Exception::class, 'Unsupported media type');

it('throws on non-201/200 putManifest failure', function () {
    Http::fake([
        'localhost:5000/v2/repo/manifests/latest' => Http::response('error', 500),
    ]);

    $this->driver->putManifest('repo', 'latest', '{}', MediaType::IMAGE_MANIFEST_V2);
})->throws(Exception::class, 'Failed to push manifest');

it('throws on invalid JSON from getManifest', function () {
    Http::fake([
        'localhost:5000/v2/repo/manifests/latest' => Http::response('not-json{{{', 200, [
            'Docker-Content-Digest' => 'sha256:abc',
            'Content-Type' => 'application/vnd.docker.distribution.manifest.v2+json',
        ]),
    ]);

    $this->driver->getManifest('repo', 'latest');
})->throws(Exception::class, 'Failed to decode manifest JSON');

it('gets manifest from manifest list entry', function () {
    Http::fake([
        'localhost:5000/v2/repo/manifests/sha256:entry*' => Http::response(sampleManifestData(), 200, [
            'Docker-Content-Digest' => 'sha256:entry1',
        ]),
    ]);

    $entry = \Cainy\Dockhand\Resources\ManifestListEntry::create(
        'repo',
        'sha256:entry1',
        MediaType::IMAGE_MANIFEST_V2,
        528,
        \Cainy\Dockhand\Resources\Platform::create('linux', 'amd64'),
    );

    $result = $this->driver->getManifestFromManifestListEntry($entry);
    expect($result)->toBeInstanceOf(ImageManifest::class);
});
